<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

if (empty($_SESSION['adminid'])) {
    CentralVixSiteModule::json(['success' => false, 'message' => 'Acesso negado.'], 403);
}
if (!CentralVixSiteModule::validateCsrf($_POST['csrf_token'] ?? '', 'admin')) {
    CentralVixSiteModule::json(['success' => false, 'message' => 'Token CSRF inválido.'], 403);
}

$projectId = (int) ($_POST['project_id'] ?? 0);
$status = preg_replace('/[^a-z_]/', '', $_POST['status'] ?? '');
$note = $_POST['note'] ?? '';

CentralVixSiteModule::json(CentralVixSiteProjectService::changeStatus(
    $projectId,
    $status,
    'admin',
    (int) $_SESSION['adminid'],
    CentralVixSiteModule::adminName($_SESSION['adminid']),
    $note
));
