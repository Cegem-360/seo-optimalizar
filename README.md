# SEO Optimalizar

Laravel 12 application using Filament v4, Livewire v3, Tailwind CSS v4, and Pest v4.

- PHP 8.4.12
- laravel/framework v12
- filament/filament v4
- livewire/livewire v3
- tailwindcss v4
- pestphp/pest v4
- laravel/pint v1
- rector/rector v2
- laravel/sail v1
- Served locally via Laravel Herd

## Quick start

Prerequisites:
- PHP 8.4+, Composer 2+
- Node 20+ and npm
- A database (MySQL/MariaDB/Postgres or SQLite)
- Laravel Herd (recommended) or Docker with Sail

Install:
- git clone <repo> && cd seo-optimalizar
- composer install
- cp .env.example .env
- php artisan key:generate
- Configure DB in .env
- php artisan migrate --seed (optional seeders)
- npm ci
- npm run dev (for HMR) or npm run build

Open the site:
- Using Herd: open your local domain seo-optimalizar.test
- If assets don’t load, run npm run dev or npm run build

Sail (optional, Docker):
- php -d detect_unicode=0 artisan sail:install
- ./vendor/bin/sail up -d
- Use ./vendor/bin/sail <command> for PHP/Artisan/NPM

## Testing

- php artisan test
- Filter a test: php artisan test --filter=SomeFeature
- Browser tests (Pest v4) should live in tests/Browser

## Code style and refactoring

- Format: vendor/bin/pint --dirty
- Automated refactors: vendor/bin/rector process

## Common tasks

- Fresh DB: php artisan migrate:fresh --seed
- Storage symlink: php artisan storage:link
- Vite manifest error: run npm run dev or npm run build

## Notes for this stack

- Laravel 12 streamlined structure: configure middleware/routes in bootstrap/app.php (no Console Kernel).
- Prefer Eloquent relationships, form requests for validation, and named routes.
- Filament v4 and Livewire v3 are installed; follow existing Resource/Panel conventions.
- Tailwind v4: CSS should import with @import "tailwindcss"; (no corePlugins flag).

## Troubleshooting

- UI not updating: ensure npm run dev is running or rebuild assets.
- 419/CSRF or auth issues in Livewire: verify sessions and app key set.
- Slow queries: add eager loading and indexes where appropriate.

## Contributing

- Match existing conventions.
- Reuse components before creating new ones.
- Run Pint and relevant tests before committing.
- Avoid dependency changes without approval.
- Ask if you need additional factories/seeders or tests created.
