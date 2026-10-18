<?php if (!defined('WHMCS')) { die('This file cannot be accessed directly'); } ?>
<?php
require_once __DIR__ . '/../../centralvix_shared/lib/AdminUi.php';
$projectQuery = !empty($filterProject) ? 'project_id=' . (int) $filterProject : '';
CentralVixAdminUi::renderHeader([
    'title' => 'Project Manager',
    'subtitle' => 'Projetos, tarefas Kanban e acompanhamento de entregas.',
    'modulelink' => $modulelink,
    'tab' => $tab,
    'success' => $success ?? '',
    'error' => $error ?? '',
    'nav' => [
        ['tab' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'fa-chart-pie'],
        ['tab' => 'projects', 'label' => 'Projetos', 'icon' => 'fa-folder-open'],
        ['tab' => 'tasks', 'label' => 'Kanban', 'icon' => 'fa-columns', 'query' => $projectQuery],
        ['tab' => 'settings', 'label' => 'Configurações', 'icon' => 'fa-cog'],
    ],
]);
