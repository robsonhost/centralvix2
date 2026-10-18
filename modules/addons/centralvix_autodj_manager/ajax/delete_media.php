<?php
if (!defined('WHMCS')) { die('This file cannot be accessed directly'); }
$isAdmin = !empty($_SESSION['adminid']);
$clientId = $isAdmin ? (int) ($_POST['client_id'] ?? 0) : (int) ($_SESSION['uid'] ?? 0);
if (!$isAdmin) {
    CentralVixAutoDJModule::requireClient();
} else {
    CentralVixAutoDJModule::requireAdmin();
}
if (!CentralVixAutoDJModule::validateCsrf($_POST['token'] ?? '', $isAdmin ? 'admin' : 'client')) {
    CentralVixAutoDJModule::json(['success' => false, 'message' => 'CSRF inválido'], 403);
}
$mediaId = (int) ($_POST['media_id'] ?? 0);
if (!$clientId && !$isAdmin) {
    CentralVixAutoDJModule::json(['success' => false, 'message' => 'Cliente inválido']);
}
$media = CentralVixAutoDJMediaLibrary::getMedia($mediaId, $clientId ?: (int) ($_SESSION['uid'] ?? 0), $isAdmin);
if (!$media) {
    CentralVixAutoDJModule::json(['success' => false, 'message' => 'Arquivo não encontrado.']);
}
if (!$isAdmin && (int) $media->client_id !== (int) $_SESSION['uid']) {
    CentralVixAutoDJModule::json(['success' => false, 'message' => 'Acesso negado.'], 403);
}
$result = CentralVixAutoDJStorageService::deleteFile($mediaId, (int) $media->client_id);
CentralVixAutoDJLogger::log('delete_media', $result['success'] ? 'success' : 'failed', $media->account_id, $media->client_id);
CentralVixAutoDJModule::json($result);
