<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

require_once __DIR__ . '/lib/Security.php';
CentralVixSignModule::autoload();

add_hook('AdminAreaPage', 1, function () {
    if (empty($_GET['cvix_sign_ajax']) && empty($_GET['cvix_sign_dl'])) {
        return;
    }
    if (basename($_SERVER['SCRIPT_NAME'] ?? '') !== 'addonmodules.php') {
        return;
    }
    if (($_GET['module'] ?? '') !== 'centralvix_assinatura_digital') {
        return;
    }
    while (ob_get_level() > 0) {
        @ob_end_clean();
    }
    if (!empty($_GET['cvix_sign_dl'])) {
        CentralVixSignDocumentService::serveFile((int) $_GET['cvix_sign_dl'], $_GET['file'] ?? 'original', null);
    }
    $f = __DIR__ . '/ajax/' . preg_replace('/[^a-z_]/', '', $_GET['cvix_sign_ajax']) . '.php';
    if (is_file($f)) {
        require $f;
    } else {
        CentralVixSignModule::json(['success' => false], 404);
    }
    exit;
});

add_hook('ClientAreaPage', 1, function () {
    if (empty($_GET['cvix_sign_ajax']) && empty($_GET['cvix_sign_dl'])) {
        return;
    }
    if (($_GET['m'] ?? '') !== 'centralvix_assinatura_digital') {
        return;
    }
    while (ob_get_level() > 0) {
        @ob_end_clean();
    }
    if (!empty($_GET['cvix_sign_dl'])) {
        if (empty($_SESSION['uid'])) {
            http_response_code(403);
            exit;
        }
        CentralVixSignDocumentService::serveFile((int) $_GET['cvix_sign_dl'], $_GET['file'] ?? 'original', (int) $_SESSION['uid']);
    }
    $f = __DIR__ . '/ajax/' . preg_replace('/[^a-z_]/', '', $_GET['cvix_sign_ajax']) . '.php';
    if (is_file($f)) {
        require $f;
    } else {
        CentralVixSignModule::json(['success' => false], 404);
    }
    exit;
});

add_hook('AfterCronJob', 1, function () {
    require_once __DIR__ . '/lib/Security.php';
    CentralVixSignModule::autoload();
    CentralVixSignDocumentService::checkExpired();
});
