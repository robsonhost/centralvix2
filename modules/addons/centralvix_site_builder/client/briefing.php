<?php
if (!defined('WHMCS')) { die('This file cannot be accessed directly'); }
$project = CentralVixSiteProjectService::get($projectId, $clientId);
if (!$project) { echo '<div class="cvix-sb-empty">Projeto não encontrado.</div>'; return; }
$briefing = CentralVixSiteBriefingService::toArray(CentralVixSiteBriefingService::getByProject($projectId));
$customFields = CentralVixSiteBriefingService::getCustomFieldsConfig();
$ajaxSave = CentralVixSiteModule::ajaxUrl('save_briefing', true);
include __DIR__ . '/_nav.php';
?>
<div class="cvix-sb-project-header"><h2>Briefing — <?php echo CentralVixSiteSecurity::e($project->project_name); ?></h2></div>
<nav class="cvix-sb-subnav">
<a href="<?php echo CentralVixSiteSecurity::e($modulelink.'&action=project&id='.(int)$projectId); ?>">Visão geral</a>
<a href="<?php echo CentralVixSiteSecurity::e($modulelink.'&action=briefing&id='.(int)$projectId); ?>" class="active">Briefing</a>
<a href="<?php echo CentralVixSiteSecurity::e($modulelink.'&action=files&id='.(int)$projectId); ?>">Arquivos</a>
<a href="<?php echo CentralVixSiteSecurity::e($modulelink.'&action=approval&id='.(int)$projectId); ?>">Aprovação</a>
</nav>

<form id="cvix-sb-briefing-form" class="cvix-sb-form client">
<input type="hidden" name="csrf_token" value="<?php echo CentralVixSiteSecurity::e($csrf); ?>">
<input type="hidden" name="project_id" value="<?php echo (int)$projectId; ?>">
<?php foreach (CentralVixSiteBriefingService::STANDARD_FIELDS as $field=>$label):
    $isLong = in_array($field, ['description','objective','audience','pages','reference_sites','notes'], true);
?>
<label><?php echo CentralVixSiteSecurity::e($label); ?>
<?php if ($isLong): ?><textarea name="<?php echo $field; ?>" rows="3"><?php echo CentralVixSiteSecurity::e($briefing[$field]); ?></textarea>
<?php else: ?><input type="text" name="<?php echo $field; ?>" value="<?php echo CentralVixSiteSecurity::e($briefing[$field]); ?>"><?php endif; ?>
</label>
<?php endforeach; ?>
<?php foreach ($customFields as $cf): ?>
<label><?php echo CentralVixSiteSecurity::e($cf['label']); ?>
<input type="text" name="custom[<?php echo CentralVixSiteSecurity::e($cf['key']); ?>]" value="<?php echo CentralVixSiteSecurity::e($briefing['custom'][$cf['key']] ?? ''); ?>">
</label>
<?php endforeach; ?>
<div class="cvix-sb-form-actions">
<button type="button" class="cvix-sb-btn outline cvix-sb-save-briefing" data-submit="0">Salvar rascunho</button>
<button type="button" class="cvix-sb-btn cvix-sb-save-briefing" data-submit="1">Enviar briefing</button>
<span id="cvix-sb-briefing-msg"></span>
</div>
</form>
<script>window.CVIX_SB_BRIEFING_URL = <?php echo json_encode($ajaxSave); ?>;</script>
