# Running SeaJet with Docker

This lets someone install and run the app on a new machine with only Docker
installed — no local PHP, Composer, Node, or MySQL setup required.

## Prerequisites

- Docker Desktop (or Docker Engine + Compose plugin) v20.10+

## First-time setup

```bash
# 1. Copy the docker env template to the real .env the app will use
cp .env.docker .env

# 2. Build the app image and start everything (app, MySQL, phpMyAdmin)
docker compose up -d --build
```

On first boot the `app` container's entrypoint ([docker/entrypoint.sh](../../docker/entrypoint.sh)) will:

- generate `APP_KEY` if it isn't already set in `.env`
- wait for MySQL to accept connections
- run `php artisan migrate --force`
- link `storage` -> `public/storage`
- cache config/routes/views

## URLs

- App: http://localhost:8080
- phpMyAdmin: http://localhost:8081 (server `db`, user `seajet`, password `seajet`)
- MySQL (host access): `localhost:3307` (user `seajet` / password `seajet`, root password `root`)

## Seeding demo data

```bash
docker compose exec app php artisan db:seed --class=ErpDemoSeeder
```

## Common commands

```bash
# Tail logs
docker compose logs -f app

# Run artisan/composer inside the container
docker compose exec app php artisan migrate
docker compose exec app composer install

# Rebuild after a Dockerfile/dependency change
docker compose up -d --build

# Stop everything (keeps DB/storage data)
docker compose down

# Stop and wipe DB/storage volumes too
docker compose down -v
```

## Customizing for another environment

Edit `.env` (not `.env.docker`, which is only the template committed to the
repo) to point at a different database, mail server, or app URL. `.env` is
bind-mounted into the container, so changes take effect on the next
`docker compose restart app`.
