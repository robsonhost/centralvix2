<?php
if (!defined('WHMCS')) { die('This file cannot be accessed directly'); }
$p = CentralVixPmProjectService::get($projectId, $clientId);
if (!$p) { echo '<div class="cvix-pm-empty">Projeto não encontrado.</div>'; return; }
$milestones = CentralVixPmMilestoneService::listByProject($projectId, true);
$comments = CentralVixPmCommentService::listByProject($projectId, null, true);
$files = CentralVixPmFileService::listByProject($projectId, null, true);
$tasks = CentralVixPmTaskService::listByProject($projectId, true);
?>
<div class="cvix-pm-project-head">
<h2><?php echo CentralVixPmSecurity::e($p->project_name); ?></h2>
<span class="cvix-pm-pill"><?php echo CentralVixPmSecurity::e(CentralVixPmProjectService::statusLabel($p->status)); ?></span>
</div>
<div class="cvix-pm-progress lg"><div style="width:<?php echo (int)$p->progress_percent; ?>%"></div></div>
<nav class="cvix-pm-subnav">
<a href="<?php echo CentralVixPmSecurity::e($modulelink.'&action=project&id='.(int)$projectId); ?>" class="active">Visão geral</a>
<a href="<?php echo CentralVixPmSecurity::e($modulelink.'&action=tasks&id='.(int)$projectId); ?>">Tarefas</a>
</nav>
<div class="cvix-pm-stats">
<div><span>Progresso</span><strong><?php echo (int)$p->progress_percent; ?>%</strong></div>
<div><span>Prazo</span><strong><?php echo $p->deadline ? date('d/m/Y', strtotime($p->deadline)) : '—'; ?></strong></div>
<div><span>Atualizado</span><strong><?php echo $p->updated_at ? date('d/m/Y', strtotime($p->updated_at)) : '—'; ?></strong></div>
</div>
<?php if ($p->description): ?><p class="cvix-pm-desc"><?php echo nl2br(CentralVixPmSecurity::e($p->description)); ?></p><?php endif; ?>

<?php if (count($milestones)): ?>
<section class="cvix-pm-section"><h3>Marcos</h3><ul><?php foreach ($milestones as $m): ?><li><?php echo CentralVixPmSecurity::e($m->title); ?> — <?php echo $m->due_date ? date('d/m/Y', strtotime($m->due_date)) : '—'; ?></li><?php endforeach; ?></ul></section>
<?php endif; ?>

<section class="cvix-pm-section"><h3>Tarefas visíveis</h3>
<ul><?php foreach ($tasks as $t): if ((int)$t->parent_id) continue; ?>
<li><?php echo CentralVixPmSecurity::e($t->title); ?> — <?php echo CentralVixPmSecurity::e(CentralVixPmTaskService::statusLabel($t->status)); ?></li>
<?php endforeach; ?></ul></section>

<section class="cvix-pm-section"><h3>Comentários</h3>
<?php foreach ($comments as $c): ?><div class="cvix-pm-comment public"><strong><?php echo CentralVixPmSecurity::e($c->author_name); ?></strong><p><?php echo CentralVixPmSecurity::e($c->message); ?></p></div><?php endforeach; ?>
<form id="cvix-pm-client-comment" class="cvix-pm-form inline">
<input type="hidden" name="csrf_token" value="<?php echo CentralVixPmSecurity::e($csrf); ?>">
<input type="hidden" name="project_id" value="<?php echo (int)$projectId; ?>">
<input type="text" name="message" placeholder="Seu comentário..." required>
<button type="button" class="cvix-pm-btn sm cvix-pm-client-comment-btn">Enviar</button>
</form>
</section>

<section class="cvix-pm-section"><h3>Anexos</h3>
<ul><?php foreach ($files as $f): ?><li><a href="<?php echo CentralVixPmSecurity::e(CentralVixPmModule::downloadUrl($f->id, true)); ?>"><?php echo CentralVixPmSecurity::e($f->original_name); ?></a></li><?php endforeach; ?></ul>
<form id="cvix-pm-client-upload" enctype="multipart/form-data" class="cvix-pm-form inline">
<input type="hidden" name="csrf_token" value="<?php echo CentralVixPmSecurity::e($csrf); ?>">
<input type="hidden" name="project_id" value="<?php echo (int)$projectId; ?>">
<input type="file" name="file" required>
<button type="button" class="cvix-pm-btn sm cvix-pm-client-upload-btn">Enviar arquivo</button>
</form>
</section>
<script>window.CVIX_PM_CLIENT={comment:<?php echo json_encode(CentralVixPmModule::ajaxUrl('comment',true)); ?>,upload:<?php echo json_encode(CentralVixPmModule::ajaxUrl('upload',true)); ?>};</script>
