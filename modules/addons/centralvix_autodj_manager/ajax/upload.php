<?php
if (!defined('WHMCS')) { die('This file cannot be accessed directly'); }
$isAdmin = !empty($_SESSION['adminid']);
$clientId = $isAdmin ? (int) ($_POST['client_id'] ?? 0) : (int) ($_SESSION['uid'] ?? 0);
if (!$isAdmin) {
    CentralVixAutoDJModule::requireClient();
} else {
    CentralVixAutoDJModule::requireAdmin();
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    CentralVixAutoDJModule::json(['success' => false, 'message' => 'POST required'], 405);
}
if (!CentralVixAutoDJModule::validateCsrf($_POST['token'] ?? '', $isAdmin ? 'admin' : 'client')) {
    CentralVixAutoDJModule::json(['success' => false, 'message' => 'CSRF inválido'], 403);
}
$accountId = (int) ($_POST['account_id'] ?? 0);
if (!$clientId && $isAdmin) {
    $acc = CentralVixAutoDJService::getAccount($accountId);
    $clientId = $acc ? (int) $acc->client_id : 0;
}
if (empty($_FILES['file'])) {
    CentralVixAutoDJModule::json(['success' => false, 'message' => 'Nenhum arquivo enviado.']);
}
$result = CentralVixAutoDJStorageService::storeFile($accountId, $clientId, $_FILES['file'], $_POST['media_type'] ?? 'music');
CentralVixAutoDJLogger::log('upload', !empty($result['valid']) ? 'success' : 'failed', $accountId, $clientId, $result['message'] ?? '');
CentralVixAutoDJModule::json([
    'success' => !empty($result['valid']),
    'message' => $result['message'] ?? '',
    'media_id' => $result['media_id'] ?? 0,
]);
