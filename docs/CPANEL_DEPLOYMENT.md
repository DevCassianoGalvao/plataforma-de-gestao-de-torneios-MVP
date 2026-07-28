# cPanel Checklist

## Target URL

The test deployment URL is `https://www.cassianogalvao.com.br/copa-online`. Configure `APP_URL` exactly with this value and `APP_BASE_PATH=/copa-online`. Point the `copa-online` directory document root to this repository `public/` directory, or copy the contents of `public/` into `public_html/copa-online` while keeping `app/`, `storage/`, `database/`, `.env` and `vendor/` outside public web root.

- Select PHP 8.2 and enable PDO MySQL, Fileinfo and ZipArchive.
- Set document root to `public/`; retain `.env` and `storage/` above it.
- Copy `.env`, set `APP_ENV=production`, `APP_DEBUG=false` and HTTPS URL.
- Import/create MySQL database, run migrations from terminal or temporary protected runner.
- Set directories `storage/private`, `storage/exports`, `storage/logs` writable (0750 where supported).
- Enable HTTPS, `public/.htaccess`, cron jobs, SMTP and off-host backups.
- Verify login, private download, PDF, ZIP and export after deployment.
