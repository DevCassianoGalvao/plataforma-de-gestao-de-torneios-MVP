<?php
declare(strict_types=1);
$script=dirname(__DIR__).'/bin/clean-install.ps1';if(!is_file($script))throw new RuntimeException('Clean installer missing.');$source=(string)file_get_contents($script);foreach(['^torneios_test_','DROP DATABASE IF EXISTS','finally']as$needle)if(!str_contains($source,$needle))throw new RuntimeException('Disposable database safeguard missing.');echo "CLEAN_INSTALL_E2E_OK\n";
