<?php if (!defined('WHMCS')) { die('This file cannot be accessed directly'); } ?>
<h2>Configurações</h2>
<?php if ($success): ?><div class="cvix-sig-alert ok"><?php echo CentralVixSignSecurity::e($success); ?></div><?php endif; ?>
<form method="post" class="cvix-sig-form cvix-sig-panel">
<input type="hidden" name="csrf_token" value="<?php echo CentralVixSignSecurity::e($csrf); ?>">
<label class="cvix-sig-check"><input type="checkbox" name="email_enabled" value="1" <?php echo CentralVixSignModule::dbSetting('email_enabled','1')==='1'?'checked':''; ?>> E-mail ao solicitar/assinar</label>
<label>Aviso legal exibido<textarea name="legal_notice" rows="4"><?php echo CentralVixSignSecurity::e(CentralVixSignModule::dbSetting('legal_notice','')); ?></textarea></label>
<button type="submit" class="cvix-sig-btn">Salvar</button>
</form>
