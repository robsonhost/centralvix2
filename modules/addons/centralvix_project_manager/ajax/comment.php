<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

$isClient = CentralVixPmModule::isClient();
$area = $isClient ? 'client' : 'admin';
if (!$isClient && !CentralVixPmModule::isAdmin()) {
    CentralVixPmModule::json(['success' => false, 'message' => 'Acesso negado.'], 403);
}
if (!CentralVixPmModule::validateCsrf($_POST['csrf_token'] ?? '', $area)) {
    CentralVixPmModule::json(['success' => false, 'message' => 'CSRF inválido.'], 403);
}

$projectId = (int) ($_POST['project_id'] ?? 0);
$taskId = (int) ($_POST['task_id'] ?? 0) ?: null;
$message = $_POST['message'] ?? '';
$visibility = $_POST['visibility'] ?? 'internal';

if ($isClient) {
    CentralVixPmModule::json(CentralVixPmCommentService::add(
        $projectId, $taskId, 'client', (int) $_SESSION['uid'],
        CentralVixPmModule::clientName($_SESSION['uid']), $message, 'public', (int) $_SESSION['uid']
    ));
}

CentralVixPmModule::json(CentralVixPmCommentService::add(
    $projectId, $taskId, 'admin', (int) ($_SESSION['adminid'] ?? 0),
    CentralVixPmModule::adminName($_SESSION['adminid'] ?? 0), $message, $visibility, null
));
