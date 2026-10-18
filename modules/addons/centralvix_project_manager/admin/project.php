<?php
if (!defined('WHMCS')) { die('This file cannot be accessed directly'); }
$p = CentralVixPmProjectService::get($projectId);
if (!$p) { echo '<p>Projeto não encontrado.</p>'; return; }
$client = WHMCS\Database\Capsule::table('tblclients')->where('id', $p->client_id)->first();
$admins = WHMCS\Database\Capsule::table('tbladmins')->select('id','firstname','lastname')->get();
$tasks = CentralVixPmTaskService::listByProject($projectId);
$board = CentralVixPmTaskService::kanbanBoard($projectId);
$milestones = CentralVixPmMilestoneService::listByProject($projectId);
$comments = CentralVixPmCommentService::listByProject($projectId);
$files = CentralVixPmFileService::listByProject($projectId);
$logs = CentralVixPmLogger::listByProject($projectId);
$hours = CentralVixPmTimeService::totalHours($projectId);
$kanbanProjectId = $projectId;
?>
<h2><?php echo CentralVixPmSecurity::e($p->project_name); ?></h2>
<?php if ($success): ?><div class="cvix-pm-alert ok"><?php echo CentralVixPmSecurity::e($success); ?></div><?php endif; ?>

<div class="cvix-pm-tabs">
<section class="cvix-pm-panel">
<h3>Projeto</h3>
<form method="post" class="cvix-pm-form">
<input type="hidden" name="csrf_token" value="<?php echo CentralVixPmSecurity::e($csrf); ?>">
<input type="hidden" name="update_project" value="1">
<input type="hidden" name="project_id" value="<?php echo (int)$p->id; ?>">
<label>Nome<input type="text" name="project_name" value="<?php echo CentralVixPmSecurity::e($p->project_name); ?>"></label>
<label>Cliente<strong><?php echo CentralVixPmSecurity::e($client ? $client->firstname.' '.$client->lastname : ''); ?></strong></label>
<label>Tipo<select name="project_type"><?php foreach (CentralVixPmProjectService::TYPES as $k=>$l): ?><option value="<?php echo $k; ?>" <?php echo $p->project_type===$k?'selected':''; ?>><?php echo CentralVixPmSecurity::e($l); ?></option><?php endforeach; ?></select></label>
<label>Status<select name="new_status"><?php foreach (CentralVixPmProjectService::STATUSES as $k=>$l): ?><option value="<?php echo $k; ?>" <?php echo $p->status===$k?'selected':''; ?>><?php echo CentralVixPmSecurity::e($l); ?></option><?php endforeach; ?></select></label>
<label>Service / Invoice / Ticket
<input type="number" name="service_id" value="<?php echo (int)$p->service_id; ?>" placeholder="Service" style="width:30%">
<input type="number" name="invoice_id" value="<?php echo (int)$p->invoice_id; ?>" placeholder="Invoice" style="width:30%">
<input type="number" name="ticket_id" value="<?php echo (int)$p->ticket_id; ?>" placeholder="Ticket" style="width:30%">
</label>
<label>Responsável<select name="assigned_admin"><?php foreach ($admins as $a): ?><option value="<?php echo (int)$a->id; ?>" <?php echo (int)$p->assigned_admin===(int)$a->id?'selected':''; ?>><?php echo CentralVixPmSecurity::e($a->firstname.' '.$a->lastname); ?></option><?php endforeach; ?></select></label>
<label>Prazo<input type="date" name="deadline" value="<?php echo CentralVixPmSecurity::e($p->deadline); ?>"></label>
<label>Progresso %<input type="number" name="progress_percent" min="0" max="100" value="<?php echo (int)$p->progress_percent; ?>"></label>
<label class="cvix-pm-check"><input type="checkbox" name="client_visible" value="1" <?php echo $p->client_visible?'checked':''; ?>> Visível ao cliente</label>
<button type="submit" class="cvix-pm-btn">Salvar</button>
</form>
<p class="cvix-pm-muted">Horas: <?php echo number_format($hours, 1, ',', '.'); ?>h · <a href="<?php echo CentralVixPmSecurity::e($modulelink.'&tab=tasks&project_id='.(int)$p->id); ?>">Abrir Kanban</a></p>
</section>

<section class="cvix-pm-panel wide">
<h3>Kanban</h3>
<?php $kanbanProjectId = $p->id; include __DIR__ . '/../templates/kanban_board.php'; ?>
</section>

