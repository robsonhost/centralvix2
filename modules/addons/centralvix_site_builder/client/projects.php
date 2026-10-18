<?php
if (!defined('WHMCS')) { die('This file cannot be accessed directly'); }
$projects = CentralVixSiteProjectService::listByClient($clientId);
include __DIR__ . '/_nav.php';
?>
<div class="cvix-sb-hero">
<h2>Seus projetos de site</h2>
<p>Acompanhe briefing, materiais, design, revisão e entrega.</p>
</div>
<div class="cvix-sb-project-cards">
<?php foreach ($projects as $p): ?>
<a class="cvix-sb-project-card" href="<?php echo CentralVixSiteSecurity::e($modulelink.'&action=project&id='.(int)$p->id); ?>">
<div class="cvix-sb-project-card-top">
<h3><?php echo CentralVixSiteSecurity::e($p->project_name); ?></h3>
<span class="cvix-sb-pill cvix-sb-pill--<?php echo CentralVixSiteSecurity::e($p->status); ?>"><?php echo CentralVixSiteSecurity::e(CentralVixSiteProjectService::statusLabel($p->status)); ?></span>
</div>
<div class="cvix-sb-progress lg"><div style="width:<?php echo (int)$p->progress_percent; ?>%"></div></div>
<ul class="cvix-sb-meta-list">
<li><span>Prazo</span><strong><?php echo $p->deadline ? date('d/m/Y', strtotime($p->deadline)) : 'A definir'; ?></strong></li>
<li><span>Atualizado</span><strong><?php echo $p->updated_at ? date('d/m/Y', strtotime($p->updated_at)) : date('d/m/Y', strtotime($p->created_at)); ?></strong></li>
<li><span>Próxima etapa</span><strong><?php echo CentralVixSiteSecurity::e(CentralVixSiteProjectService::nextStageHint($p->status)); ?></strong></li>
</ul>
</a>
<?php endforeach; ?>
</div>
<?php if (!count($projects)): ?>
<div class="cvix-sb-empty">Nenhum projeto ativo. Entre em contato com a CentralVix ou aguarde a abertura do seu pedido.</div>
<?php endif; ?>
