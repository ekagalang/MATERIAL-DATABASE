# Repository Guidelines

## Project Structure & Module Organization
- `app/` holds application logic (controllers, models, helpers). Example: `app/Http/Controllers/`.
- `routes/` contains route definitions (`routes/web.php`).
- `resources/views/` contains Blade templates; `resources/js/` and `resources/css/` are bundled via Vite.
- `database/migrations/` and `database/seeders/` manage schema and seed data.
- `public/` hosts static assets and Vite build output.
- `tests/` contains automated tests (Pest).

## Build, Test, and Development Commands
- `composer run setup` installs PHP/JS deps, creates `.env`, generates key, runs migrations, and builds assets.
- `composer run dev` starts the PHP server, queue listener, and Vite dev server.
- `php artisan serve` runs the Laravel dev server only.
- `npm run dev` runs the Vite dev server only.
- `npm run build` produces production assets.
- `composer test` or `php artisan test` runs the test suite.
- `npm run format` formats JS/CSS/JSON/PHP/Blade via Prettier.

## Coding Style & Naming Conventions
- Indentation: 4 spaces, LF line endings, UTF-8 (see `.editorconfig`).
- PHP follows PSR-12; use `vendor/bin/pint` if formatting is needed.
- Blade/JS/CSS should be formatted with Prettier.
- Naming: Controllers use `*Controller.php`, models are singular PascalCase, migrations are timestamped and descriptive.

## Testing Guidelines
- Tests live in `tests/` and typically end with `Test.php`.
- Prefer focused runs during development: `php artisan test --filter Materials`.
- Update or add tests when changing behavior or calculations.

## Commit & Pull Request Guidelines
- Recent commits use short, imperative summaries like "Fix …" or "Adjust …". Keep the first line concise and specific.
- PRs should include a summary, testing notes, and screenshots for UI/Blade changes. Link related issues when available.

## Configuration & Data
- Copy `.env` from `.env.example` and set app/DB credentials before running.
- Schema changes go in `database/migrations/`; seed defaults in `database/seeders/`.
