param([string]$DatabaseName = "torneios_test_disposable")
$ErrorActionPreference='Stop'
if ($DatabaseName -notmatch '^torneios_test_[a-z0-9_]+$') { throw 'Refusing unsafe database name.' }
$mysql='C:\xampp\mysql\bin\mysql.exe'; $php='C:\xampp\php\php.exe'
if (!(Test-Path $mysql)) { throw 'MySQL client not found. Set path for your environment.' }
& $mysql -u root -e "DROP DATABASE IF EXISTS ``$DatabaseName``; CREATE DATABASE ``$DatabaseName`` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
try { $env:DB_NAME=$DatabaseName; $env:APP_ENV='testing'; & $php bin/migrate.php; if($LASTEXITCODE){throw 'Migration failed'}; & $php bin/seed.php; if($LASTEXITCODE){throw 'Seed failed'}; & $php tests/integration.php; if($LASTEXITCODE){throw 'Integration failed'}; Write-Output "CLEAN_INSTALL_OK $DatabaseName" } finally { & $mysql -u root -e "DROP DATABASE IF EXISTS ``$DatabaseName``;" }