<section class="cvix-pm-panel">
<h3>Nova tarefa</h3>
<form id="cvix-pm-new-task" class="cvix-pm-form">
<input type="hidden" name="csrf_token" value="<?php echo CentralVixPmSecurity::e($csrf); ?>">
<input type="hidden" name="project_id" value="<?php echo (int)$p->id; ?>">
<label>Título<input type="text" name="title" required></label>
<label>Prioridade<select name="priority"><?php foreach (CentralVixPmTaskService::PRIORITIES as $k=>$l): ?><option value="<?php echo $k; ?>"><?php echo CentralVixPmSecurity::e($l); ?></option><?php endforeach; ?></select></label>
<label>Prazo<input type="date" name="due_date"></label>
<label>Horas est.<input type="number" step="0.25" name="estimated_hours" min="0"></label>
<label>Visibilidade<select name="visibility"><option value="internal">Interna</option><option value="client">Visível ao cliente</option></select></label>
<button type="button" class="cvix-pm-btn cvix-pm-save-task">Criar tarefa</button>
</form>
</section>

<section class="cvix-pm-panel wide">
<h3>Marcos</h3>
<form method="post" class="cvix-pm-form inline">
<input type="hidden" name="csrf_token" value="<?php echo CentralVixPmSecurity::e($csrf); ?>">
<input type="hidden" name="save_milestone" value="1">
<input type="hidden" name="project_id" value="<?php echo (int)$p->id; ?>">
<input type="text" name="title" placeholder="Título do marco" required>
<input type="date" name="due_date">
<label class="cvix-pm-check"><input type="checkbox" name="client_visible" value="1"> Cliente vê</label>
<button type="submit" class="cvix-pm-btn sm">+ Marco</button>
</form>
<ul><?php foreach ($milestones as $m): ?><li><?php echo CentralVixPmSecurity::e($m->title); ?> — <?php echo $m->due_date ? date('d/m/Y', strtotime($m->due_date)) : '—'; ?><?php echo $m->completed_at ? ' ✓' : ''; ?></li><?php endforeach; ?></ul>
</section>

<section class="cvix-pm-panel">
<h3>Registrar horas</h3>
<form method="post" class="cvix-pm-form">
<input type="hidden" name="csrf_token" value="<?php echo CentralVixPmSecurity::e($csrf); ?>">
<input type="hidden" name="save_time" value="1">
<input type="hidden" name="project_id" value="<?php echo (int)$p->id; ?>">
<label>Horas<input type="number" step="0.25" name="hours" min="0.25" required></label>
<label>Tarefa ID<input type="number" name="task_id" min="0"></label>
<label>Data<input type="date" name="entry_date" value="<?php echo date('Y-m-d'); ?>"></label>
<label>Descrição<input type="text" name="description"></label>
<button type="submit" class="cvix-pm-btn sm">Registrar</button>
</form>
</section>

<section class="cvix-pm-panel wide">
<h3>Comentários</h3>
<form id="cvix-pm-comment-form" class="cvix-pm-form inline">
<input type="hidden" name="csrf_token" value="<?php echo CentralVixPmSecurity::e($csrf); ?>">
<input type="hidden" name="project_id" value="<?php echo (int)$p->id; ?>">
<input type="text" name="message" placeholder="Comentário..." required style="flex:1">
<select name="visibility"><option value="internal">Interno</option><option value="public">Público (cliente)</option></select>
<button type="button" class="cvix-pm-btn sm cvix-pm-send-comment">Enviar</button>
</form>
<div class="cvix-pm-comments"><?php foreach ($comments as $c): ?>
<div class="cvix-pm-comment <?php echo $c->visibility; ?>"><strong><?php echo CentralVixPmSecurity::e($c->author_name); ?></strong>
<span class="cvix-pm-tag"><?php echo $c->visibility==='public'?'Público':'Interno'; ?></span>
<p><?php echo CentralVixPmSecurity::e($c->message); ?></p></div>
<?php endforeach; ?></div>
</section>

<section class="cvix-pm-panel wide">
<h3>Anexos</h3>
<form id="cvix-pm-upload-form" enctype="multipart/form-data" class="cvix-pm-form inline">
<input type="hidden" name="csrf_token" value="<?php echo CentralVixPmSecurity::e($csrf); ?>">
<input type="hidden" name="project_id" value="<?php echo (int)$p->id; ?>">
<select name="visibility"><option value="internal">Interno</option><option value="client">Cliente</option></select>
<input type="file" name="file" required>
<button type="button" class="cvix-pm-btn sm cvix-pm-upload">Upload</button>
</form>
<ul><?php foreach ($files as $f): ?><li><a href="<?php echo CentralVixPmSecurity::e(CentralVixPmModule::downloadUrl($f->id)); ?>"><?php echo CentralVixPmSecurity::e($f->original_name); ?></a> <span class="cvix-pm-tag"><?php echo $f->visibility; ?></span></li><?php endforeach; ?></ul>
</section>

<section class="cvix-pm-panel wide">
<h3>Histórico</h3>
<ul class="cvix-pm-log"><?php foreach ($logs as $l): ?><li><?php echo date('d/m H:i', strtotime($l->created_at)); ?> — <?php echo CentralVixPmSecurity::e($l->message); ?></li><?php endforeach; ?></ul>
</section>
</div>
