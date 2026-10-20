# The World of Magnar

A player-focused TTRPG reference for the world of Magnar, built with Laravel,
Vue, SCSS, and Docker.

## Product direction

The World of Magnar is not a general-purpose worldbuilding wiki or a World
Anvil clone. Its intended audience is a TTRPG player who signs in, creates a
character, chooses details such as which gods they believe in, their birthplace,
and race, and gets a concise at-a-glance reference to the parts of the setting that
character would know. Keep future features focused on player characters and
contextual, character-known information rather than open-ended lore authoring.

The celestial system presents Magnar and its three moons, two suns, and five
neighboring planets. Magnar's year is 780 days: two 390-day cycles, each
containing thirteen 30-day months. Its eight seasonal stages place winter on
both sides of the cycle boundary, making one 195-day winter.

## Run with Docker

1. Copy `.env.example` to `.env` (PowerShell: `Copy-Item .env.example .env`).
2. Build the application image and generate the Laravel application key:

   ```sh
   docker compose build
   docker compose run --rm app php artisan key:generate
   ```

3. Start the development stack with automatic file watching and Vue/SCSS hot reload:

   ```sh
   docker compose -f compose.yaml -f compose.dev.yaml up -d --build
   ```

4. Run database migrations in a second terminal:

   ```sh
   docker compose -f compose.yaml -f compose.dev.yaml exec app php artisan migrate
   ```

The site is available at <http://localhost:8080> by default. If that port is
already in use, set `APP_PORT` in `.env` to another host port. The Docker image
serves Laravel while a separate Vite container watches source files and serves
the Vue and SCSS assets with hot module replacement. Changes to backend PHP,
routes, Blade templates, Vue components, and SCSS are reflected automatically.
The Vue entry point is `resources/js/app.js`, the root component is
`resources/js/App.vue`, and global styles are in `resources/scss/app.scss`.

After the initial start, changes are watched automatically; no image rebuild is
needed. To stop the development stack:

```sh
docker compose -f compose.yaml -f compose.dev.yaml down
```

## Accounts

The welcome page links to account creation at `/register` and sign-in at
`/login`. Accounts use Laravel's session authentication and are stored in the
configured database. Registration requires a name, unique email address, and
confirmed password of at least eight characters.
