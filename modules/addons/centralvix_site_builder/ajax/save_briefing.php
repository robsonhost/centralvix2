<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

$isClient = !empty($_SESSION['uid']) && empty($_SESSION['adminid']);
$isAdmin = !empty($_SESSION['adminid']);

if ($isClient) {
    if (!CentralVixSiteModule::validateCsrf($_POST['csrf_token'] ?? '', 'client')) {
        CentralVixSiteModule::json(['success' => false, 'message' => 'Token CSRF inválido.'], 403);
    }
    $clientId = (int) $_SESSION['uid'];
    $projectId = (int) ($_POST['project_id'] ?? 0);
    CentralVixSiteModule::json(CentralVixSiteBriefingService::save($projectId, $_POST, $clientId));
}

if ($isAdmin) {
    if (!CentralVixSiteModule::validateCsrf($_POST['csrf_token'] ?? '', 'admin')) {
        CentralVixSiteModule::json(['success' => false, 'message' => 'Token CSRF inválido.'], 403);
    }
    $projectId = (int) ($_POST['project_id'] ?? 0);
    CentralVixSiteModule::json(CentralVixSiteBriefingService::save($projectId, $_POST, null));
}

CentralVixSiteModule::json(['success' => false, 'message' => 'Acesso negado.'], 403);
