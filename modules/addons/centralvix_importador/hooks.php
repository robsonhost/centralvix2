<?php
/**
 * Intercepta requisições AJAX dentro do diretório admin do WHMCS (compatível com admin renomeado).
 */

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

add_hook('AdminAreaPage', 1, function () {
    if (empty($_GET['cvix_ajax'])) {
        return;
    }

    if (basename($_SERVER['SCRIPT_NAME'] ?? '') !== 'addonmodules.php') {
        return;
    }

    if (($_GET['module'] ?? '') !== 'centralvix_importador') {
        return;
    }

    require_once __DIR__ . '/lib/ModuleHelper.php';
    CentralVixImportadorHelper::autoloadLib();

    while (ob_get_level() > 0) {
        @ob_end_clean();
    }

    $endpoint = preg_replace('/[^a-z_]/', '', $_GET['cvix_ajax']);
    $allowed = ['ping', 'upload', 'preview', 'import', 'report'];

    if (!in_array($endpoint, $allowed, true)) {
        CentralVixImportadorHelper::jsonResponse([
            'success' => false,
            'message' => 'Endpoint AJAX inválido.',
        ], 404);
    }

    $ajaxFile = __DIR__ . '/ajax/' . $endpoint . '.php';
    if (!is_file($ajaxFile)) {
        CentralVixImportadorHelper::jsonResponse([
            'success' => false,
            'message' => 'Endpoint não encontrado.',
        ], 404);
    }

    try {
        require $ajaxFile;
    } catch (Throwable $e) {
        CentralVixImportadorHelper::jsonResponse([
            'success' => false,
            'message' => 'Erro interno: ' . $e->getMessage(),
        ], 500);
    }

    CentralVixImportadorHelper::jsonResponse([
        'success' => false,
        'message' => 'Resposta vazia do servidor.',
    ], 500);
});
