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
    CentralVixSiteModule::json(CentralVixSiteFileService::upload(
        (int) ($_POST['project_id'] ?? 0),
        (int) $_SESSION['uid'],
        'client',
        (int) $_SESSION['uid'],
        preg_replace('/[^a-z_]/', '', $_POST['category'] ?? 'documents')
    ));
}

if ($isAdmin) {
    if (!CentralVixSiteModule::validateCsrf($_POST['csrf_token'] ?? '', 'admin')) {
        CentralVixSiteModule::json(['success' => false, 'message' => 'Token CSRF inválido.'], 403);
    }
    $project = CentralVixSiteProjectService::get((int) ($_POST['project_id'] ?? 0));
    if (!$project) {
        CentralVixSiteModule::json(['success' => false, 'message' => 'Projeto não encontrado.']);
    }
    CentralVixSiteModule::json(CentralVixSiteFileService::upload(
        (int) $project->id,
        (int) $project->client_id,
        'admin',
        (int) ($_SESSION['adminid'] ?? 0),
        preg_replace('/[^a-z_]/', '', $_POST['category'] ?? 'documents')
    ));
}

CentralVixSiteModule::json(['success' => false, 'message' => 'Acesso negado.'], 403);
