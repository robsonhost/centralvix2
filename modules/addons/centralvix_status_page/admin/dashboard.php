<?php if (!defined('WHMCS')) { die('This file cannot be accessed directly'); } ?>
<div class="cvix-sp-cards">
    <div class="cvix-sp-card"><span>Serviços monitorados</span><strong><?php echo (int)$stats['total']; ?></strong></div>
    <div class="cvix-sp-card ok"><span>Operacionais</span><strong><?php echo (int)$stats['operational']; ?></strong></div>
    <div class="cvix-sp-card warn"><span>Com problema</span><strong><?php echo (int)$stats['problems']; ?></strong></div>
    <div class="cvix-sp-card danger"><span>Incidentes ativos</span><strong><?php echo (int)$stats['incidents']; ?></strong></div>
    <div class="cvix-sp-card info"><span>Manutenções</span><strong><?php echo (int)$stats['maintenance']; ?></strong></div>
    <div class="cvix-sp-card"><span>Uptime médio</span><strong><?php echo CentralVixStatusSecurity::e($stats['avg_uptime']); ?>%</strong></div>
</div>

<div class="cvix-sp-panel">
    <h3>Status geral: <?php echo CentralVixStatusSecurity::e(CentralVixStatusModule::getStatuses()[$overall] ?? $overall); ?></h3>
    <p><?php echo CentralVixStatusSecurity::e(CentralVixStatusService::getOverallMessage()); ?></p>
</div>

<div class="cvix-sp-charts">
    <div class="cvix-sp-chart-box">
        <h4>Uptime / Resposta — 24h</h4>
        <canvas id="cvixChart24" height="120"></canvas>
    </div>
    <div class="cvix-sp-chart-box">
        <h4>7 dias</h4>
        <canvas id="cvixChart7" height="120"></canvas>
    </div>
    <div class="cvix-sp-chart-box">
        <h4>30 dias</h4>
        <canvas id="cvixChart30" height="120"></canvas>
    </div>
</div>
<script>
window.CVIX_CHART_24 = <?php echo json_encode($chart24); ?>;
window.CVIX_CHART_7 = <?php echo json_encode($chart7); ?>;
window.CVIX_CHART_30 = <?php echo json_encode($chart30); ?>;
</script>
