<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

require_once __DIR__ . '/lib/Security.php';
CentralVixMigModule::autoload();

add_hook('AdminAreaPage', 1, function () {
    if (empty($_GET[CentralVixMigModule::AJAX_PARAM]) && empty($_GET[CentralVixMigModule::DOWNLOAD_PARAM])) {
        return;
    }
    if (basename($_SERVER['SCRIPT_NAME'] ?? '') !== 'addonmodules.php') {
        return;
    }
    if (($_GET['module'] ?? '') !== CentralVixMigModule::MODULE) {
        return;
    }
    while (ob_get_level() > 0) {
        @ob_end_clean();
    }
    if (!empty($_GET[CentralVixMigModule::DOWNLOAD_PARAM])) {
        CentralVixMigFileService::serveDownload((int) $_GET[CentralVixMigModule::DOWNLOAD_PARAM], null);
    }
    $action = preg_replace('/[^a-z_]/', '', $_GET[CentralVixMigModule::AJAX_PARAM]);
    $f = __DIR__ . '/ajax/' . $action . '.php';
    if (!is_file($f)) {
        CentralVixMigModule::json(['success' => false, 'message' => 'Endpoint não encontrado'], 404);
    }
    require $f;
    exit;
});

add_hook('ClientAreaPage', 1, function () {
    if (empty($_GET[CentralVixMigModule::AJAX_PARAM]) && empty($_GET[CentralVixMigModule::DOWNLOAD_PARAM])) {
        return;
    }
    if (($_GET['m'] ?? '') !== CentralVixMigModule::MODULE) {
        return;
    }
    while (ob_get_level() > 0) {
        @ob_end_clean();
    }
    if (!empty($_GET[CentralVixMigModule::DOWNLOAD_PARAM])) {
        if (empty($_SESSION['uid'])) {
            http_response_code(403);
            exit;
        }
        CentralVixMigFileService::serveDownload((int) $_GET[CentralVixMigModule::DOWNLOAD_PARAM], (int) $_SESSION['uid']);
    }
    $action = preg_replace('/[^a-z_]/', '', $_GET[CentralVixMigModule::AJAX_PARAM]);
    $f = __DIR__ . '/ajax/' . $action . '.php';
    if (!is_file($f)) {
        CentralVixMigModule::json(['success' => false, 'message' => 'Endpoint não encontrado'], 404);
    }
    require $f;
    exit;
});
