<?php
if (!defined('WHMCS')) { die('This file cannot be accessed directly'); }
$isAdmin = !empty($_SESSION['adminid']);
$isClient = !empty($_SESSION['uid']);
if (!$isAdmin && !$isClient) {
    CentralVixStreamingModule::json(['success' => false, 'message' => 'Acesso negado'], 403);
}
$accountId = (int) ($_GET['account_id'] ?? $_POST['account_id'] ?? 0);
$clientId = $isClient ? (int) $_SESSION['uid'] : 0;
$account = CentralVixStreamingService::getAccount($accountId, $clientId);
if (!$account) {
    CentralVixStreamingModule::json(['success' => false, 'message' => 'Conta não encontrada']);
}
$live = CentralVixStreamingStatisticsService::collectForAccount($accountId);
$summary = CentralVixStreamingStatisticsService::summary($accountId, 7);
$history = CentralVixStreamingStatisticsService::history($accountId, 24);
CentralVixStreamingModule::json([
    'success' => true,
    'live' => $live,
    'summary' => $summary,
    'history' => $history,
    'stream_url' => CentralVixStreamingService::streamUrl($account),
]);
