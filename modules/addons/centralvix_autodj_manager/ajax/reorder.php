<?php
if (!defined('WHMCS')) { die('This file cannot be accessed directly'); }
CentralVixAutoDJModule::requireClient();
if (!CentralVixAutoDJModule::validateCsrf($_POST['token'] ?? '', 'client')) {
    CentralVixAutoDJModule::json(['success' => false, 'message' => 'CSRF inválido'], 403);
}
$clientId = (int) $_SESSION['uid'];
$playlistId = (int) ($_POST['playlist_id'] ?? 0);
$order = isset($_POST['order']) ? array_map('intval', (array) $_POST['order']) : [];
$result = CentralVixAutoDJPlaylistService::reorder($playlistId, $order, $clientId);
CentralVixAutoDJModule::json($result);
