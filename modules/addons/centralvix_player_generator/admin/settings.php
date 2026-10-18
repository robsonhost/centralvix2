<?php
if (!defined('WHMCS')) { die('This file cannot be accessed directly'); }
$trackViews = CentralVixPlayerModule::dbSetting('track_views', '1') === '1';
?>
<h2>Configurações</h2>
<?php if (!empty($success)): ?><div class="cvix-pg-alert ok"><?php echo CentralVixPlayerSecurity::e($success); ?></div><?php endif; ?>
<form method="post" class="cvix-pg-form">
<input type="hidden" name="csrf_token" value="<?php echo CentralVixPlayerSecurity::e($csrf); ?>">
<label class="cvix-pg-check"><input type="checkbox" name="track_views" value="1" <?php echo $trackViews ? 'checked' : ''; ?>> Registrar visualizações dos players públicos</label>
<button type="submit" class="cvix-pg-btn">Salvar</button>
</form>
