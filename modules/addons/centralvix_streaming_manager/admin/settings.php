<?php if (!defined('WHMCS')) { die('This file cannot be accessed directly'); } ?>
<div class="cvix-st-panel">
    <h3>Configurações</h3>
    <ul class="cvix-st-list">
        <li>Timeout API, retenção de stats, intervalo de coleta</li>
        <li>IDs de produtos para hooks de provisionamento</li>
        <li>Notificações ao cliente</li>
        <li>Seed de criptografia para credenciais</li>
    </ul>
    <p>Configure em <strong>Configurações → Addon Modules → CentralVix Streaming Manager</strong>.</p>
</div>
<div class="cvix-st-panel">
    <h3>Server module WHMCS</h3>
    <p>Para provisionamento nativo no pedido (Create/Suspend/Terminate automático no produto), recomenda-se também um <strong>server module</strong> em <code>/modules/servers/</code> que delegue a este addon. Os hooks <code>AfterModule*</code> já cobrem serviços criados por qualquer server module.</p>
    <p>Stats Icecast: <code>/status-json.xsl</code> · SHOUTcast: <code>/admin.cgi?mode=viewxml</code> ou <code>/7.html</code></p>
</div>
