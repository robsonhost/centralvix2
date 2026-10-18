<?php
if (!defined('WHMCS')) { die('This file cannot be accessed directly'); }
$stats = CentralVixSiteProjectService::dashboardStats();
?>
<h2>Dashboard</h2>
<div class="cvix-sb-cards">
<div class="cvix-sb-card"><span>Projetos ativos</span><strong><?php echo (int)$stats['active']; ?></strong></div>
<div class="cvix-sb-card warn"><span>Aguardando cliente</span><strong><?php echo (int)$stats['awaitingClient']; ?></strong></div>
<div class="cvix-sb-card danger"><span>Atrasados</span><strong><?php echo (int)$stats['overdue']; ?></strong></div>
<div class="cvix-sb-card"><span>Em desenvolvimento</span><strong><?php echo (int)$stats['inDev']; ?></strong></div>
<div class="cvix-sb-card ok"><span>Aprovados</span><strong><?php echo (int)$stats['approved']; ?></strong></div>
<div class="cvix-sb-card"><span>Finalizados</span><strong><?php echo (int)$stats['finished']; ?></strong></div>
</div>
<?php
$recent = CentralVixSiteProjectService::listAll([], 8);
if (count($recent)): ?>
<h3>Projetos recentes</h3>
<table class="cvix-sb-table">
<thead><tr><th>Projeto</th><th>Cliente</th><th>Status</th><th>Prazo</th><th></th></tr></thead>
<tbody>
<?php foreach ($recent as $p): ?>
<tr class="<?php echo CentralVixSiteProjectService::isOverdue($p)?'overdue':''; ?>">
<td><?php echo CentralVixSiteSecurity::e($p->project_name); ?></td>
<td><?php echo CentralVixSiteSecurity::e($p->client_name); ?></td>
<td><span class="cvix-sb-status cvix-sb-status--<?php echo CentralVixSiteSecurity::e($p->status); ?>"><?php echo CentralVixSiteSecurity::e(CentralVixSiteProjectService::statusLabel($p->status)); ?></span></td>
<td><?php echo $p->deadline ? date('d/m/Y', strtotime($p->deadline)) : '—'; ?></td>
<td><a href="<?php echo CentralVixSiteSecurity::e($modulelink.'&tab=project&id='.(int)$p->id); ?>">Abrir</a></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>
