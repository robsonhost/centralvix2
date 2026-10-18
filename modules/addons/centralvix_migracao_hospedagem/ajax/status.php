<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

if (!CentralVixMigSecurity::isAdminSession()) {
    CentralVixMigModule::json(['success' => false, 'message' => 'Acesso admin necessário'], 403);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    CentralVixMigModule::json(['success' => false, 'message' => 'Método não permitido'], 405);
}

if (!CentralVixMigModule::validateCsrf($_POST['csrf_token'] ?? '', 'admin')) {
    CentralVixMigModule::json(['success' => false, 'message' => 'CSRF inválido'], 403);
}

$requestId = (int) ($_POST['request_id'] ?? 0);
$newStatus = $_POST['new_status'] ?? '';
$clientMessage = $_POST['client_message'] ?? '';

$result = CentralVixMigMigrationService::changeStatus(
    $requestId,
    $newStatus,
    'admin',
    CentralVixMigSecurity::adminId(),
    $clientMessage
);

CentralVixMigModule::json($result, $result['success'] ? 200 : 400);
