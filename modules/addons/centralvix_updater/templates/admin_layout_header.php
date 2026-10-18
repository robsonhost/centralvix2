<?php if (!defined('WHMCS')) { die('This file cannot be accessed directly'); } ?>
<?php
require_once __DIR__ . '/../../centralvix_shared/lib/AdminUi.php';
CentralVixAdminUi::renderHeader([
    'title' => 'Atualizador',
    'subtitle' => 'Distribuição de versões, pacotes e downloads seguros para seus produtos.',
    'modulelink' => $modulelink,
    'tab' => $tab,
    'success' => $success ?? '',
    'error' => $error ?? '',
    'nav' => [
        ['tab' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'fa-chart-pie'],
        ['tab' => 'products', 'label' => 'Produtos', 'icon' => 'fa-box'],
        ['tab' => 'versions', 'label' => 'Versões', 'icon' => 'fa-code-branch'],
        ['tab' => 'upload', 'label' => 'Upload', 'icon' => 'fa-cloud-upload-alt'],
        ['tab' => 'downloads', 'label' => 'Downloads', 'icon' => 'fa-download'],
        ['tab' => 'settings', 'label' => 'Configurações', 'icon' => 'fa-cog'],
    ],
]);
