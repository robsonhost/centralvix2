<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

require_once __DIR__ . '/lib/Security.php';
CentralVixSiteModule::autoload();

add_hook('AdminAreaPage', 1, function () {
    if (empty($_GET['cvix_site_ajax']) && empty($_GET['cvix_site_download'])) {
        return;
    }
    if (basename($_SERVER['SCRIPT_NAME'] ?? '') !== 'addonmodules.php') {
        return;
    }
    if (($_GET['module'] ?? '') !== 'centralvix_site_builder') {
        return;
    }
    while (ob_get_level() > 0) {
        @ob_end_clean();
    }
    if (!empty($_GET['cvix_site_download'])) {
        CentralVixSiteFileService::serveDownload((int) $_GET['cvix_site_download'], null, true);
    }
    $action = preg_replace('/[^a-z_]/', '', $_GET['cvix_site_ajax']);
    $f = __DIR__ . '/ajax/' . $action . '.php';
    if (!is_file($f)) {
        CentralVixSiteModule::json(['success' => false, 'message' => 'Endpoint não encontrado'], 404);
    }
    require $f;
    exit;
});

add_hook('ClientAreaPage', 1, function () {
    if (empty($_GET['cvix_site_ajax']) && empty($_GET['cvix_site_download'])) {
        return;
    }
    if (($_GET['m'] ?? '') !== 'centralvix_site_builder') {
        return;
    }
    while (ob_get_level() > 0) {
        @ob_end_clean();
    }
    if (!empty($_GET['cvix_site_download'])) {
        if (empty($_SESSION['uid'])) {
            http_response_code(403);
            exit('Login necessário.');
        }
        CentralVixSiteFileService::serveDownload((int) $_GET['cvix_site_download'], (int) $_SESSION['uid'], false);
    }
    $action = preg_replace('/[^a-z_]/', '', $_GET['cvix_site_ajax']);
    $f = __DIR__ . '/ajax/' . $action . '.php';
    if (!is_file($f)) {
        CentralVixSiteModule::json(['success' => false, 'message' => 'Endpoint não encontrado'], 404);
    }
    require $f;
    exit;
});

add_hook('AfterModuleCreate', 1, function ($vars) {
    if (CentralVixSiteModule::setting('auto_create_on_order', '') !== 'on') {
        return;
    }
    require_once __DIR__ . '/lib/Security.php';
    CentralVixSiteModule::autoload();
    $serviceId = (int) ($vars['params']['serviceid'] ?? 0);
    if (!$serviceId) {
        return;
    }
    $hosting = WHMCS\Database\Capsule::table('tblhosting')->where('id', $serviceId)->first();
    if (!$hosting) {
        return;
    }
    $product = WHMCS\Database\Capsule::table('tblproducts')->where('id', $hosting->packageid)->first();
    $exists = WHMCS\Database\Capsule::table('mod_centralvix_site_projects')->where('service_id', $serviceId)->exists();
    if ($exists) {
        return;
    }
    CentralVixSiteProjectService::create([
        'client_id' => (int) $hosting->userid,
        'service_id' => $serviceId,
        'product_name' => $product->name ?? '',
        'project_name' => ($product->name ?? 'Site') . ' — #' . $serviceId,
        'domain' => $hosting->domain ?? '',
        'plan' => $product->name ?? '',
        'start_date' => date('Y-m-d'),
    ]);
});
