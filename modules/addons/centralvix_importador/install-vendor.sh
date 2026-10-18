#!/bin/bash
# Reinstala PhpSpreadsheet compatível com PHP 8.2 (versão 2.x — sem bloqueio de segurança do 1.29)
cd "$(dirname "$0")"
rm -rf vendor composer.lock
composer install --no-dev --no-interaction
echo "Concluído. Teste no admin: addonmodules.php?module=centralvix_importador&cvix_ajax=ping"
