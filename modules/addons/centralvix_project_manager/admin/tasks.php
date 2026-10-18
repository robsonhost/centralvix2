<?php
if (!defined('WHMCS')) { die('This file cannot be accessed directly'); }
$projects = CentralVixPmProjectService::listAll();
$pid = $filterProject ?: (int) ($projects[0]->id ?? 0);
$board = $pid ? CentralVixPmTaskService::kanbanBoard($pid) : [];
$kanbanProjectId = $pid;
?>
<h2>Kanban</h2>
<form method="get" class="cvix-pm-form inline" action="addonmodules.php">
<input type="hidden" name="module" value="centralvix_project_manager">
<input type="hidden" name="tab" value="tasks">
<label>Projeto<select name="project_id" onchange="this.form.submit()">
<?php foreach ($projects as $p): ?><option value="<?php echo (int)$p->id; ?>" <?php echo $pid===(int)$p->id?'selected':''; ?>><?php echo CentralVixPmSecurity::e($p->project_name); ?></option><?php endforeach; ?>
</select></label>
</form>
<?php if ($pid): include __DIR__ . '/../templates/kanban_board.php'; else: ?><p>Selecione um projeto.</p><?php endif; ?>
