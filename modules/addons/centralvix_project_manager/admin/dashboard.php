<?php if (!defined('WHMCS')) { die('This file cannot be accessed directly'); }
$s = CentralVixPmProjectService::dashboardStats();
?>
<h2>Dashboard</h2>
<div class="cvix-pm-cards">
<div class="cvix-pm-card"><span>Projetos ativos</span><strong><?php echo (int)$s['active']; ?></strong></div>
<div class="cvix-pm-card warn"><span>Atrasados</span><strong><?php echo (int)$s['overdue']; ?></strong></div>
<div class="cvix-pm-card"><span>Tarefas abertas</span><strong><?php echo (int)$s['openTasks']; ?></strong></div>
<div class="cvix-pm-card"><span>Aguardando cliente</span><strong><?php echo (int)$s['awaiting']; ?></strong></div>
<div class="cvix-pm-card ok"><span>Concluídos</span><strong><?php echo (int)$s['completed']; ?></strong></div>
<div class="cvix-pm-card"><span>Horas registradas</span><strong><?php echo number_format($s['hours'], 1, ',', '.'); ?></strong></div>
</div>
