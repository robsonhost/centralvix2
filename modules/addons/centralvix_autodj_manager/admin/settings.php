<?php if (!defined('WHMCS')) { die('This file cannot be accessed directly'); } ?>
<div class="cvix-adj-panel">
    <h3>Configurações globais</h3>
    <ul class="cvix-adj-list">
        <li>Upload máximo: <?php echo CentralVixAutoDJSecurity::e($settings['max_upload_mb'] ?? '50'); ?> MB</li>
        <li>AAC permitido: <?php echo ($settings['allow_aac'] ?? '') === 'on' ? 'Sim' : 'Não'; ?></li>
        <li>Quota padrão: <?php echo CentralVixAutoDJSecurity::e($settings['default_quota_mb'] ?? '5120'); ?> MB</li>
    </ul>
    <p>Altere em <strong>Configurações → Addon Modules → CentralVix AutoDJ Manager</strong>.</p>
    <p>Storage: <code><?php echo CentralVixAutoDJSecurity::e(CentralVixAutoDJModule::storagePath()); ?></code> (protegido .htaccess)</p>
</div>
