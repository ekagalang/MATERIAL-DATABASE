# Repository Guidelines

## Project Structure & Module Organization
- `app/` contains core Laravel code (controllers, models, services, helpers), with controllers in `app/Http/Controllers/`.
- `routes/` defines HTTP routes (`routes/web.php`, plus API routes if added).
- `resources/views/` stores Blade templates; `resources/js/` and `resources/css/` are built through Vite.
- `database/migrations/` and `database/seeders/` manage schema evolution and seed data.
- `public/` serves static assets and Vite build output.
- `tests/` contains Pest/PHPUnit tests.

## Build, Test, and Development Commands
- `composer run setup` — install dependencies, copy `.env`, generate app key, run migrations, and build assets.
- `composer run dev` — run the Laravel server, queue worker, log watcher, and Vite dev server together.
- `php artisan serve` — run only the Laravel app server.
- `npm run dev` — run only the Vite asset watcher.
- `npm run build` — generate production frontend assets.
- `composer test` or `php artisan test` — run the full test suite.
- `npm run format` — format Blade, PHP, JS, CSS, and JSON files via Prettier.

## Coding Style & Naming Conventions
- Follow `.editorconfig`: UTF-8, LF endings, 4-space indentation.
- Use PSR-12 for PHP; run `vendor/bin/pint` when needed.
- Use Prettier formatting for Blade/JS/CSS/JSON.
- Naming conventions: controllers `*Controller.php`, singular PascalCase models, descriptive timestamped migrations.

## Testing Guidelines
- Add tests under `tests/`, using `*Test.php` file names.
- Prefer targeted runs during development, e.g. `php artisan test --filter Materials`.
- Add or update tests for behavior changes, especially business logic and calculations.

## Commit & Pull Request Guidelines
- Keep commit subjects short, imperative, and specific (e.g., `Fix material total calculation`).
- PRs should include: concise summary, testing notes (`php artisan test` output), and screenshots for UI/Blade updates.
- Link related issues and call out schema/config changes clearly.

## Configuration & Data
- Copy `.env.example` to `.env` and set local app/database credentials before first run.
- Put schema changes in `database/migrations/` and default seed data in `database/seeders/`.
