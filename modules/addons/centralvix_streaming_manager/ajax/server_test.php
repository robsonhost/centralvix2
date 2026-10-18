<?php
if (!defined('WHMCS')) { die('This file cannot be accessed directly'); }
CentralVixStreamingModule::requireAdmin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    CentralVixStreamingModule::json(['success' => false, 'message' => 'POST required'], 405);
}
if (!CentralVixStreamingModule::validateCsrf($_POST['token'] ?? '')) {
    CentralVixStreamingModule::json(['success' => false, 'message' => 'CSRF inválido'], 403);
}
$serverId = (int) ($_POST['server_id'] ?? 0);
$server = CentralVixStreamingService::getServer($serverId);
if (!$server) {
    CentralVixStreamingModule::json(['success' => false, 'message' => 'Servidor não encontrado']);
}
$provider = CentralVixStreamingServerFactory::provider($server);
if (!$provider) {
    CentralVixStreamingModule::json(['success' => false, 'message' => 'Provider inválido']);
}
$result = $provider->testConnection();
CentralVixStreamingLogger::log('server_test', $result['success'] ? 'success' : 'failed', 0, $result['message']);
CentralVixStreamingModule::json($result);
