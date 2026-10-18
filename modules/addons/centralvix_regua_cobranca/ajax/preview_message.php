<?php
if (!defined('WHMCS')) { die('This file cannot be accessed directly'); }
CentralVixReguaModule::requireAdmin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    CentralVixReguaModule::json(['success' => false, 'message' => 'POST required'], 405);
}
if (!CentralVixReguaModule::validateCsrf($_POST['token'] ?? '')) {
    CentralVixReguaModule::json(['success' => false, 'message' => 'CSRF inválido'], 403);
}
$data = [
    'subject' => $_POST['subject'] ?? '',
    'body_email' => $_POST['body_email'] ?? '',
    'body_whatsapp' => $_POST['body_whatsapp'] ?? '',
];
$invoiceId = (int) ($_POST['invoice_id'] ?? 0);
$preview = CentralVixReguaTemplateService::preview($data, $invoiceId);
CentralVixReguaModule::json(['success' => true, 'preview' => $preview]);
