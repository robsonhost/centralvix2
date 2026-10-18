<?php
/**
 * Detalhe público de incidente
 */
$root = realpath(dirname(__FILE__, 4));
if (!$root || !is_file($root . '/init.php')) {
    http_response_code(500);
    die('Sistema indisponível.');
}
require_once $root . '/init.php';
require_once dirname(__DIR__) . '/lib/Bootstrap.php';
CentralVixStatusModule::autoload();

$id = (int)($_GET['id'] ?? 0);
$incident = CentralVixStatusIncidentService::getIncident($id);
if (!$incident || !$incident->is_public) {
    http_response_code(404);
    die('Incidente não encontrado.');
}
$updates = CentralVixStatusIncidentService::getUpdates($id);
$statuses = CentralVixStatusModule::getStatuses();
$assetBase = '../assets';
$ver = CentralVixStatusModule::VERSION;

include dirname(__DIR__) . '/templates/incident_public.php';
