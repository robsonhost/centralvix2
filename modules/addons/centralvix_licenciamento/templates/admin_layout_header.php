<?php if (!defined('WHMCS')) { die('This file cannot be accessed directly'); } ?>
<?php
require_once __DIR__ . '/../../centralvix_shared/lib/AdminUi.php';
CentralVixAdminUi::renderHeader([
    'title' => 'Licenciamento',
    'subtitle' => 'Gestão de licenças de software, produtos e validação via API.',
    'modulelink' => $modulelink,
    'tab' => $tab,
    'success' => $success ?? '',
    'error' => $error ?? '',
    'nav' => [
        ['tab' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'fa-chart-pie'],
        ['tab' => 'licenses', 'label' => 'Licenças', 'icon' => 'fa-key'],
        ['tab' => 'products', 'label' => 'Produtos', 'icon' => 'fa-box'],
        ['tab' => 'logs', 'label' => 'Logs', 'icon' => 'fa-list-alt'],
        ['tab' => 'settings', 'label' => 'Configurações', 'icon' => 'fa-cog'],
    ],
]);
