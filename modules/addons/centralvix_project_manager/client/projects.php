<?php
if (!defined('WHMCS')) { die('This file cannot be accessed directly'); }
$projects = CentralVixPmProjectService::listByClient($clientId);
?>
<div class="cvix-pm-hero"><h2>Meus Projetos</h2><p>Acompanhe etapas, tarefas e entregas liberadas pela CentralVix.</p></div>
<div class="cvix-pm-cards client">
<?php foreach ($projects as $p): ?>
<a class="cvix-pm-card-link" href="<?php echo CentralVixPmSecurity::e($modulelink.'&action=project&id='.(int)$p->id); ?>">
<h3><?php echo CentralVixPmSecurity::e($p->project_name); ?></h3>
<span class="cvix-pm-pill"><?php echo CentralVixPmSecurity::e(CentralVixPmProjectService::statusLabel($p->status)); ?></span>
<div class="cvix-pm-progress"><div style="width:<?php echo (int)$p->progress_percent; ?>%"></div></div>
<p><?php echo CentralVixPmSecurity::e(CentralVixPmProjectService::typeLabel($p->project_type)); ?> · Prazo: <?php echo $p->deadline ? date('d/m/Y', strtotime($p->deadline)) : '—'; ?></p>
</a>
<?php endforeach; ?>
</div>
<?php if (!count($projects)): ?><div class="cvix-pm-empty">Nenhum projeto disponível para você.</div><?php endif; ?>
