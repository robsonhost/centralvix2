<?php
/**
 * Página pública de status — CentralVix
 */
$root = realpath(dirname(__FILE__, 4));
if (!$root || !is_file($root . '/init.php')) {
    http_response_code(500);
    die('Sistema indisponível.');
}
require_once $root . '/init.php';
require_once dirname(__DIR__) . '/lib/Bootstrap.php';
CentralVixStatusModule::autoload();

$overall = CentralVixStatusService::getOverallStatus();
$message = CentralVixStatusService::getOverallMessage();
$grouped = CentralVixStatusService::servicesByCategory(true);
$incidents = CentralVixStatusIncidentService::recentPublic(5);
$maintenance = CentralVixStatusMaintenanceService::upcomingPublic(5);
$statuses = CentralVixStatusModule::getStatuses();
$categories = CentralVixStatusModule::getCategories();
$assetBase = '../assets';
$ver = CentralVixStatusModule::VERSION;

include dirname(__DIR__) . '/templates/status_public.php';
