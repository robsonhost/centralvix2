<?php if (!defined('WHMCS')) { die('This file cannot be accessed directly'); }
if (!isset($kanbanProjectId)) return;
$labels = CentralVixPmTaskService::STATUSES;
?>
<div class="cvix-pm-kanban" data-project-id="<?php echo (int)$kanbanProjectId; ?>">
<?php foreach (CentralVixPmTaskService::KANBAN_COLUMNS as $col): ?>
<div class="cvix-pm-kanban-col" data-status="<?php echo CentralVixPmSecurity::e($col); ?>">
<h4><?php echo CentralVixPmSecurity::e($labels[$col]); ?></h4>
<div class="cvix-pm-kanban-list" data-drop="<?php echo CentralVixPmSecurity::e($col); ?>">
<?php foreach ($board[$col] ?? [] as $t): ?>
<div class="cvix-pm-kanban-card" draggable="true" data-task-id="<?php echo (int)$t->id; ?>">
<strong><?php echo CentralVixPmSecurity::e($t->title); ?></strong>
<span class="cvix-pm-pri cvix-pm-pri--<?php echo CentralVixPmSecurity::e($t->priority); ?>"><?php echo CentralVixPmSecurity::e(CentralVixPmTaskService::priorityLabel($t->priority)); ?></span>
<?php if ($t->visibility==='client'): ?><span class="cvix-pm-tag">Cliente</span><?php endif; ?>
<?php if ($t->due_date): ?><small>Prazo: <?php echo date('d/m', strtotime($t->due_date)); ?></small><?php endif; ?>
</div>
<?php endforeach; ?>
</div>
</div>
<?php endforeach; ?>
</div>
