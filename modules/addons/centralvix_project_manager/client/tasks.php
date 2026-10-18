<?php
if (!defined('WHMCS')) { die('This file cannot be accessed directly'); }
$p = CentralVixPmProjectService::get($projectId, $clientId);
if (!$p) { echo '<div class="cvix-pm-empty">Projeto não encontrado.</div>'; return; }
$tasks = CentralVixPmTaskService::listByProject($projectId, true);
?>
<div class="cvix-pm-project-head"><h2>Tarefas — <?php echo CentralVixPmSecurity::e($p->project_name); ?></h2></div>
<nav class="cvix-pm-subnav">
<a href="<?php echo CentralVixPmSecurity::e($modulelink.'&action=project&id='.(int)$projectId); ?>">Visão geral</a>
<a href="<?php echo CentralVixPmSecurity::e($modulelink.'&action=tasks&id='.(int)$projectId); ?>" class="active">Tarefas</a>
</nav>
<div class="cvix-pm-task-list client">
<?php foreach ($tasks as $t): if ((int)$t->parent_id) continue; ?>
<div class="cvix-pm-task-item">
<h4><?php echo CentralVixPmSecurity::e($t->title); ?></h4>
<span class="cvix-pm-pill sm"><?php echo CentralVixPmSecurity::e(CentralVixPmTaskService::statusLabel($t->status)); ?></span>
<?php if ($t->description): ?><p><?php echo nl2br(CentralVixPmSecurity::e($t->description)); ?></p><?php endif; ?>
<div class="cvix-pm-task-meta">
<span>Prazo: <?php echo $t->due_date ? date('d/m/Y', strtotime($t->due_date)) : '—'; ?></span>
<span>Progresso: <?php echo (int)$t->progress_percent; ?>%</span>
</div>
<?php $subs = CentralVixPmTaskService::subtasks($t->id); if (count($subs)): ?>
<ul class="cvix-pm-subtasks"><?php foreach ($subs as $s): if ($s->visibility!=='client') continue; ?><li><?php echo CentralVixPmSecurity::e($s->title); ?></li><?php endforeach; ?></ul>
<?php endif; ?>
</div>
<?php endforeach; ?>
<?php if (!count($tasks)): ?><div class="cvix-pm-empty">Nenhuma tarefa liberada.</div><?php endif; ?>
</div>
