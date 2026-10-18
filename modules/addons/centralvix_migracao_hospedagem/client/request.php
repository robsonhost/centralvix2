<?php
$request = CentralVixMigMigrationService::getForClient($requestId, $clientId);
if (!$request) {
    echo '<div class="cvix-mig-empty">Solicitação não encontrada.</div>';
    return;
}
$public = CentralVixMigMigrationService::clientPublicPayload($request);
$timeline = CentralVixMigMigrationService::timelineState($request);
$comments = CentralVixMigMigrationService::commentsForRequest($requestId, true);
$files = CentralVixMigFileService::listForRequest($requestId);
$checklistSummary = CentralVixMigChecklistService::progressSummary($requestId);
$dl = $modulelink . '&' . CentralVixMigModule::DOWNLOAD_PARAM . '=';
$ajaxBase = $modulelink . '&' . CentralVixMigModule::AJAX_PARAM . '=';
?>
<div class="cvix-mig-hero cvix-mig-hero--compact">
<h2><?php echo CentralVixMigSecurity::e($request->domain); ?></h2>
<p><?php echo CentralVixMigSecurity::e($request->reference); ?> · <?php echo CentralVixMigSecurity::e($public['status_label']); ?></p>
</div>

<?php if ($timeline['failed']): ?>
<div class="cvix-mig-alert err">Esta migração foi <?php echo $request->status === 'cancelada' ? 'cancelada' : 'interrompida'; ?>. Entre em contato com o suporte.</div>
<?php endif; ?>

<?php if ($request->client_message): ?>
<div class="cvix-mig-alert ok"><?php echo nl2br(CentralVixMigSecurity::e($request->client_message)); ?></div>
<?php endif; ?>

<div class="cvix-mig-progress-card">
<div class="cvix-mig-progress-label"><span>Progresso geral</span><strong><?php echo (int)$request->progress_percent; ?>%</strong></div>
<div class="cvix-mig-progress cvix-mig-progress--lg"><div style="width:<?php echo (int)$request->progress_percent; ?>%"></div></div>
<p class="cvix-mig-muted">Checklist: <?php echo (int)$checklistSummary['done']; ?>/<?php echo (int)$checklistSummary['total']; ?> etapas concluídas pela equipe</p>
</div>

<ol class="cvix-mig-timeline">
<?php foreach ($timeline['steps'] as $i => $step):
    $state = 'pending';
    if ($i < $timeline['current']) {
        $state = 'done';
    } elseif ($i === $timeline['current'] && !$timeline['failed']) {
        $state = 'active';
    } elseif ($i === $timeline['current'] && $timeline['failed']) {
        $state = 'failed';
    }
?>
<li class="cvix-mig-timeline__item cvix-mig-timeline__item--<?php echo $state; ?>">
<span class="cvix-mig-timeline__dot"></span>
<span class="cvix-mig-timeline__label"><?php echo CentralVixMigSecurity::e($step['label']); ?></span>
</li>
<?php endforeach; ?>
</ol>

<div class="cvix-mig-split-client">
<div class="cvix-mig-panel">
<h3>Detalhes</h3>
<ul class="cvix-mig-details">
<li><strong>Painel origem:</strong> <?php echo CentralVixMigSecurity::e($public['current_panel']); ?></li>
<li><strong>Tipo:</strong> <?php echo CentralVixMigSecurity::e($public['migration_type']); ?></li>
<li><strong>Preferência:</strong> <?php echo $public['preferred_date'] ? date('d/m/Y', strtotime($public['preferred_date'])) : '—'; ?></li>
<li><strong>Agendada:</strong> <?php echo $public['scheduled_date'] ? date('d/m/Y H:i', strtotime($public['scheduled_date'])) : 'A confirmar'; ?></li>
</ul>
</div>
<div class="cvix-mig-panel">
<h3>Comunicação</h3>
<?php foreach ($comments as $c): ?>
<div class="cvix-mig-comment">
<strong><?php echo CentralVixMigSecurity::e($c->author_name); ?></strong>
<span class="cvix-mig-muted"><?php echo date('d/m/Y H:i', strtotime($c->created_at)); ?></span>
<p><?php echo nl2br(CentralVixMigSecurity::e($c->message)); ?></p>
</div>
<?php endforeach; ?>
<form id="cvix-mig-comment-form" class="cvix-mig-form" data-action="<?php echo CentralVixMigSecurity::e($ajaxBase.'comment'); ?>">
<input type="hidden" name="csrf_token" value="<?php echo CentralVixMigSecurity::e($csrf); ?>">
<input type="hidden" name="request_id" value="<?php echo (int)$requestId; ?>">
<textarea name="message" rows="3" placeholder="Sua mensagem..." required></textarea>
<button type="submit" class="cvix-mig-btn sm">Enviar</button>
</form>
</div>
</div>

<div class="cvix-mig-panel">
<h3>Anexos</h3>
<ul class="cvix-mig-files">
<?php foreach ($files as $f): ?>
<li><a href="<?php echo CentralVixMigSecurity::e($dl.(int)$f->id); ?>"><?php echo CentralVixMigSecurity::e($f->original_name); ?></a></li>
<?php endforeach; ?>
<?php if (!count($files)): ?><li class="cvix-mig-muted">Nenhum arquivo.</li><?php endif; ?>
</ul>
<form id="cvix-mig-upload-form" class="cvix-mig-form cvix-mig-inline" enctype="multipart/form-data" data-action="<?php echo CentralVixMigSecurity::e($ajaxBase.'upload'); ?>">
<input type="hidden" name="csrf_token" value="<?php echo CentralVixMigSecurity::e($csrf); ?>">
<input type="hidden" name="request_id" value="<?php echo (int)$requestId; ?>">
<select name="file_type"><option value="screenshot">Screenshot</option><option value="doc">Documentação</option></select>
<input type="file" name="file" required accept="image/*,.pdf,.txt,.zip">
<button type="submit" class="cvix-mig-btn sm">Anexar</button>
</form>
</div>

<p><a href="<?php echo CentralVixMigSecurity::e($modulelink); ?>">← Voltar</a></p>
