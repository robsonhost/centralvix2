<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    CentralVixMigModule::json(['success' => false, 'message' => 'Método não permitido'], 405);
}

$requestId = (int) ($_POST['request_id'] ?? 0);
$isAdmin = CentralVixMigSecurity::isAdminSession();
$clientId = (int) ($_SESSION['uid'] ?? 0);

if ($isAdmin) {
    if (!CentralVixMigModule::validateCsrf($_POST['csrf_token'] ?? '', 'admin')) {
        CentralVixMigModule::json(['success' => false, 'message' => 'CSRF inválido'], 403);
    }
    $request = CentralVixMigMigrationService::get($requestId);
} else {
    if (!$clientId) {
        CentralVixMigModule::json(['success' => false, 'message' => 'Login necessário'], 401);
    }
    if (!CentralVixMigModule::validateCsrf($_POST['csrf_token'] ?? '', 'client')) {
        CentralVixMigModule::json(['success' => false, 'message' => 'CSRF inválido'], 403);
    }
    $request = CentralVixMigMigrationService::getForClient($requestId, $clientId);
}

if (!$request) {
    CentralVixMigModule::json(['success' => false, 'message' => 'Acesso negado'], 403);
}

$fileType = $_POST['file_type'] ?? 'doc';
$result = CentralVixMigFileService::handleUpload($requestId, $_FILES['file'] ?? [], $clientId ?: (int) $request->client_id, $fileType);
CentralVixMigModule::json($result, $result['success'] ? 200 : 400);
