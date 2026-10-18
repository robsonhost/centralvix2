<?php if (!defined('WHMCS')) { die('This file cannot be accessed directly'); } ?>
<?php
require_once __DIR__ . '/../../centralvix_shared/lib/AdminUi.php';
CentralVixAdminUi::renderHeader([
    'title' => 'Régua de Cobrança',
    'subtitle' => 'Automação de lembretes e acompanhamento de faturas.',
    'modulelink' => $modulelink,
    'tab' => $tab,
    'success' => $success ?? '',
    'error' => $error ?? '',
    'nav' => [
        ['tab' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'fa-chart-pie'],
        ['tab' => 'rules', 'label' => 'Réguas', 'icon' => 'fa-stream'],
        ['tab' => 'templates', 'label' => 'Templates', 'icon' => 'fa-envelope'],
        ['tab' => 'history', 'label' => 'Histórico', 'icon' => 'fa-history'],
        ['tab' => 'settings', 'label' => 'Configurações', 'icon' => 'fa-cog'],
    ],
]);
