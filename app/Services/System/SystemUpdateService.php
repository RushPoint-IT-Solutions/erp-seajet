<?php

namespace App\Services\System;

use Symfony\Component\Process\Process;

class SystemUpdateService
{
    public function status()
    {
        $state = $this->readState();
        $branch = $this->runReadOnly(['git', 'branch', '--show-current']);
        $commit = $this->runReadOnly(['git', 'rev-parse', '--short', 'HEAD']);
        $tag = $this->runReadOnly(['git', 'describe', '--tags', '--exact-match', 'HEAD']);
        $commitDate = $this->runReadOnly(['git', 'show', '-s', '--format=%cI', 'HEAD']);
        $dirtyOutput = $this->runReadOnly(['git', 'status', '--porcelain'], true);
        $dirtyFiles = array_values(array_filter(preg_split('/\r\n|\r|\n/', trim($dirtyOutput))));

        return array_merge([
            'enabled' => (bool) config('system_update.enabled', true),
            'repository' => $commit !== '',
            'branch' => $branch ?: 'unknown',
            'commit' => $commit ?: 'unknown',
            'commit_date' => $commitDate ?: null,
            'version' => $tag ?: (($branch ?: 'unknown').'@'.($commit ?: 'unknown')),
            'dirty' => count($dirtyFiles) > 0,
            'dirty_files' => $dirtyFiles,
            'running' => in_array($state['status'] ?? null, ['queued', 'running'], true),
            'state' => $state,
        ], []);
    }

    public function assertReady()
    {
        $status = $this->status();
        if (!$status['enabled']) {
            throw new \RuntimeException('Web-based system updates are disabled.');
        }
        if (!$status['repository']) {
            throw new \RuntimeException('The application directory is not a Git repository.');
        }
        if ($status['running']) {
            throw new \RuntimeException('A system update is already queued or running.');
        }
        if ($status['dirty']) {
            throw new \RuntimeException('Update blocked: commit or stash all working-tree changes first.');
        }

        return $status;
    }

    public function assertReadyForCommand($dryRun = false)
    {
        $status = $this->status();
        if (!$status['enabled']) {
            throw new \RuntimeException('Web-based system updates are disabled.');
        }
        if (!$status['repository']) {
            throw new \RuntimeException('The application directory is not a Git repository.');
        }
        if ($status['dirty']) {
            throw new \RuntimeException('Update blocked: commit or stash all working-tree changes first.');
        }

        return $status;
    }

    public function queue($userId, $companyId = null)
    {
        $status = $this->assertReady();
        $this->writeState([
            'status' => 'queued',
            'message' => 'System update queued.',
            'started_at' => date(DATE_ATOM),
            'finished_at' => null,
            'initiated_by' => (int) $userId,
            'company_id' => $companyId ? (int) $companyId : null,
            'before_version' => $status['version'],
            'after_version' => null,
            'log' => [],
        ]);

        $artisan = base_path('artisan');
        try {
            if (DIRECTORY_SEPARATOR === '\\') {
                $quote = function ($value) { return "'".str_replace("'", "''", $value)."'"; };
                $arguments = $quote($artisan).','.$quote('system:update').','.$quote('--initiated-by='.(int) $userId);
                if ($companyId) {
                    $arguments .= ','.$quote('--company-id='.(int) $companyId);
                }
                $script = 'Start-Process -FilePath '.$quote(PHP_BINARY).' -ArgumentList '.$arguments
                    .' -WorkingDirectory '.$quote(base_path()).' -WindowStyle Hidden';
                $launcher = new Process(['powershell.exe', '-NoProfile', '-NonInteractive', '-Command', $script], base_path());
                $launcher->setTimeout(20);
                $launcher->mustRun();
            } else {
                $command = 'nohup '.escapeshellarg(PHP_BINARY).' '.escapeshellarg($artisan)
                    .' system:update --initiated-by='.(int) $userId;
                if ($companyId) {
                    $command .= ' --company-id='.(int) $companyId;
                }
                $command .= ' >/dev/null 2>&1 &';
                $launcher = new Process(['/bin/sh', '-c', $command], base_path());
                $launcher->setTimeout(20);
                $launcher->mustRun();
            }
        } catch (\Throwable $e) {
            $failed = $this->readState();
            $failed['status'] = 'failed';
            $failed['message'] = 'The background updater could not be started.';
            $failed['finished_at'] = date(DATE_ATOM);
            $failed['log'][] = '['.date('Y-m-d H:i:s').'] FAILED: '.$e->getMessage();
            $this->writeState($failed);
            throw $e;
        }

        return $this->readState();
    }

    public function readState()
    {
        $path = $this->statePath();
        if (!is_file($path)) {
            return ['status' => 'idle', 'message' => 'No system update has been run.', 'log' => []];
        }

        $decoded = json_decode((string) file_get_contents($path), true);
        return is_array($decoded) ? $decoded : ['status' => 'unknown', 'message' => 'Update state could not be read.', 'log' => []];
    }

    public function writeState(array $state)
    {
        $directory = dirname($this->statePath());
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }
        $temporary = $this->statePath().'.tmp';
        file_put_contents($temporary, json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
        rename($temporary, $this->statePath());
    }

    public function statePath()
    {
        return storage_path('app/system-updates/status.json');
    }

    public function lockPath()
    {
        return storage_path('app/system-updates/update.lock');
    }

    private function runReadOnly(array $command, $allowFailure = false)
    {
        try {
            $process = new Process($command, base_path());
            $process->setTimeout(10);
            $process->run();
            if (!$allowFailure && !$process->isSuccessful()) {
                return '';
            }
            return trim($process->getOutput());
        } catch (\Throwable $e) {
            return '';
        }
    }
}
