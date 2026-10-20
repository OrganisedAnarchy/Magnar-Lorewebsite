# Magnar Lore

A base Laravel application with Vue, SCSS, and Docker.

## Run with Docker

1. Copy `.env.example` to `.env` (PowerShell: `Copy-Item .env.example .env`).
2. Build the application image and generate the Laravel application key:

   ```sh
   docker compose build
   docker compose run --rm app php artisan key:generate
   ```

3. Start the containers:

   ```sh
   docker compose up
   ```

4. Run database migrations in a second terminal:

   ```sh
   docker compose exec app php artisan migrate
   ```

The site is available at <http://localhost:8080> by default. If that port is
already in use, set `APP_PORT` in `.env` to another host port. The Docker image
builds the Vue and SCSS assets as part of its build. The Vue entry point is
`resources/js/app.js`, the root component is `resources/js/App.vue`, and global
styles are in `resources/scss/app.scss`.
