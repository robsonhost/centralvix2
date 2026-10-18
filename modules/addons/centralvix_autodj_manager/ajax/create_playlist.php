<?php
if (!defined('WHMCS')) { die('This file cannot be accessed directly'); }
$isAdmin = !empty($_SESSION['adminid']);
$clientId = $isAdmin ? (int) ($_POST['client_id'] ?? $_SESSION['uid'] ?? 0) : (int) ($_SESSION['uid'] ?? 0);
if (!$isAdmin) {
    CentralVixAutoDJModule::requireClient();
} else {
    CentralVixAutoDJModule::requireAdmin();
}
if (!CentralVixAutoDJModule::validateCsrf($_POST['token'] ?? '', $isAdmin ? 'admin' : 'client')) {
    CentralVixAutoDJModule::json(['success' => false, 'message' => 'CSRF inválido'], 403);
}
if (!$clientId) {
    CentralVixAutoDJModule::json(['success' => false, 'message' => 'Cliente inválido']);
}
$_POST['media_ids'] = isset($_POST['media_ids']) ? (array) $_POST['media_ids'] : [];
$result = CentralVixAutoDJPlaylistService::save($_POST, $clientId, (int) ($_POST['id'] ?? 0));
CentralVixAutoDJLogger::log('save_playlist', $result['success'] ? 'success' : 'failed', (int) ($_POST['account_id'] ?? 0), $clientId);
CentralVixAutoDJModule::json($result);
