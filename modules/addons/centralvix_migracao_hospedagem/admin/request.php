<?php
$request = CentralVixMigMigrationService::get($requestId);
if (!$request) {
    echo '<p>Solicitação não encontrada.</p>';
    return;
}
$statuses = CentralVixMigModule::statuses();
$checklist = CentralVixMigChecklistService::listForRequest($requestId);
$comments = CentralVixMigMigrationService::commentsForRequest($requestId, false);
$files = CentralVixMigFileService::listForRequest($requestId);
$logs = CentralVixMigLogger::forRequest($requestId, 30);
$maskedCreds = CentralVixMigCredentialVault::maskedView($request);
$client = CentralVixMigModule::clientName($request->client_id);
$dl = $modulelink . '&' . CentralVixMigModule::DOWNLOAD_PARAM . '=';
?>
<h2><?php echo CentralVixMigSecurity::e($request->reference); ?> — <?php echo CentralVixMigSecurity::e($request->domain); ?></h2>
<p class="cvix-mig-muted">Cliente: <?php echo CentralVixMigSecurity::e($client); ?> · Status: <span class="cvix-mig-pill cvix-mig-pill--<?php echo CentralVixMigSecurity::e($request->status); ?>"><?php echo CentralVixMigSecurity::e($statuses[$request->status] ?? ''); ?></span> · Progresso: <?php echo (int)$request->progress_percent; ?>%</p>

<div class="cvix-mig-split">
<div class="cvix-mig-panel">
<h3>Alterar status</h3>
<form method="post" class="cvix-mig-form">
<input type="hidden" name="csrf_token" value="<?php echo CentralVixMigSecurity::e($csrf); ?>">
<input type="hidden" name="admin_action" value="status">
<input type="hidden" name="request_id" value="<?php echo (int)$requestId; ?>">
<label>Status<select name="new_status">
<?php foreach ($statuses as $k=>$l): ?>
<option value="<?php echo CentralVixMigSecurity::e($k); ?>" <?php echo $request->status===$k?'selected':''; ?>><?php echo CentralVixMigSecurity::e($l); ?></option>
<?php endforeach; ?>
</select></label>
<label>Mensagem ao cliente<textarea name="client_message" rows="3"><?php echo CentralVixMigSecurity::e($request->client_message); ?></textarea></label>
<button type="submit" class="cvix-mig-btn" onclick="return confirm('Confirmar alteração de status?');">Atualizar status</button>
</form>

<h3>Agendar</h3>
<form method="post" class="cvix-mig-form">
<input type="hidden" name="csrf_token" value="<?php echo CentralVixMigSecurity::e($csrf); ?>">
<input type="hidden" name="admin_action" value="schedule">
<input type="hidden" name="request_id" value="<?php echo (int)$requestId; ?>">
<label>Data<input type="date" name="scheduled_date" value="<?php echo $request->scheduled_date ? date('Y-m-d', strtotime($request->scheduled_date)) : ''; ?>"></label>
<button type="submit" class="cvix-mig-btn sm">Agendar migração</button>
</form>
</div>

<div class="cvix-mig-panel wide">
<h3>Dados da solicitação</h3>
<div class="cvix-mig-grid2">
<div><strong>Hospedagem atual</strong><br><?php echo CentralVixMigSecurity::e($request->current_hosting ?: '—'); ?></div>
<div><strong>Painel</strong><br><?php echo CentralVixMigSecurity::e(CentralVixMigModule::panels()[$request->current_panel] ?? ''); ?></div>
<div><strong>Servidor</strong><br><?php echo CentralVixMigSecurity::e($request->current_server ?: '—'); ?></div>
<div><strong>Tipo</strong><br><?php echo CentralVixMigSecurity::e($request->migration_type); ?></div>
<div><strong>Tamanho est.</strong><br><?php echo CentralVixMigSecurity::e($request->estimated_size ?: '—'); ?></div>
<div><strong>Bancos / E-mails</strong><br><?php echo (int)$request->database_count; ?> / <?php echo (int)$request->email_count; ?></div>
<div><strong>Data preferencial</strong><br><?php echo $request->preferred_date ? date('d/m/Y', strtotime($request->preferred_date)) : '—'; ?></div>
<div><strong>Agendada</strong><br><?php echo $request->scheduled_date ? date('d/m/Y H:i', strtotime($request->scheduled_date)) : '—'; ?></div>
</div>
<?php if ($request->notes): ?><p><strong>Observações cliente:</strong><br><?php echo nl2br(CentralVixMigSecurity::e($request->notes)); ?></p><?php endif; ?>

