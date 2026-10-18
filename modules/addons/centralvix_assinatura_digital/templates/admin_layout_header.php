<?php if (!defined('WHMCS')) { die('This file cannot be accessed directly'); } ?>
<?php
require_once __DIR__ . '/../../centralvix_shared/lib/AdminUi.php';
$legalNotice = trim((string) CentralVixSignModule::dbSetting('legal_notice', ''));
$extraHtml = $legalNotice !== '' ? '<div class="cvix-legal">' . CentralVixSignSecurity::e($legalNotice) . '</div>' : '';
CentralVixAdminUi::renderHeader([
    'title' => 'Assinatura Digital',
    'subtitle' => 'Documentos, templates e auditoria de assinaturas eletrônicas.',
    'modulelink' => $modulelink,
    'tab' => $tab,
    'success' => $success ?? '',
    'error' => $error ?? '',
    'extra_html' => $extraHtml,
    'nav' => [
        ['tab' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'fa-chart-pie'],
        ['tab' => 'documents', 'label' => 'Documentos', 'icon' => 'fa-file-signature'],
        ['tab' => 'templates', 'label' => 'Templates', 'icon' => 'fa-file-alt'],
        ['tab' => 'audit', 'label' => 'Auditoria', 'icon' => 'fa-shield-alt'],
        ['tab' => 'settings', 'label' => 'Configurações', 'icon' => 'fa-cog'],
    ],
]);
