<?php
$apiSecret = CentralVixLicModule::dbSetting('api_secret', '');
?>
<h2>Configurações</h2>
<?php if ($success): ?><div class="cvix-lic-alert ok"><?php echo CentralVixLicSecurity::e($success); ?></div><?php endif; ?>
<form method="post" class="cvix-lic-form cvix-lic-panel">
<input type="hidden" name="csrf_token" value="<?php echo CentralVixLicSecurity::e($csrf); ?>">
<label>Grace period padrão (horas)<select name="default_grace_hours">
<?php $g = (int) CentralVixLicModule::dbSetting('default_grace_hours', 24); foreach ([24,48,72] as $h): ?>
<option value="<?php echo $h; ?>" <?php echo $g===$h?'selected':''; ?>><?php echo $h; ?> horas</option>
<?php endforeach; ?>
</select></label>
<label>Rate limit API (req/min/IP)<input type="number" name="api_rate_limit" min="10" value="<?php echo (int) CentralVixLicModule::dbSetting('api_rate_limit', 60); ?>"></label>
<label class="cvix-lic-check"><input type="checkbox" name="email_enabled" value="1" <?php echo CentralVixLicModule::dbSetting('email_enabled','1')==='1'?'checked':''; ?>> E-mail ao criar licença</label>
<p>API Secret (admin/integrações server-side): <code><?php echo CentralVixLicSecurity::e(substr($apiSecret, 0, 8)); ?>…</code></p>
<label class="cvix-lic-check"><input type="checkbox" name="regenerate_api_secret" value="1"> Regenerar API secret</label>
<button type="submit" class="cvix-lic-btn">Salvar</button>
</form>
