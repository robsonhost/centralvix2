<?php
$emailOn = CentralVixMigModule::dbSetting('email_enabled', '1');
$waOn = CentralVixMigModule::dbSetting('whatsapp_enabled', '0');
$purgeOn = CentralVixMigModule::dbSetting('auto_purge_credentials', '1');
$backupOn = CentralVixMigModule::dbSetting('allow_client_backup_upload', '0');
$provider = CentralVixMigModule::migrationProvider();
?>
<h2>Configurações</h2>
<form method="post" class="cvix-mig-panel cvix-mig-form" style="max-width:480px">
<input type="hidden" name="csrf_token" value="<?php echo CentralVixMigSecurity::e($csrf); ?>">
<label class="cvix-mig-check"><input type="checkbox" name="email_enabled" value="1" <?php echo $emailOn==='1'?'checked':''; ?>> Notificações por e-mail</label>
<label class="cvix-mig-check"><input type="checkbox" name="whatsapp_enabled" value="1" <?php echo $waOn==='1'?'checked':''; ?>> Preparar mensagens WhatsApp (sem credenciais)</label>
<label class="cvix-mig-check"><input type="checkbox" name="auto_purge_credentials" value="1" <?php echo $purgeOn==='1'?'checked':''; ?>> Excluir credenciais ao concluir/cancelar</label>
<label class="cvix-mig-check"><input type="checkbox" name="allow_client_backup_upload" value="1" <?php echo $backupOn==='1'?'checked':''; ?>> Permitir upload de backup pelo cliente</label>
<p class="cvix-mig-muted">Provider ativo: <?php echo CentralVixMigSecurity::e($provider->getName()); ?> · Automatizado: <?php echo $provider->isAutomated() ? 'Sim' : 'Não'; ?></p>
<p class="cvix-mig-muted">Credenciais: AES-256-GCM · Chave mestra em banco (settings).</p>
<button type="submit" class="cvix-mig-btn">Salvar</button>
</form>
