<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    CentralVixMigModule::json(['success' => false, 'message' => 'Método não permitido'], 405);
}

$clientId = (int) ($_SESSION['uid'] ?? 0);
if (!$clientId) {
    CentralVixMigModule::json(['success' => false, 'message' => 'Login necessário'], 401);
}

if (!CentralVixMigModule::validateCsrf($_POST['csrf_token'] ?? '', 'client')) {
    CentralVixMigModule::json(['success' => false, 'message' => 'CSRF inválido'], 403);
}

$result = CentralVixMigMigrationService::create($_POST, $clientId);
CentralVixMigModule::json($result, $result['success'] ? 200 : 400);
