<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

require_once __DIR__ . '/lib/Security.php';
CentralVixStreamingModule::autoload();

add_hook('AdminAreaPage', 1, function () {
    if (empty($_GET['cvix_stream_ajax'])) {
        return;
    }
    if (basename($_SERVER['SCRIPT_NAME'] ?? '') !== 'addonmodules.php') {
        return;
    }
    if (($_GET['module'] ?? '') !== 'centralvix_streaming_manager') {
        return;
    }
    while (ob_get_level() > 0) {
        @ob_end_clean();
    }
    $action = preg_replace('/[^a-z_]/', '', $_GET['cvix_stream_ajax']);
    $file = __DIR__ . '/ajax/' . $action . '.php';
    if (!is_file($file)) {
        CentralVixStreamingModule::json(['success' => false, 'message' => 'Endpoint não encontrado'], 404);
    }
    require $file;
    exit;
});

add_hook('AfterCronJob', 1, function () {
    require_once __DIR__ . '/lib/Security.php';
    CentralVixStreamingModule::autoload();
    $last = (int) CentralVixStreamingModule::dbSetting('last_stats_cron', '0');
    $interval = max(5, (int) CentralVixStreamingModule::setting('stats_interval', 15)) * 60;
    if (time() - $last < $interval) {
        return;
    }
    CentralVixStreamingModule::setDbSetting('last_stats_cron', (string) time());
    CentralVixStreamingStatisticsService::collectAll();
});

function centralvix_streaming_should_provision($productId)
{
    $csv = CentralVixStreamingModule::setting('product_ids', '');
    if ($csv === '') {
        return true;
    }
    $ids = array_map('intval', array_filter(explode(',', $csv)));
    return in_array((int) $productId, $ids, true);
}

add_hook('AfterModuleCreate', 1, function ($vars) {
    require_once __DIR__ . '/lib/Security.php';
    CentralVixStreamingModule::autoload();
    $serviceId = (int) ($vars['params']['serviceid'] ?? $vars['serviceid'] ?? 0);
    $productId = (int) ($vars['params']['pid'] ?? $vars['pid'] ?? $vars['params']['packageid'] ?? 0);
    if (!$serviceId || !centralvix_streaming_should_provision($productId)) {
        return;
    }
    CentralVixStreamingProvisioningService::onModuleCreate($serviceId);
});

add_hook('AfterModuleSuspend', 1, function ($vars) {
    require_once __DIR__ . '/lib/Security.php';
    CentralVixStreamingModule::autoload();
    $serviceId = (int) ($vars['params']['serviceid'] ?? $vars['serviceid'] ?? 0);
    if ($serviceId) {
        CentralVixStreamingProvisioningService::onModuleSuspend($serviceId);
    }
});

add_hook('AfterModuleUnsuspend', 1, function ($vars) {
    require_once __DIR__ . '/lib/Security.php';
    CentralVixStreamingModule::autoload();
    $serviceId = (int) ($vars['params']['serviceid'] ?? $vars['serviceid'] ?? 0);
    if ($serviceId) {
        CentralVixStreamingProvisioningService::onModuleUnsuspend($serviceId);
    }
});

add_hook('AfterModuleTerminate', 1, function ($vars) {
    require_once __DIR__ . '/lib/Security.php';
    CentralVixStreamingModule::autoload();
    $serviceId = (int) ($vars['params']['serviceid'] ?? $vars['serviceid'] ?? 0);
    if ($serviceId) {
        CentralVixStreamingProvisioningService::onModuleTerminate($serviceId);
    }
});
