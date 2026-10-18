<?php
if (!defined('WHMCS')) { die('This file cannot be accessed directly'); }
$project = CentralVixSiteProjectService::get($projectId);
if (!$project) { echo '<p>Projeto não encontrado.</p>'; return; }
$briefing = CentralVixSiteBriefingService::toArray(CentralVixSiteBriefingService::getByProject($projectId));
$files = CentralVixSiteFileService::listByProject($projectId);
$timeline = CentralVixSiteTimelineService::listByProject($projectId);
$approvals = CentralVixSiteApprovalService::listByProject($projectId);
$client = WHMCS\Database\Capsule::table('tblclients')->where('id', $project->client_id)->first();
$admins = WHMCS\Database\Capsule::table('tbladmins')->select('id', 'firstname', 'lastname')->get();
?>
<h2><?php echo CentralVixSiteSecurity::e($project->project_name); ?></h2>
<?php if (!empty($success)): ?><div class="cvix-sb-alert ok"><?php echo CentralVixSiteSecurity::e($success); ?></div><?php endif; ?>

<div class="cvix-sb-project-grid">
<section class="cvix-sb-panel">
<h3>Dados do projeto</h3>
<form method="post" class="cvix-sb-form">
<input type="hidden" name="csrf_token" value="<?php echo CentralVixSiteSecurity::e($csrf); ?>">
<input type="hidden" name="update_project" value="1">
<input type="hidden" name="project_id" value="<?php echo (int)$project->id; ?>">
<label>Nome<input type="text" name="project_name" value="<?php echo CentralVixSiteSecurity::e($project->project_name); ?>"></label>
<label>Cliente<strong><?php echo CentralVixSiteSecurity::e($client ? $client->firstname.' '.$client->lastname : ''); ?></strong></label>
<label>Produto<input type="text" name="product_name" value="<?php echo CentralVixSiteSecurity::e($project->product_name); ?>"></label>
<label>Domínio<input type="text" name="domain" value="<?php echo CentralVixSiteSecurity::e($project->domain); ?>"></label>
<label>Plano<input type="text" name="plan" value="<?php echo CentralVixSiteSecurity::e($project->plan); ?>"></label>
<label>Responsável<select name="assigned_admin">
<?php foreach ($admins as $a): ?><option value="<?php echo (int)$a->id; ?>" <?php echo (int)$project->assigned_admin===(int)$a->id?'selected':''; ?>><?php echo CentralVixSiteSecurity::e($a->firstname.' '.$a->lastname); ?></option><?php endforeach; ?>
</select></label>
<label>Início<input type="date" name="start_date" value="<?php echo CentralVixSiteSecurity::e($project->start_date); ?>"></label>
<label>Prazo<input type="date" name="deadline" value="<?php echo CentralVixSiteSecurity::e($project->deadline); ?>"></label>
<label>Progresso %<input type="number" name="progress_percent" min="0" max="100" value="<?php echo (int)$project->progress_percent; ?>"></label>
<button type="submit" class="cvix-sb-btn">Salvar</button>
</form>
</section>

<section class="cvix-sb-panel">
<h3>Alterar status</h3>
<form id="cvix-sb-status-form" class="cvix-sb-form">
<input type="hidden" name="csrf_token" value="<?php echo CentralVixSiteSecurity::e($csrf); ?>">
<input type="hidden" name="project_id" value="<?php echo (int)$project->id; ?>">
<label>Status<select name="status">
<?php foreach (CentralVixSiteProjectService::STATUSES as $k=>$l): ?>
<option value="<?php echo CentralVixSiteSecurity::e($k); ?>" <?php echo $project->status===$k?'selected':''; ?>><?php echo CentralVixSiteSecurity::e($l); ?></option>
<?php endforeach; ?>
</select></label>
<label>Observação<textarea name="note" rows="2"></textarea></label>
<button type="button" class="cvix-sb-btn cvix-sb-change-status">Atualizar status</button>
</form>
<div class="cvix-sb-progress"><div style="width:<?php echo (int)$project->progress_percent; ?>%"></div></div>
<p>Atual: <strong><?php echo CentralVixSiteSecurity::e(CentralVixSiteProjectService::statusLabel($project->status)); ?></strong></p>
</section>

