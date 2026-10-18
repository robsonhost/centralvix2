<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

if (!CentralVixPmModule::isAdmin()) {
    CentralVixPmModule::json(['success' => false, 'message' => 'Acesso negado.'], 403);
}
if (!CentralVixPmModule::validateCsrf($_POST['csrf_token'] ?? '', 'admin')) {
    CentralVixPmModule::json(['success' => false, 'message' => 'CSRF inválido.'], 403);
}

$type = $_POST['type'] ?? 'project';
$projectId = (int) ($_POST['project_id'] ?? 0);

if ($type === 'kanban') {
    $columns = json_decode($_POST['columns'] ?? '{}', true);
    CentralVixPmModule::json(CentralVixPmTaskService::reorderKanban($projectId, is_array($columns) ? $columns : []));
}

if ($type === 'task') {
    CentralVixPmModule::json(CentralVixPmTaskService::moveKanban(
        (int) ($_POST['task_id'] ?? 0),
        $_POST['status'] ?? 'todo',
        (int) ($_POST['kanban_order'] ?? 0),
        $projectId
    ));
}

CentralVixPmModule::json(CentralVixPmProjectService::changeStatus(
    $projectId,
    preg_replace('/[^a-z_]/', '', $_POST['status'] ?? ''),
    'admin',
    (int) ($_SESSION['adminid'] ?? 0)
));