<h3>Checklist</h3>
<form method="post">
<input type="hidden" name="csrf_token" value="<?php echo CentralVixMigSecurity::e($csrf); ?>">
<input type="hidden" name="admin_action" value="checklist">
<input type="hidden" name="request_id" value="<?php echo (int)$requestId; ?>">
<div class="cvix-mig-checklist">
<?php foreach ($checklist as $item): ?>
<label class="cvix-mig-check-item">
<input type="checkbox" name="completed" value="1" <?php echo $item->completed?'checked':''; ?> onchange="this.form.item_key.value='<?php echo CentralVixMigSecurity::e($item->item_key); ?>'; this.form.submit();">
<?php echo CentralVixMigSecurity::e($item->label); ?>
</label>
<?php endforeach; ?>
<input type="hidden" name="item_key" value="">
</div>
</form>

<h3>Credenciais (criptografadas)</h3>
<?php if ($maskedCreds): ?>
<p class="cvix-mig-muted">Host: <?php echo CentralVixMigSecurity::e($maskedCreds['host']); ?> · Porta: <?php echo CentralVixMigSecurity::e($maskedCreds['port']); ?> · Usuário: <?php echo CentralVixMigSecurity::e($maskedCreds['username']); ?> · Senha: ********</p>
<form method="post" style="display:inline" onsubmit="return confirm('Excluir credenciais permanentemente?');">
<input type="hidden" name="csrf_token" value="<?php echo CentralVixMigSecurity::e($csrf); ?>">
<input type="hidden" name="admin_action" value="purge_credentials">
<input type="hidden" name="request_id" value="<?php echo (int)$requestId; ?>">
<button type="submit" class="cvix-mig-btn sm danger">Excluir credenciais</button>
</form>
<?php else: ?>
<p class="cvix-mig-muted">Nenhuma credencial armazenada<?php echo $request->credentials_purged_at ? ' (excluídas em '.date('d/m/Y', strtotime($request->credentials_purged_at)).')' : ''; ?>.</p>
<?php endif; ?>
<form method="post" class="cvix-mig-form" style="margin-top:10px">
<input type="hidden" name="csrf_token" value="<?php echo CentralVixMigSecurity::e($csrf); ?>">
<input type="hidden" name="admin_action" value="credentials">
<input type="hidden" name="request_id" value="<?php echo (int)$requestId; ?>">
<label>Host<input type="text" name="cred_host"></label>
<label>Porta<input type="number" name="cred_port" value="2083"></label>
<label>Usuário<input type="text" name="cred_username" autocomplete="off"></label>
<label>Senha<input type="password" name="cred_password" autocomplete="new-password"></label>
<button type="submit" class="cvix-mig-btn sm">Salvar credenciais</button>
</form>

<h3>Notas internas</h3>
<form method="post" class="cvix-mig-form">
<input type="hidden" name="csrf_token" value="<?php echo CentralVixMigSecurity::e($csrf); ?>">
<input type="hidden" name="admin_action" value="admin_notes">
<input type="hidden" name="request_id" value="<?php echo (int)$requestId; ?>">
<textarea name="admin_notes" rows="4"><?php echo CentralVixMigSecurity::e($request->admin_notes); ?></textarea>
<button type="submit" class="cvix-mig-btn sm">Salvar notas</button>
</form>

<h3>Comentários</h3>
<?php foreach ($comments as $c): ?>
<div class="cvix-mig-comment cvix-mig-comment--<?php echo CentralVixMigSecurity::e($c->visibility); ?>">
<strong><?php echo CentralVixMigSecurity::e($c->author_name); ?></strong>
<span class="cvix-mig-muted"><?php echo date('d/m/Y H:i', strtotime($c->created_at)); ?> · <?php echo CentralVixMigSecurity::e($c->visibility); ?></span>
<p><?php echo nl2br(CentralVixMigSecurity::e($c->message)); ?></p>
</div>
<?php endforeach; ?>

<h3>Arquivos</h3>
<ul class="cvix-mig-files">
<?php foreach ($files as $f): ?>
<li><a href="<?php echo CentralVixMigSecurity::e($dl.(int)$f->id); ?>"><?php echo CentralVixMigSecurity::e($f->original_name); ?></a> (<?php echo CentralVixMigSecurity::e($f->file_type); ?>)</li>
<?php endforeach; ?>
</ul>

<h3>Log</h3>
<ul class="cvix-mig-loglist">
<?php foreach ($logs as $log): ?>
<li><code><?php echo CentralVixMigSecurity::e($log->created_at); ?></code> <?php echo CentralVixMigSecurity::e($log->action); ?> — <?php echo CentralVixMigSecurity::e($log->message); ?></li>
<?php endforeach; ?>
</ul>
</div>
</div>
<p><a href="<?php echo CentralVixMigSecurity::e($modulelink.'&tab=requests'); ?>" class="cvix-mig-btn sm">← Voltar</a></p>
