<?php
if (!defined('WHMCS')) { die('This file cannot be accessed directly'); }
CentralVixReguaModule::requireAdmin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    CentralVixReguaModule::json(['success' => false, 'message' => 'POST required'], 405);
}
if (!CentralVixReguaModule::validateCsrf($_POST['token'] ?? '')) {
    CentralVixReguaModule::json(['success' => false, 'message' => 'CSRF inválido'], 403);
}
$phone = $_POST['phone'] ?? '';
$message = $_POST['message'] ?? '';
$result = CentralVixReguaWhatsAppService::test($phone, $message);
CentralVixReguaModule::json([
    'success' => !empty($result['success']),
    'message' => $result['message'] ?? 'Erro desconhecido',
    'response' => $result['response'] ?? '',
]);
