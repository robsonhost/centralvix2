<?php
$csrf = CentralVixUpdModule::csrfToken();
$rate = CentralVixUpdModule::dbSetting('api_rate_limit', '60');
$ttl = CentralVixUpdModule::dbSetting('download_token_ttl', '15');
$apiOn = CentralVixUpdModule::setting('api_enabled', 'on');
?>
<h2>Configurações</h2>
<form method="post" class="cvix-upd-panel cvix-upd-form" style="max-width:420px">
<input type="hidden" name="csrf_token" value="<?php echo CentralVixUpdSecurity::e($csrf); ?>">
<input type="hidden" name="admin_action" value="save_settings">
<label>Rate limit API (req/min)<input type="number" name="api_rate_limit" min="1" value="<?php echo (int)$rate; ?>"></label>
<label>Expiração token download (minutos)<input type="number" name="download_token_ttl" min="5" value="<?php echo (int)$ttl; ?>"></label>
<p class="cvix-upd-muted">API ativa via addon WHMCS: <?php echo $apiOn === 'on' ? 'Sim' : 'Não'; ?></p>
<p class="cvix-upd-muted">Assinatura de pacotes: HMAC-SHA256 (segredo servidor em DB).</p>
<button type="submit" class="cvix-upd-btn">Salvar</button>
</form>
