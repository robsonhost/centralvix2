<?php if (!defined('WHMCS')) { die('This file cannot be accessed directly'); } ?>
<div class="cvix-sp-panel">
    <h3>Configurações</h3>
    <p>Notificações, WhatsApp e opções avançadas são configuradas em <strong>Configurações → Addon Modules → CentralVix Status Page</strong>.</p>
    <ul class="cvix-sp-list">
        <li>E-mail de alertas</li>
        <li>WhatsApp (endpoint, token, instância, telefone)</li>
        <li>Cooldown anti-spam de notificações</li>
        <li>Permitir alvos privados (SSRF — desabilitado por padrão)</li>
        <li>Intervalo de checagem via cron</li>
    </ul>
    <h4>Cron manual</h4>
    <code>php <?php echo CentralVixStatusSecurity::e(CentralVixStatusModule::path()); ?>/cron.php</code>
    <h4>Página pública</h4>
    <p><a href="<?php echo CentralVixStatusSecurity::e(CentralVixStatusModule::publicStatusUrl()); ?>" target="_blank"><?php echo CentralVixStatusSecurity::e(CentralVixStatusModule::publicStatusUrl()); ?></a></p>
    <h4>Segurança</h4>
    <ul class="cvix-sp-list">
        <li>CSRF em todas requisições AJAX admin</li>
        <li>Proteção SSRF em monitores HTTP/TCP</li>
        <li>Tokens WhatsApp nunca registrados em logs</li>
        <li>Dados sensíveis ocultos na página pública</li>
    </ul>
</div>
