# Installation

Use PHP 8.2+, MySQL 8/MariaDB equivalent and point the web document root at `public/`.

1. Copy `.env.example` to `.env`; set `APP_ENV=production`, `APP_DEBUG=false`, a HTTPS `APP_URL` and database credentials.
2. Create the database and run `php bin/migrate.php`.
3. Ensure `storage/private`, `storage/exports`, `storage/logs` are writable by PHP and outside public root.
4. Run `php bin/seed.php` only when initial data is desired.
5. Configure cron for any queued exports/backups used by the hosting environment.

Never use `bin/clean-install.ps1` against production. It only accepts disposable names prefixed with `torneios_test_`.
