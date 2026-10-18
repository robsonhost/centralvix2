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

$action = $_POST['task_action'] ?? 'save';
$projectId = (int) ($_POST['project_id'] ?? 0);

if ($action === 'delete') {
    CentralVixPmModule::json(CentralVixPmTaskService::delete((int) $_POST['task_id'], $projectId));
}
if ($action === 'kanban_reorder') {
    $columns = json_decode($_POST['columns'] ?? '{}', true);
    CentralVixPmModule::json(CentralVixPmTaskService::reorderKanban($projectId, is_array($columns) ? $columns : []));
}

CentralVixPmModule::json(CentralVixPmTaskService::save($_POST, (int) ($_POST['task_id'] ?? 0)));
