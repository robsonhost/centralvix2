<?php
if (!defined('WHMCS')) { die('This file cannot be accessed directly'); }
$project = CentralVixSiteProjectService::get($projectId, $clientId);
if (!$project) { echo '<div class="cvix-sb-empty">Projeto não encontrado.</div>'; return; }
$timeline = CentralVixSiteTimelineService::listByProject($projectId, 15);
include __DIR__ . '/_nav.php';
?>
<div class="cvix-sb-project-header">
<div>
<h2><?php echo CentralVixSiteSecurity::e($project->project_name); ?></h2>
<p class="cvix-sb-muted"><?php echo CentralVixSiteSecurity::e($project->domain ?: 'Domínio a definir'); ?> · <?php echo CentralVixSiteSecurity::e($project->plan ?: 'Plano'); ?></p>
</div>
<span class="cvix-sb-pill cvix-sb-pill--<?php echo CentralVixSiteSecurity::e($project->status); ?>"><?php echo CentralVixSiteSecurity::e(CentralVixSiteProjectService::statusLabel($project->status)); ?></span>
</div>

<div class="cvix-sb-stats-row">
<div class="cvix-sb-stat"><span>Progresso</span><strong><?php echo (int)$project->progress_percent; ?>%</strong></div>
<div class="cvix-sb-stat"><span>Prazo</span><strong><?php echo $project->deadline ? date('d/m/Y', strtotime($project->deadline)) : '—'; ?></strong></div>
<div class="cvix-sb-stat"><span>Última atualização</span><strong><?php echo $project->updated_at ? date('d/m/Y H:i', strtotime($project->updated_at)) : '—'; ?></strong></div>
<div class="cvix-sb-stat"><span>Próxima etapa</span><strong><?php echo CentralVixSiteSecurity::e(CentralVixSiteProjectService::nextStageHint($project->status)); ?></strong></div>
</div>

<div class="cvix-sb-progress lg"><div style="width:<?php echo (int)$project->progress_percent; ?>%"></div></div>

<nav class="cvix-sb-subnav">
<a href="<?php echo CentralVixSiteSecurity::e($modulelink.'&action=project&id='.(int)$projectId); ?>" class="active">Visão geral</a>
<a href="<?php echo CentralVixSiteSecurity::e($modulelink.'&action=briefing&id='.(int)$projectId); ?>">Briefing</a>
<a href="<?php echo CentralVixSiteSecurity::e($modulelink.'&action=files&id='.(int)$projectId); ?>">Arquivos</a>
<a href="<?php echo CentralVixSiteSecurity::e($modulelink.'&action=approval&id='.(int)$projectId); ?>">Aprovação</a>
</nav>

<section class="cvix-sb-panel client">
<h3>Timeline</h3>
<ul class="cvix-sb-timeline client">
<?php foreach ($timeline as $t): $e = CentralVixSiteTimelineService::formatEntry($t); ?>
<li>
<div class="cvix-sb-timeline-dot"></div>
<div><strong><?php echo CentralVixSiteSecurity::e($e['title']); ?></strong>
<p><?php echo CentralVixSiteSecurity::e($e['description']); ?></p>
<span class="cvix-sb-muted"><?php echo $e['date']; ?> <?php echo $e['time']; ?> · <?php echo CentralVixSiteSecurity::e($e['actor_name']); ?></span></div>
</li>
<?php endforeach; ?>
</ul>
</section>
