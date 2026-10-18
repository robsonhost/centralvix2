<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

require_once __DIR__ . '/lib/Security.php';
CentralVixPmModule::autoload();

add_hook('AdminAreaPage', 1, function () {
    if (empty($_GET['cvix_pm_ajax']) && empty($_GET['cvix_pm_download'])) {
        return;
    }
    if (basename($_SERVER['SCRIPT_NAME'] ?? '') !== 'addonmodules.php') {
        return;
    }
    if (($_GET['module'] ?? '') !== 'centralvix_project_manager') {
        return;
    }
    while (ob_get_level() > 0) {
        @ob_end_clean();
    }
    if (!empty($_GET['cvix_pm_download'])) {
        CentralVixPmFileService::serveDownload((int) $_GET['cvix_pm_download'], null);
    }
    $action = preg_replace('/[^a-z_]/', '', $_GET['cvix_pm_ajax']);
    $f = __DIR__ . '/ajax/' . $action . '.php';
    if (!is_file($f)) {
        CentralVixPmModule::json(['success' => false, 'message' => 'Endpoint não encontrado'], 404);
    }
    require $f;
    exit;
});

add_hook('ClientAreaPage', 1, function () {
    if (empty($_GET['cvix_pm_ajax']) && empty($_GET['cvix_pm_download'])) {
        return;
    }
    if (($_GET['m'] ?? '') !== 'centralvix_project_manager') {
        return;
    }
    while (ob_get_level() > 0) {
        @ob_end_clean();
    }
    if (!empty($_GET['cvix_pm_download'])) {
        if (empty($_SESSION['uid'])) {
            http_response_code(403);
            exit;
        }
        CentralVixPmFileService::serveDownload((int) $_GET['cvix_pm_download'], (int) $_SESSION['uid']);
    }
    $action = preg_replace('/[^a-z_]/', '', $_GET['cvix_pm_ajax']);
    $f = __DIR__ . '/ajax/' . $action . '.php';
    if (!is_file($f)) {
        CentralVixPmModule::json(['success' => false, 'message' => 'Endpoint não encontrado'], 404);
    }
    require $f;
    exit;
});
