<?php
if (!defined('WHMCS')) { die('This file cannot be accessed directly'); }
$project = CentralVixSiteProjectService::get($projectId, $clientId);
if (!$project) { echo '<div class="cvix-sb-empty">Projeto não encontrado.</div>'; return; }
$approvals = CentralVixSiteApprovalService::listByProject($projectId);
$canApprove = in_array($project->status, ['aguardando_cliente', 'em_revisao'], true);
$ajaxApprove = CentralVixSiteModule::ajaxUrl('approve', true);
include __DIR__ . '/_nav.php';
?>
<div class="cvix-sb-project-header"><h2>Aprovação — <?php echo CentralVixSiteSecurity::e($project->project_name); ?></h2></div>
<nav class="cvix-sb-subnav">
<a href="<?php echo CentralVixSiteSecurity::e($modulelink.'&action=project&id='.(int)$projectId); ?>">Visão geral</a>
<a href="<?php echo CentralVixSiteSecurity::e($modulelink.'&action=briefing&id='.(int)$projectId); ?>">Briefing</a>
<a href="<?php echo CentralVixSiteSecurity::e($modulelink.'&action=files&id='.(int)$projectId); ?>">Arquivos</a>
<a href="<?php echo CentralVixSiteSecurity::e($modulelink.'&action=approval&id='.(int)$projectId); ?>" class="active">Aprovação</a>
</nav>

<?php if ($canApprove): ?>
<section class="cvix-sb-panel client approval-box">
<h3>Sua decisão</h3>
<p>Etapa atual: <strong><?php echo CentralVixSiteSecurity::e(CentralVixSiteProjectService::statusLabel($project->status)); ?></strong></p>
<form id="cvix-sb-approval-form" class="cvix-sb-form">
<input type="hidden" name="csrf_token" value="<?php echo CentralVixSiteSecurity::e($csrf); ?>">
<input type="hidden" name="project_id" value="<?php echo (int)$projectId; ?>">
<input type="hidden" name="stage" value="<?php echo CentralVixSiteSecurity::e($project->current_stage); ?>">
<label>Comentário (obrigatório para alterações)<textarea name="message" rows="4" placeholder="Descreva aprovação ou alterações desejadas..."></textarea></label>
<div class="cvix-sb-form-actions">
<button type="button" class="cvix-sb-btn cvix-sb-approve" data-action="approve">Aprovar etapa</button>
<button type="button" class="cvix-sb-btn danger cvix-sb-approve" data-action="request_change">Solicitar alteração</button>
</div>
<span id="cvix-sb-approval-msg"></span>
</form>
</section>
<?php else: ?>
<div class="cvix-sb-info">Este projeto não está aguardando sua aprovação no momento. Status: <?php echo CentralVixSiteSecurity::e(CentralVixSiteProjectService::statusLabel($project->status)); ?></div>
<?php endif; ?>

<section class="cvix-sb-panel client">
<h3>Histórico de aprovações</h3>
<ul class="cvix-sb-timeline client">
<?php foreach ($approvals as $a): $fmt = CentralVixSiteApprovalService::formatRecord($a); ?>
<li>
<div class="cvix-sb-timeline-dot"></div>
<div><strong><?php echo CentralVixSiteSecurity::e($fmt['action_label']); ?></strong> — <?php echo CentralVixSiteSecurity::e($fmt['stage_label']); ?>
<p><?php echo CentralVixSiteSecurity::e($fmt['message'] ?: '—'); ?></p>
<span class="cvix-sb-muted"><?php echo $fmt['date']; ?> <?php echo $fmt['time']; ?> · IP <?php echo CentralVixSiteSecurity::e($fmt['ip_address']); ?></span></div>
</li>
<?php endforeach; ?>
<?php if (!count($approvals)): ?><li>Nenhum registro ainda.</li><?php endif; ?>
</ul>
</section>
<script>window.CVIX_SB_APPROVE = <?php echo json_encode($ajaxApprove); ?>;</script>
