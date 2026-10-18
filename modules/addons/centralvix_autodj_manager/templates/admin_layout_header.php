<?php if (!defined('WHMCS')) { die('This file cannot be accessed directly'); } ?>
<?php
require_once __DIR__ . '/../../centralvix_shared/lib/AdminUi.php';
CentralVixAdminUi::renderHeader([
    'title' => 'AutoDJ Manager',
    'subtitle' => 'Providers, contas AutoDJ e integração com servidores de streaming.',
    'modulelink' => $modulelink,
    'tab' => $tab,
    'success' => $success ?? '',
    'error' => $error ?? '',
    'nav' => [
        ['tab' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'fa-chart-pie'],
        ['tab' => 'servers', 'label' => 'Providers', 'icon' => 'fa-server'],
        ['tab' => 'accounts', 'label' => 'Contas', 'icon' => 'fa-users'],
        ['tab' => 'settings', 'label' => 'Configurações', 'icon' => 'fa-cog'],
        ['tab' => 'logs', 'label' => 'Logs', 'icon' => 'fa-list-alt'],
    ],
]);
