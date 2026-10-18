<?php if (!defined('WHMCS')) { die('This file cannot be accessed directly'); }
$emailOn = CentralVixPmModule::dbSetting('email_enabled','1')==='1';
?>
<h2>Configurações</h2>
<?php if ($success): ?><div class="cvix-pm-alert ok"><?php echo CentralVixPmSecurity::e($success); ?></div><?php endif; ?>
<form method="post" class="cvix-pm-form">
<input type="hidden" name="csrf_token" value="<?php echo CentralVixPmSecurity::e($csrf); ?>">
<label class="cvix-pm-check"><input type="checkbox" name="email_enabled" value="1" <?php echo $emailOn?'checked':''; ?>> Notificar cliente por e-mail</label>
<button type="submit" class="cvix-pm-btn">Salvar</button>
</form>
