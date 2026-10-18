<?php
$rows = CentralVixMigMigrationService::listForClient($clientId);
$statuses = CentralVixMigModule::statuses();
?>
<div class="cvix-mig-hero">
<h2>Migração de Hospedagem</h2>
<p>Solicite a migração do seu site para a CentralVix e acompanhe cada etapa.</p>
<a class="cvix-mig-btn" href="<?php echo CentralVixMigSecurity::e($modulelink.'&action=new'); ?>">Nova solicitação</a>
</div>
<?php if (!count($rows)): ?>
<div class="cvix-mig-empty">Você ainda não possui solicitações de migração.</div>
<?php else: ?>
<div class="cvix-mig-grid">
<?php foreach ($rows as $r): ?>
<a class="cvix-mig-card-link" href="<?php echo CentralVixMigSecurity::e($modulelink.'&action=request&id='.(int)$r->id); ?>">
<div class="cvix-mig-card-head">
<strong><?php echo CentralVixMigSecurity::e($r->domain); ?></strong>
<span class="cvix-mig-pill cvix-mig-pill--<?php echo CentralVixMigSecurity::e($r->status); ?>"><?php echo CentralVixMigSecurity::e($statuses[$r->status] ?? $r->status); ?></span>
</div>
<p class="cvix-mig-muted"><?php echo CentralVixMigSecurity::e($r->reference); ?></p>
<div class="cvix-mig-progress"><div style="width:<?php echo (int)$r->progress_percent; ?>%"></div></div>
<span class="cvix-mig-muted"><?php echo (int)$r->progress_percent; ?>% concluído</span>
</a>
<?php endforeach; ?>
</div>
<?php endif; ?>
