# System update console

The Administrator navbar includes **System Update**, which displays the deployed Git branch, commit, tag/version, commit date, working-tree state, and the latest update log.

The web action is protected by `system_updates.edit`, CSRF validation, an `UPDATE` confirmation phrase, a clean-worktree preflight, an exclusive process lock, and the activity log. It never accepts shell commands or user-selected arguments.

## Update sequence

1. Verify the repository and clean working tree.
2. Enter Laravel maintenance mode.
3. Run `git pull --ff-only <remote> <current-branch>`.
4. Run `composer install --no-interaction --prefer-dist --optimize-autoloader` (`--no-dev` in production by default).
5. Run `php artisan migrate --force`.
6. Clear application, configuration, and view caches; then rebuild configuration and view caches.
7. Leave maintenance mode, including when an update step fails.

The updater runs in a detached process and the page polls its JSON status. State and the last 500 output lines are stored under `storage/app/system-updates/`. A failed update never automatically resets Git or rolls back a database migration; recovery remains an explicit administrator operation.

## Configuration

```dotenv
SYSTEM_UPDATE_ENABLED=true
SYSTEM_UPDATE_REMOTE=origin
SYSTEM_UPDATE_NO_DEV=true
SYSTEM_UPDATE_TIMEOUT=1200
COMPOSER_BINARY=composer
```

Set `SYSTEM_UPDATE_ENABLED=false` where deployment is handled by CI/CD. The PHP/web-server account must have read/write access to the application and permission to execute Git, Composer, and the PHP CLI. SSH or token credentials for the configured Git remote must be non-interactive.

The same guarded workflow can be run from a terminal with:

```bash
php artisan system:update --initiated-by=USER_ID
```
