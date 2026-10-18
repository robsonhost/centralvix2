<?php
if (!defined('WHMCS')) { die('This file cannot be accessed directly'); }
CentralVixStreamingModule::requireAdmin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    CentralVixStreamingModule::json(['success' => false, 'message' => 'POST required'], 405);
}
if (!CentralVixStreamingModule::validateCsrf($_POST['token'] ?? '')) {
    CentralVixStreamingModule::json(['success' => false, 'message' => 'CSRF inválido'], 403);
}
$id = (int) ($_POST['id'] ?? 0);
$result = CentralVixStreamingService::saveServer($_POST, $id);
if (empty($result['success'])) {
    CentralVixStreamingModule::json($result);
}
CentralVixStreamingLogger::log('save_server', 'success', 0, 'Server #' . $result['id']);
CentralVixStreamingModule::json(['success' => true, 'id' => $result['id'], 'message' => 'Servidor salvo.']);
