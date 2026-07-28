# Backup and Restore

Use `bin/backup.ps1 -DatabaseName torneios -Destination D:\backups` only from a protected operator machine. It creates a database dump, public/private file archive and SHA-256 manifest. Copy the resulting directory to encrypted off-host storage; do not keep it below the web root.

Use `bin/restore-backup.ps1` only for a disposable database name beginning `torneios_test_`. It refuses production-like names. Restore database first, validate checksums, then restore public/private paths from `files.zip`. Maintain daily, weekly and monthly rotation and record a restore test.
