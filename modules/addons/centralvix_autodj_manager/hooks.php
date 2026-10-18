<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

require_once __DIR__ . '/lib/Security.php';
CentralVixAutoDJModule::autoload();

add_hook('AdminAreaPage', 1, function () {
    if (empty($_GET['cvix_autodj_ajax'])) {
        return;
    }
    if (basename($_SERVER['SCRIPT_NAME'] ?? '') !== 'addonmodules.php') {
        return;
    }
    if (($_GET['module'] ?? '') !== 'centralvix_autodj_manager') {
        return;
    }
    while (ob_get_level() > 0) {
        @ob_end_clean();
    }
    $action = preg_replace('/[^a-z_]/', '', $_GET['cvix_autodj_ajax']);
    $f = __DIR__ . '/ajax/' . $action . '.php';
    if (!is_file($f)) {
        CentralVixAutoDJModule::json(['success' => false, 'message' => 'Endpoint não encontrado'], 404);
    }
    require $f;
    exit;
});
