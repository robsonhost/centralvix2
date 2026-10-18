<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

if (empty($_SESSION['uid'])) {
    CentralVixSiteModule::json(['success' => false, 'message' => 'Login necessário.'], 403);
}
if (!CentralVixSiteModule::validateCsrf($_POST['csrf_token'] ?? '', 'client')) {
    CentralVixSiteModule::json(['success' => false, 'message' => 'Token CSRF inválido.'], 403);
}

$clientId = (int) $_SESSION['uid'];
$projectId = (int) ($_POST['project_id'] ?? 0);
$action = $_POST['approval_action'] ?? 'approve';

if ($action === 'request_change') {
    CentralVixSiteModule::json(CentralVixSiteApprovalService::requestChanges(
        $projectId,
        $clientId,
        $_POST['stage'] ?? '',
        $_POST['message'] ?? ''
    ));
}

CentralVixSiteModule::json(CentralVixSiteApprovalService::approve(
    $projectId,
    $clientId,
    $_POST['stage'] ?? '',
    $_POST['message'] ?? ''
));
