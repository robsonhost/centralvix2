<?php
if (!defined('WHMCS')) { die('This file cannot be accessed directly'); }
CentralVixReguaModule::requireAdmin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    CentralVixReguaModule::json(['success' => false, 'message' => 'POST required'], 405);
}
if (!CentralVixReguaModule::validateCsrf($_POST['token'] ?? '')) {
    CentralVixReguaModule::json(['success' => false, 'message' => 'CSRF inválido'], 403);
}
$id = (int) ($_POST['id'] ?? 0);
if (!$id || !CentralVixReguaBillingRuleService::toggle($id)) {
    CentralVixReguaModule::json(['success' => false, 'message' => 'Régua não encontrada']);
}
CentralVixReguaModule::json(['success' => true, 'message' => 'Status alterado.']);
