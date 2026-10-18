<?php if (!defined('WHMCS')) { die('This file cannot be accessed directly'); } ?>
<?php
require_once __DIR__ . '/../../centralvix_shared/lib/AdminUi.php';
CentralVixAdminUi::renderHeader([
    'title' => 'Site Builder',
    'subtitle' => 'Projetos de sites, templates e publicação para clientes.',
    'modulelink' => $modulelink,
    'tab' => $tab,
    'success' => $success ?? '',
    'error' => $error ?? '',
    'nav' => [
        ['tab' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'fa-chart-pie'],
        ['tab' => 'projects', 'label' => 'Projetos', 'icon' => 'fa-globe'],
        ['tab' => 'templates', 'label' => 'Templates', 'icon' => 'fa-palette'],
        ['tab' => 'settings', 'label' => 'Configurações', 'icon' => 'fa-cog'],
    ],
]);
