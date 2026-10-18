<?php
if (!defined('WHMCS')) { die('This file cannot be accessed directly'); }
$isAdmin = !empty($_SESSION['adminid']);
$isClient = !empty($_SESSION['uid']);
if (!$isAdmin && !$isClient) {
    CentralVixStreamingModule::json(['success' => false, 'message' => 'Acesso negado'], 403);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    CentralVixStreamingModule::json(['success' => false, 'message' => 'POST required'], 405);
}
$area = $isAdmin ? 'admin' : 'client';
if (!CentralVixStreamingModule::validateCsrf($_POST['token'] ?? '', $area)) {
    CentralVixStreamingModule::json(['success' => false, 'message' => 'CSRF inválido'], 403);
}
$accountId = (int) ($_POST['account_id'] ?? 0);
$action = preg_replace('/[^a-z_]/', '', $_POST['action'] ?? '');
$clientId = $isClient ? (int) $_SESSION['uid'] : 0;
$account = CentralVixStreamingService::getAccount($accountId, $clientId);
if (!$account) {
    CentralVixStreamingModule::json(['success' => false, 'message' => 'Conta não encontrada']);
}
if ($isClient) {
    $allowed = ['change_password', 'start', 'stop', 'restart'];
    if (!in_array($action, $allowed, true)) {
        CentralVixStreamingModule::json(['success' => false, 'message' => 'Ação não permitida']);
    }
    $provider = CentralVixStreamingService::providerForAccount($account);
    if ($provider) {
        $caps = $provider->getCapabilities();
        if (empty($caps[$action === 'change_password' ? 'change_password' : $action])) {
            CentralVixStreamingModule::json(['success' => false, 'message' => 'Ação não suportada pelo servidor']);
        }
    }
}
$params = [];
if ($action === 'change_password') {
    $params['password'] = $_POST['password'] ?? bin2hex(random_bytes(8));
}
if ($action === 'change_bitrate') {
    $params['bitrate'] = (int) ($_POST['bitrate'] ?? 0);
}
if ($action === 'change_listeners') {
    $params['max_listeners'] = (int) ($_POST['max_listeners'] ?? 0);
}
$result = CentralVixStreamingProvisioningService::runAccountAction($accountId, $action, $params);
CentralVixStreamingModule::json($result);