<section class="cvix-sb-panel wide">
<h3>Briefing</h3>
<div class="cvix-sb-briefing-read">
<?php foreach (CentralVixSiteBriefingService::STANDARD_FIELDS as $k=>$label): ?>
<p><strong><?php echo CentralVixSiteSecurity::e($label); ?>:</strong> <?php echo nl2br(CentralVixSiteSecurity::e($briefing[$k] ?: '—')); ?></p>
<?php endforeach; ?>
<?php if ($briefing['submitted_at']): ?><p class="cvix-sb-muted">Enviado em <?php echo date('d/m/Y H:i', strtotime($briefing['submitted_at'])); ?></p><?php endif; ?>
</div>
</section>

<section class="cvix-sb-panel wide">
<h3>Arquivos</h3>
<form id="cvix-sb-admin-upload" class="cvix-sb-form inline" enctype="multipart/form-data">
<input type="hidden" name="csrf_token" value="<?php echo CentralVixSiteSecurity::e($csrf); ?>">
<input type="hidden" name="project_id" value="<?php echo (int)$project->id; ?>">
<select name="category"><?php foreach (CentralVixSiteFileService::CATEGORIES as $k=>$l): ?><option value="<?php echo $k; ?>"><?php echo CentralVixSiteSecurity::e($l); ?></option><?php endforeach; ?></select>
<input type="file" name="file" required>
<button type="button" class="cvix-sb-btn sm cvix-sb-upload-btn">Enviar</button>
</form>
<ul class="cvix-sb-file-list">
<?php foreach ($files as $f): ?>
<li>
<a href="<?php echo CentralVixSiteSecurity::e(CentralVixSiteModule::downloadUrl($f->id)); ?>"><?php echo CentralVixSiteSecurity::e($f->original_name); ?></a>
<span class="cvix-sb-muted"><?php echo CentralVixSiteSecurity::e(CentralVixSiteFileService::CATEGORIES[$f->file_category] ?? $f->file_category); ?> · <?php echo date('d/m/Y', strtotime($f->created_at)); ?></span>
</li>
<?php endforeach; ?>
</ul>
</section>

<section class="cvix-sb-panel wide">
<h3>Aprovações</h3>
<ul class="cvix-sb-timeline">
<?php foreach ($approvals as $a): $fmt = CentralVixSiteApprovalService::formatRecord($a); ?>
<li><strong><?php echo CentralVixSiteSecurity::e($fmt['action_label']); ?></strong> — <?php echo CentralVixSiteSecurity::e($fmt['stage_label']); ?><br>
<?php echo CentralVixSiteSecurity::e($fmt['message']); ?><br>
<span class="cvix-sb-muted"><?php echo $fmt['date']; ?> <?php echo $fmt['time']; ?> · IP <?php echo CentralVixSiteSecurity::e($fmt['ip_address']); ?></span></li>
<?php endforeach; ?>
<?php if (!count($approvals)): ?><li>Nenhuma aprovação registrada.</li><?php endif; ?>
</ul>
</section>

<section class="cvix-sb-panel wide">
<h3>Timeline</h3>
<ul class="cvix-sb-timeline">
<?php foreach ($timeline as $t): $e = CentralVixSiteTimelineService::formatEntry($t); ?>
<li><strong><?php echo CentralVixSiteSecurity::e($e['title']); ?></strong><br>
<?php echo CentralVixSiteSecurity::e($e['description']); ?><br>
<span class="cvix-sb-muted"><?php echo CentralVixSiteSecurity::e($e['actor_name']); ?> · <?php echo $e['date']; ?> <?php echo $e['time']; ?></span></li>
<?php endforeach; ?>
</ul>
</section>
</div>
<p><a href="<?php echo CentralVixSiteSecurity::e($modulelink.'&tab=projects'); ?>">← Voltar</a></p>
