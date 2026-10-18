<?php if (!defined('WHMCS')) { die('This file cannot be accessed directly'); } ?>
<div class="cvix-rc-panel">
    <h3>Configurações do módulo</h3>
    <p>Parâmetros globais em <strong>Configurações → Addon Modules → CentralVix Régua de Cobrança</strong>:</p>
    <ul class="cvix-rc-list">
        <li>Nome da empresa ({empresa})</li>
        <li>Janela global de envio (ex: 08:00 — 20:00)</li>
        <li>Intervalo do cron (minutos)</li>
        <li>WhatsApp: endpoint, token, instância, método HTTP, headers JSON</li>
        <li>Confirmação de pagamento e template</li>
    </ul>
</div>

<div class="cvix-rc-panel">
    <h3>Teste WhatsApp</h3>
    <form id="cvixTestWhatsappForm" class="cvix-rc-form">
        <input type="hidden" name="token" value="<?php echo CentralVixReguaSecurity::e($csrfToken); ?>">
        <label>Telefone<input name="phone" placeholder="5511999999999" required></label>
        <label>Mensagem<textarea name="message" rows="3">Teste CentralVix Régua de Cobrança</textarea></label>
        <button type="submit" class="cvix-rc-btn primary">Enviar teste</button>
    </form>
    <div id="cvixTestResult"></div>
</div>

<div class="cvix-rc-panel">
    <h3>Cron</h3>
    <p>O hook <code>AfterCronJob</code> executa a régua automaticamente respeitando o intervalo configurado.</p>
    <p><strong>Cron WHMCS (recomendado):</strong></p>
    <code>*/5 * * * * php -q /caminho/whmcs/crons/cron.php</code>
    <p><strong>Cron dedicado (alternativa):</strong></p>
    <code>0 * * * * php -q <?php echo CentralVixReguaSecurity::e(CentralVixReguaModule::path()); ?>/cron.php</code>
    <p>Última execução registrada: <strong><?php echo CentralVixReguaSecurity::e($lastExecution); ?></strong></p>
</div>

<div class="cvix-rc-panel">
    <h3>Segurança</h3>
    <ul class="cvix-rc-list">
        <li>CSRF em todas requisições AJAX</li>
        <li>Idempotência: invoice_id + rule_id + data</li>
        <li>Tokens WhatsApp nunca aparecem nos logs</li>
        <li>Faturas pagas não recebem novos lembretes</li>
    </ul>
</div>
