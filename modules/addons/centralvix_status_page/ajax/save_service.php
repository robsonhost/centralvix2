<?php
if (!defined('WHMCS')) { die('This file cannot be accessed directly'); }
CentralVixStatusModule::requireAdmin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    CentralVixStatusModule::json(['success' => false, 'message' => 'POST required'], 405);
}
if (!CentralVixStatusModule::validateCsrf($_POST['token'] ?? '')) {
    CentralVixStatusModule::json(['success' => false, 'message' => 'CSRF inválido'], 403);
}
$id = (int) ($_POST['id'] ?? 0);
$id = CentralVixStatusService::saveService($_POST, $id);
CentralVixStatusModule::logAction($id ? 'update_service' : 'create_service', $id, 'success', $_POST['name'] ?? '');
CentralVixStatusModule::json(['success' => true, 'id' => $id, 'message' => 'Serviço salvo.']);
