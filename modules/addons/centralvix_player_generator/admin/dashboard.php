<?php
if (!defined('WHMCS')) { die('This file cannot be accessed directly'); }
$stats = CentralVixPlayerService::stats();
$trackViews = CentralVixPlayerModule::dbSetting('track_views', '1') === '1';
?>
<div class="cvix-pg-cards">
<div class="cvix-pg-card"><span>Total players</span><strong><?php echo (int)$stats['total']; ?></strong></div>
<div class="cvix-pg-card"><span>Ativos</span><strong><?php echo (int)$stats['active']; ?></strong></div>
<div class="cvix-pg-card"><span>Clientes</span><strong><?php echo (int)$stats['clients']; ?></strong></div>
<?php if ($trackViews): ?>
<div class="cvix-pg-card"><span>Visualizações</span><strong><?php echo number_format((int)$stats['views'], 0, ',', '.'); ?></strong></div>
<?php endif; ?>
</div>
<p class="cvix-pg-muted">Gerencie players personalizados criados pelos clientes. Estatísticas de visualização <?php echo $trackViews ? 'habilitadas' : 'desabilitadas'; ?>.</p>
