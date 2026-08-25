<?php

namespace App\Console\Commands;

use App\Services\System\SystemUpdateService;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class SystemUpdate extends Command
{
    protected $signature = 'system:update {--initiated-by=0} {--company-id=} {--dry-run}';
    protected $description = 'Safely update the ERP from its configured Git remote.';

    private $updater;
    private $state;

    public function __construct(SystemUpdateService $updater)
    {
        parent::__construct();
        $this->updater = $updater;
    }

    public function handle()
    {
        $directory = dirname($this->updater->lockPath());
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }
        $lock = fopen($this->updater->lockPath(), 'c+');
        if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
            $this->error('Another system update is already running.');
            return 1;
        }

        $this->state = $this->updater->readState();
        $this->state['status'] = $this->option('dry-run') ? 'checking' : 'running';
        $this->state['message'] = $this->option('dry-run') ? 'Validating update prerequisites.' : 'System update is running.';
        $this->state['started_at'] = $this->state['started_at'] ?? date(DATE_ATOM);
        $this->state['initiated_by'] = (int) $this->option('initiated-by');
        $this->state['company_id'] = $this->option('company-id') ? (int) $this->option('company-id') : null;
        $this->state['log'] = [];
        $this->saveState();

        $maintenance = false;
        try {
            $status = $this->updater->assertReadyForCommand($this->option('dry-run'));
            $this->state['before_version'] = $status['version'];
            $this->log('Preflight passed: '.$status['version']);

            if ($this->option('dry-run')) {
                $this->state['status'] = 'ready';
                $this->state['message'] = 'Update prerequisites passed.';
                $this->state['finished_at'] = date(DATE_ATOM);
                $this->saveState();
                return 0;
            }

            $this->runStep('Maintenance mode', [PHP_BINARY, 'artisan', 'down', '--message=System update in progress']);
            $maintenance = true;
            $this->runStep('Git pull', ['git', 'pull', '--ff-only', config('system_update.remote', 'origin'), $status['branch']]);

            $composer = config('system_update.composer_binary', 'composer');
            $composerCommand = [$composer, 'install', '--no-interaction', '--prefer-dist', '--optimize-autoloader'];
            if (config('system_update.composer_no_dev', false)) {
                $composerCommand[] = '--no-dev';
            }
            $this->runStep('Composer install', $composerCommand);
            $this->runStep('Database migrations', [PHP_BINARY, 'artisan', 'migrate', '--force']);
            $this->runStep('Clear application cache', [PHP_BINARY, 'artisan', 'cache:clear']);
            $this->runStep('Clear configuration cache', [PHP_BINARY, 'artisan', 'config:clear']);
            $this->runStep('Clear compiled views', [PHP_BINARY, 'artisan', 'view:clear']);
            $this->runStep('Cache configuration', [PHP_BINARY, 'artisan', 'config:cache']);
            $this->runStep('Cache views', [PHP_BINARY, 'artisan', 'view:cache']);

            $after = $this->updater->status();
            $this->state['status'] = 'completed';
            $this->state['message'] = 'System update completed successfully.';
            $this->state['after_version'] = $after['version'];
            $this->state['finished_at'] = date(DATE_ATOM);
            $this->saveState();
            return 0;
        } catch (\Throwable $e) {
            $this->log('FAILED: '.$e->getMessage());
            $this->state['status'] = 'failed';
            $this->state['message'] = 'System update failed. Review the update log.';
            $this->state['finished_at'] = date(DATE_ATOM);
            $this->saveState();
            return 1;
        } finally {
            if ($maintenance) {
                try {
                    $this->runStep('Leave maintenance mode', [PHP_BINARY, 'artisan', 'up']);
                } catch (\Throwable $e) {
                    $this->log('WARNING: Could not leave maintenance mode: '.$e->getMessage());
                    $this->state['status'] = 'failed';
                    $this->state['message'] = 'Update finished, but maintenance mode could not be disabled. Run php artisan up manually.';
                    $this->state['finished_at'] = date(DATE_ATOM);
                    $this->saveState();
                }
            }
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    private function runStep($label, array $command)
    {
        $this->log('Starting: '.$label);
        $process = new Process($command, base_path());
        $process->setTimeout((int) config('system_update.timeout', 1200));
        $process->run(function ($type, $buffer) {
            foreach (preg_split('/\r\n|\r|\n/', trim($buffer)) as $line) {
                if ($line !== '') {
                    $this->log($line);
                }
            }
        });
        if (!$process->isSuccessful()) {
            throw new \RuntimeException($label.' failed with exit code '.$process->getExitCode().'.');
        }
        $this->log('Completed: '.$label);
    }

    private function log($line)
    {
        $line = '['.date('Y-m-d H:i:s').'] '.$line;
        $this->line($line);
        $this->state['log'][] = $line;
        $this->state['log'] = array_slice($this->state['log'], -500);
        $this->saveState();
    }

    private function saveState()
    {
        $this->updater->writeState($this->state);
    }
}
