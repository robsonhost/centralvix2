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
if (!empty($_POST['client_ids_raw'])) {
    $parts = array_filter(array_map('intval', explode(',', $_POST['client_ids_raw'])));
    $_POST['client_ids'] = $parts;
}
$id = CentralVixReguaBillingRuleService::save($_POST, $id);
CentralVixReguaModule::json(['success' => true, 'id' => $id, 'message' => 'Régua salva com sucesso.']);
