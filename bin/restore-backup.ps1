param([Parameter(Mandatory=$true)][string]$DatabaseName,[Parameter(Mandatory=$true)][string]$BackupDirectory)
$ErrorActionPreference='Stop'
if($DatabaseName -notmatch '^torneios_test_[a-z0-9_]+$'){throw 'Restore only allowed into disposable database.'}
$sql=Join-Path $BackupDirectory 'database.sql';$sum=Join-Path $BackupDirectory 'SHA256SUMS.txt';if(!(Test-Path $sql)||!(Test-Path $sum)){throw 'Incomplete backup.'}
$mysql='C:\xampp\mysql\bin\mysql.exe';if(!(Test-Path $mysql)){throw 'mysql not found.'}
& $mysql -u root -e "DROP DATABASE IF EXISTS ``$DatabaseName``; CREATE DATABASE ``$DatabaseName`` CHARACTER SET utf8mb4;"
Get-Content $sql | & $mysql -u root $DatabaseName
Write-Output "RESTORE_OK $DatabaseName"
