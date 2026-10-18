<?php if (!defined('WHMCS')) { die('This file cannot be accessed directly'); } ?>
<?php
require_once __DIR__ . '/../../centralvix_shared/lib/AdminUi.php';
CentralVixAdminUi::renderHeader([
    'title' => 'Status Page',
    'subtitle' => 'Monitoramento e status dos serviços em tempo real.',
    'modulelink' => $modulelink,
    'tab' => $tab,
    'success' => $success ?? '',
    'error' => $error ?? '',
    'nav' => [
        ['tab' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'fa-chart-pie'],
        ['tab' => 'services', 'label' => 'Serviços', 'icon' => 'fa-cubes'],
        ['tab' => 'monitors', 'label' => 'Monitores', 'icon' => 'fa-heartbeat'],
        ['tab' => 'incidents', 'label' => 'Incidentes', 'icon' => 'fa-exclamation-triangle'],
        ['tab' => 'maintenance', 'label' => 'Manutenções', 'icon' => 'fa-tools'],
        ['tab' => 'history', 'label' => 'Histórico', 'icon' => 'fa-history'],
        ['tab' => 'settings', 'label' => 'Configurações', 'icon' => 'fa-cog'],
        [
            'label' => 'Ver página pública',
            'icon' => 'fa-external-link-alt',
            'href' => CentralVixStatusModule::publicStatusUrl(),
            'target' => '_blank',
            'class' => 'cvix-sp-nav-public',
        ],
    ],
]);
