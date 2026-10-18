<?php
if (!defined('WHMCS')) { die('This file cannot be accessed directly'); }
$emailOn = CentralVixSiteModule::dbSetting('email_enabled', '1') === '1';
$waOn = CentralVixSiteModule::dbSetting('whatsapp_enabled', '') === '1';
$custom = CentralVixSiteBriefingService::getCustomFieldsConfig();
?>
<h2>Configurações</h2>
<?php if (!empty($success)): ?><div class="cvix-sb-alert ok"><?php echo CentralVixSiteSecurity::e($success); ?></div><?php endif; ?>
<form method="post" class="cvix-sb-form">
<input type="hidden" name="csrf_token" value="<?php echo CentralVixSiteSecurity::e($csrf); ?>">
<h3>Notificações</h3>
<label class="cvix-sb-check"><input type="checkbox" name="email_enabled" value="1" <?php echo $emailOn?'checked':''; ?>> Enviar e-mail nos eventos</label>
<label class="cvix-sb-check"><input type="checkbox" name="whatsapp_enabled" value="1" <?php echo $waOn?'checked':''; ?>> Preparar WhatsApp (log interno)</label>
<h3>Campos adicionais do briefing</h3>
<div id="cvix-sb-custom-fields">
<?php foreach ($custom as $i=>$c): ?>
<div class="cvix-sb-custom-row">
<input type="text" name="custom_field_key[]" value="<?php echo CentralVixSiteSecurity::e($c['key']); ?>" placeholder="chave">
<input type="text" name="custom_field_label[]" value="<?php echo CentralVixSiteSecurity::e($c['label']); ?>" placeholder="Label">
</div>
<?php endforeach; ?>
<div class="cvix-sb-custom-row">
<input type="text" name="custom_field_key[]" placeholder="chave (ex: telefone_comercial)">
<input type="text" name="custom_field_label[]" placeholder="Label">
</div>
</div>
<button type="submit" class="cvix-sb-btn">Salvar</button>
</form>
