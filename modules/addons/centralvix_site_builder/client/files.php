<?php
if (!defined('WHMCS')) { die('This file cannot be accessed directly'); }
$project = CentralVixSiteProjectService::get($projectId, $clientId);
if (!$project) { echo '<div class="cvix-sb-empty">Projeto não encontrado.</div>'; return; }
$files = CentralVixSiteFileService::listByProject($projectId);
$ajaxUpload = CentralVixSiteModule::ajaxUrl('upload', true);
$ajaxComment = CentralVixSiteModule::ajaxUrl('comment', true);
include __DIR__ . '/_nav.php';
?>
<div class="cvix-sb-project-header"><h2>Arquivos — <?php echo CentralVixSiteSecurity::e($project->project_name); ?></h2></div>
<nav class="cvix-sb-subnav">
<a href="<?php echo CentralVixSiteSecurity::e($modulelink.'&action=project&id='.(int)$projectId); ?>">Visão geral</a>
<a href="<?php echo CentralVixSiteSecurity::e($modulelink.'&action=briefing&id='.(int)$projectId); ?>">Briefing</a>
<a href="<?php echo CentralVixSiteSecurity::e($modulelink.'&action=files&id='.(int)$projectId); ?>" class="active">Arquivos</a>
<a href="<?php echo CentralVixSiteSecurity::e($modulelink.'&action=approval&id='.(int)$projectId); ?>">Aprovação</a>
</nav>

<section class="cvix-sb-panel client">
<h3>Enviar material</h3>
<form id="cvix-sb-upload-form" enctype="multipart/form-data" class="cvix-sb-form inline">
<input type="hidden" name="csrf_token" value="<?php echo CentralVixSiteSecurity::e($csrf); ?>">
<input type="hidden" name="project_id" value="<?php echo (int)$projectId; ?>">
<label>Categoria<select name="category"><?php foreach (CentralVixSiteFileService::CATEGORIES as $k=>$l): ?><option value="<?php echo $k; ?>"><?php echo CentralVixSiteSecurity::e($l); ?></option><?php endforeach; ?></select></label>
<input type="file" name="file" required>
<button type="button" class="cvix-sb-btn cvix-sb-upload-client">Upload</button>
</form>
</section>

<div class="cvix-sb-file-cards">
<?php foreach ($files as $f):
    $comments = CentralVixSiteFileService::commentsForFile($f->id);
?>
<div class="cvix-sb-file-card">
<div class="cvix-sb-file-card-head">
<a href="<?php echo CentralVixSiteSecurity::e(CentralVixSiteModule::downloadUrl($f->id, true)); ?>"><?php echo CentralVixSiteSecurity::e($f->original_name); ?></a>
<span><?php echo CentralVixSiteSecurity::e(CentralVixSiteFileService::CATEGORIES[$f->file_category] ?? ''); ?></span>
</div>
<div class="cvix-sb-comments" data-file-id="<?php echo (int)$f->id; ?>">
<?php foreach ($comments as $c): ?>
<div class="cvix-sb-comment"><strong><?php echo CentralVixSiteSecurity::e($c->author_name); ?></strong> · <?php echo date('d/m H:i', strtotime($c->created_at)); ?><p><?php echo CentralVixSiteSecurity::e($c->message); ?></p></div>
<?php endforeach; ?>
<form class="cvix-sb-comment-form">
<input type="hidden" name="csrf_token" value="<?php echo CentralVixSiteSecurity::e($csrf); ?>">
<input type="hidden" name="project_id" value="<?php echo (int)$projectId; ?>">
<input type="hidden" name="file_id" value="<?php echo (int)$f->id; ?>">
<input type="text" name="message" placeholder="Comentar..." required maxlength="500">
<button type="button" class="cvix-sb-btn sm cvix-sb-add-comment">Enviar</button>
</form>
</div>
</div>
<?php endforeach; ?>
</div>
<?php if (!count($files)): ?><div class="cvix-sb-empty">Nenhum arquivo enviado ainda.</div><?php endif; ?>
<script>window.CVIX_SB_UPLOAD = <?php echo json_encode($ajaxUpload); ?>; window.CVIX_SB_COMMENT = <?php echo json_encode($ajaxComment); ?>;</script>
