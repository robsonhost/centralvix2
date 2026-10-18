<?php
/**
 * Cron manual — CentralVix Régua de Cobrança
 * php /path/to/whmcs/modules/addons/centralvix_regua_cobranca/cron.php
 */

$root = realpath(dirname(__FILE__, 4));
if (!$root || !is_file($root . '/init.php')) {
    fwrite(STDERR, "init.php não encontrado.\n");
    exit(1);
}

require_once $root . '/init.php';
require_once __DIR__ . '/lib/Security.php';
CentralVixReguaModule::autoload();

$result = CentralVixReguaExecutionService::run(true);
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
