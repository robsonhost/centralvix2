<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

CentralVixPlayerModule::requireClient();

if (!CentralVixPlayerModule::validateCsrf($_POST['csrf_token'] ?? '', 'client')) {
    CentralVixPlayerModule::json(['success' => false, 'message' => 'Token CSRF inválido.'], 403);
}

$clientId = (int) $_SESSION['uid'];
$action = preg_replace('/[^a-z_]/', '', $_POST['action'] ?? 'save');

if ($action === 'delete') {
    $id = (int) ($_POST['player_id'] ?? 0);
    CentralVixPlayerModule::json(CentralVixPlayerService::delete($id, $clientId));
}

if ($action === 'duplicate') {
    $id = (int) ($_POST['player_id'] ?? 0);
    CentralVixPlayerModule::json(CentralVixPlayerService::duplicate($id, $clientId));
}

$playerId = (int) ($_POST['player_id'] ?? 0);
$existing = $playerId ? CentralVixPlayerService::get($playerId, $clientId) : null;

$logo = CentralVixPlayerService::handleUpload($clientId, 'logo', $existing->logo_path ?? '');
if (!$logo['success']) {
    CentralVixPlayerModule::json($logo);
}
$bg = CentralVixPlayerService::handleUpload($clientId, 'background', $existing->bg_image_path ?? '');
if (!$bg['success']) {
    CentralVixPlayerModule::json($bg);
}

$data = $_POST;
$data['socials'] = $_POST['socials'] ?? [];
$result = CentralVixPlayerService::saveFromRequest($clientId, $data, $playerId);

if (!$result['success']) {
    CentralVixPlayerModule::json($result);
}

$saveId = $result['id'];
$updates = [];
if ($logo['path']) {
    $updates['logo_path'] = $logo['path'];
}
if ($bg['path']) {
    $updates['bg_image_path'] = $bg['path'];
}
if ($updates) {
    WHMCS\Database\Capsule::table('mod_centralvix_players')->where('id', $saveId)->update($updates);
    $result['player'] = CentralVixPlayerService::playerToArray(CentralVixPlayerService::get($saveId, $clientId));
}

CentralVixPlayerModule::json($result);
