<?php if (!defined('WHMCS')) { die('This file cannot be accessed directly'); } ?>
<?php
require_once __DIR__ . '/../../centralvix_shared/lib/AdminUi.php';
CentralVixAdminUi::renderHeader([
    'title' => 'Streaming Manager',
    'subtitle' => 'Servidores, contas, planos e estatísticas de streaming.',
    'modulelink' => $modulelink,
    'tab' => $tab,
    'success' => $success ?? '',
    'error' => $error ?? '',
    'nav' => [
        ['tab' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'fa-chart-pie'],
        ['tab' => 'servers', 'label' => 'Servidores', 'icon' => 'fa-server'],
        ['tab' => 'accounts', 'label' => 'Contas', 'icon' => 'fa-users'],
        ['tab' => 'plans', 'label' => 'Planos', 'icon' => 'fa-layer-group'],
        ['tab' => 'statistics', 'label' => 'Estatísticas', 'icon' => 'fa-chart-line'],
        ['tab' => 'logs', 'label' => 'Logs', 'icon' => 'fa-list-alt'],
        ['tab' => 'settings', 'label' => 'Configurações', 'icon' => 'fa-cog'],
    ],
]);
