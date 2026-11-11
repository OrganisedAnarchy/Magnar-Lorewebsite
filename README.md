# The World of Magnar

![Version](https://img.shields.io/badge/version-0.3-blue)
![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white)
![Vue](https://img.shields.io/badge/Vue-3-42b883?logo=vuedotjs&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.4-777BB4?logo=php&logoColor=white)

A full-stack lore wiki and reference site for **Magnar**, a custom tabletop RPG
setting. It combines a public-facing world guide (an animated celestial system
and an interactive map) with an admin-only authoring suite for lore entries,
constructed languages, books and locations.

**Current version: 0.3**

## Highlights

- **Reactive Vue admin forms with draft autosave.** Every lore category, language,
  book and location is edited in a Vue 3 component. Changes are autosaved as a
  local draft as you type, restored on your next visit (with a one-click discard),
  and only posted to Laravel when you press **Save**, where Laravel's form
  requests validate and persist them.
- **Wiki-style linking.** A CodeMirror 6 Markdown editor supports
  `[[Title]]`, `[[Title|Alias]]` and `[[Title@Category]]` links with
  autocomplete, live "exists / missing" link checking, and Ctrl/Cmd+click to open
  or create the linked entry.
- **Ten lore categories** (Magic, Organisations, Nature, People, Religion,
  Technology, Items, History, Culture, Cosmology) with free-form sub-categories
  that are remembered and suggested across entries.
- **In-world calendar.** Birth, death and publication dates use Magnar's
  day-month-cycle-year calendar, with "circa" and "unknown" flags.
- **Relationships.** Family trees for people, plus ranks and memberships for
  organisations.
- **Constructed-language tooling.** A language builder with
  syllable inventories, a lexicon, random word and passage generators, and
  vocabulary coverage measured against an extended Swadesh list.
- **Books.** Author lookup (auto-creating missing people), available languages,
  summaries and full text.
- **Interactive map.** Locations can carry an uploaded map image and contain child
  locations pinned on their parent's map, browsed through a JSON-backed Vue viewer.
- **AWS S3 image storage.** Uploaded imagery (such as location maps) is stored on
  a configurable disk, which can be switched to an AWS S3 bucket. Images in S3
  are shown in the admin through short-lived signed URLs, and replaced or
  removed images are deleted from the bucket.
- **Celestial system.** An animated diagram of Magnar's three moons, two suns and
  five neighbouring planets. Magnar's 780-day year is two 390-day cycles of
  thirteen 30-day months.
- **Access control.** Session authentication with registration and sign-in, and
  an admin middleware guarding the authoring area.

## Technology

| Layer | Technologies |
| --- | --- |
| Backend | PHP 8.4, Laravel 13, Eloquent ORM, Form Requests, database-backed sessions |
| Frontend | Vue 3 (Composition API, `<script setup>`), SCSS, CodeMirror 6 |
| Build tooling | Vite 8, `laravel-vite-plugin`, `@vitejs/plugin-vue`, Sass |
| Database | MySQL 8.4 (SQLite supported for local testing) |
| Storage | Local public disk or AWS S3 (`league/flysystem-aws-s3-v3`) for uploaded images |
| Infrastructure | Docker, Docker Compose, Apache, multi-stage image build (Node 24 + PHP 8.4) |
| Testing & quality | PHPUnit 12, Laravel Pint |
| Tooling | Laravel Boost, Laravel Pail, Laravel Tinker |

## Architecture notes

- Public pages are Blade views; interactive pieces (admin forms, the map and the
  celestial diagram) are Vue components mounted from `resources/js/`.
- Entry points: `resources/js/app.js` (public site), `resources/js/admin.js`
  (admin forms) and `resources/js/map.js` (map viewer).
- Admin forms share a `useFormDraft` composable
  (`resources/js/useFormDraft.js`) that snapshots form state, debounces autosave
  to `localStorage`, restores drafts, and clears them on submit. Drafts are keyed
  per form and record, so editing several entries never mixes drafts.
- Admin routes live under `/admin` behind the `auth` and `admin` middleware.
  Controllers in `app/Http/Controllers/Admin` use dedicated Form Requests for
  validation.
- Feature and unit tests live in `tests/`.

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

The Vite container keeps its own `node_modules` volume. After pulling changes to
`package.json` or `package-lock.json`, restart it to install the updated dependencies:

```sh
docker compose -f compose.yaml -f compose.dev.yaml restart vite
```

To stop the development stack:

```sh
docker compose -f compose.yaml -f compose.dev.yaml down
```

## Accounts and admin access

The welcome page links to account creation at `/register` and sign-in at
`/login`. Accounts use Laravel's session authentication and are stored in the
configured database. Registration requires a name, unique email address, and
confirmed password of at least eight characters. The admin area at `/admin`
requires an account with the `is_admin` flag set.

## Image storage (AWS S3)

Uploaded images are written to the disk named by `IMAGES_DISK` in `.env`. It
defaults to `public` (local storage). To store images in AWS S3, set:

```dotenv
IMAGES_DISK=s3
AWS_ACCESS_KEY_ID=your-access-key
AWS_SECRET_ACCESS_KEY=your-secret-key
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-bucket-name
```

Optional settings for S3-compatible services are `AWS_URL`, `AWS_ENDPOINT` and
`AWS_USE_PATH_STYLE_ENDPOINT`. Buckets can stay private: the admin displays
images using temporary signed URLs. Uploads accept JPG, PNG, WebP and GIF files
up to 10 MB.

## Testing and formatting

```sh
php artisan test --compact
vendor/bin/pint --dirty
```

## Product direction

The World of Magnar is aimed at TTRPG players: the long-term goal is a concise,
at-a-glance reference where a player creates a character, chooses details such as
beliefs, birthplace and race, and sees the parts of the setting that character
would know. The admin lore tools exist to feed that experience.

## Versioning

This project follows a simple `MAJOR.MINOR` scheme.

| Version | Summary |
| --- | --- |
| 0.3 | AWS S3 support for uploaded images, with a configurable images disk and signed URLs for private buckets. |
| 0.2 | Vue-based forms for all lore categories, languages, books and locations with reactive draft autosave; locations and interactive map; family trees; organisation members and ranks; language tools. |
