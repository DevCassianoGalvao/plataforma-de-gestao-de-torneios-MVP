param([Parameter(Mandatory=$true)][string]$DatabaseName,[Parameter(Mandatory=$true)][string]$Destination)
$ErrorActionPreference='Stop'
if($DatabaseName -notmatch '^[a-zA-Z0-9_]+$'){throw 'Unsafe database name.'}
$root=Split-Path -Parent $PSScriptRoot;$stamp=Get-Date -Format 'yyyyMMdd-HHmmss';$target=Join-Path $Destination "torneios-$stamp"
New-Item -ItemType Directory -Force -Path $target | Out-Null
$mysql='C:\xampp\mysql\bin\mysqldump.exe';if(!(Test-Path $mysql)){throw 'mysqldump not found.'}
& $mysql -u root --single-transaction --routines --events $DatabaseName | Out-File -Encoding utf8 (Join-Path $target 'database.sql')
Compress-Archive -Path (Join-Path $root 'public\uploads-public\*'),(Join-Path $root 'storage\private\*') -DestinationPath (Join-Path $target 'files.zip') -Force
Get-FileHash (Join-Path $target 'database.sql'),(Join-Path $target 'files.zip') -Algorithm SHA256 | Format-Table -AutoSize | Out-File (Join-Path $target 'SHA256SUMS.txt')
Write-Output "BACKUP_OK $target"
