<?php
$filters = [];
if (!empty($filterStatus)) {
    $filters['status'] = $filterStatus;
}
if (!empty($_GET['q'])) {
    $filters['q'] = CentralVixMigSecurity::sanitizeText($_GET['q'], 80);
}
$rows = CentralVixMigMigrationService::listAdmin($filters);
$statuses = CentralVixMigModule::statuses();
?>
<h2>Solicitações</h2>
<form method="get" class="cvix-mig-inline" style="margin-bottom:12px">
<input type="hidden" name="module" value="centralvix_migracao_hospedagem">
<input type="hidden" name="tab" value="requests">
<label>Status <select name="status" onchange="this.form.submit()">
<option value="">Todos</option>
<?php foreach ($statuses as $k=>$l): ?>
<option value="<?php echo CentralVixMigSecurity::e($k); ?>" <?php echo $filterStatus===$k?'selected':''; ?>><?php echo CentralVixMigSecurity::e($l); ?></option>
<?php endforeach; ?>
</select></label>
<label>Busca <input type="text" name="q" value="<?php echo CentralVixMigSecurity::e($_GET['q'] ?? ''); ?>" placeholder="domínio ou ref"></label>
<button type="submit" class="cvix-mig-btn sm">Filtrar</button>
</form>
<table class="cvix-mig-table">
<thead><tr><th>Ref</th><th>Cliente</th><th>Domínio</th><th>Painel</th><th>Status</th><th>Progresso</th><th>Data</th><th></th></tr></thead>
<tbody>
<?php foreach ($rows as $r): ?>
<tr>
<td><code><?php echo CentralVixMigSecurity::e($r->reference); ?></code></td>
<td><?php echo CentralVixMigSecurity::e(trim($r->firstname.' '.$r->lastname)); ?></td>
<td><?php echo CentralVixMigSecurity::e($r->domain); ?></td>
<td><?php echo CentralVixMigSecurity::e(CentralVixMigModule::panels()[$r->current_panel] ?? $r->current_panel); ?></td>
<td><span class="cvix-mig-pill cvix-mig-pill--<?php echo CentralVixMigSecurity::e($r->status); ?>"><?php echo CentralVixMigSecurity::e($statuses[$r->status] ?? $r->status); ?></span></td>
<td><?php echo (int)$r->progress_percent; ?>%</td>
<td><?php echo CentralVixMigSecurity::e(substr($r->created_at,0,10)); ?></td>
<td><a class="cvix-mig-btn sm" href="<?php echo CentralVixMigSecurity::e($modulelink.'&tab=request&id='.(int)$r->id); ?>">Abrir</a></td>
</tr>
<?php endforeach; ?>
<?php if (!count($rows)): ?><tr><td colspan="8">Nenhuma solicitação.</td></tr><?php endif; ?>
</tbody>
</table>
