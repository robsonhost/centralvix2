<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

$isClient = !empty($_SESSION['uid']) && empty($_SESSION['adminid']);
$isAdmin = !empty($_SESSION['adminid']);
$area = $isClient ? 'client' : 'admin';

if (!$isClient && !$isAdmin) {
    CentralVixSiteModule::json(['success' => false, 'message' => 'Acesso negado.'], 403);
}
if (!CentralVixSiteModule::validateCsrf($_POST['csrf_token'] ?? '', $area)) {
    CentralVixSiteModule::json(['success' => false, 'message' => 'Token CSRF inválido.'], 403);
}

$projectId = (int) ($_POST['project_id'] ?? 0);
$fileId = (int) ($_POST['file_id'] ?? 0);
$message = $_POST['message'] ?? '';

if ($isClient) {
    $clientId = (int) $_SESSION['uid'];
    CentralVixSiteModule::json(CentralVixSiteFileService::addComment(
        $projectId,
        $fileId ?: null,
        'client',
        $clientId,
        CentralVixSiteModule::clientName($clientId),
        $message,
        $clientId
    ));
}

CentralVixSiteModule::json(CentralVixSiteFileService::addComment(
    $projectId,
    $fileId ?: null,
    'admin',
    (int) ($_SESSION['adminid'] ?? 0),
    CentralVixSiteModule::adminName($_SESSION['adminid'] ?? 0),
    $message,
    null
));
