<?php if (!defined('WHMCS')) { die('This file cannot be accessed directly'); }
if (!$account): ?>
<p>Selecione uma conta.</p>
<?php return; endif;
$summary = CentralVixStreamingStatisticsService::summary($account->id, 7);
$history = CentralVixStreamingStatisticsService::history($account->id, 48);
?>
<div class="cvix-st-client-grid">
    <div class="cvix-st-client-card"><span>Pico 7d</span><strong><?php echo (int)$summary['peak_listeners']; ?></strong></div>
    <div class="cvix-st-client-card"><span>Média ouvintes</span><strong><?php echo CentralVixStreamingSecurity::e($summary['avg_listeners']); ?></strong></div>
    <div class="cvix-st-client-card"><span>Checks online</span><strong><?php echo (int)$summary['online_checks']; ?>/<?php echo (int)$summary['total_checks']; ?></strong></div>
    <div class="cvix-st-client-card"><span>Transferência</span><strong><?php echo number_format($summary['bytes_transfer']/1048576, 2); ?> MB</strong></div>
</div>
<div class="cvix-st-client-panel">
    <h3>Histórico (48h)</h3>
    <div class="cvix-st-sparkline">
        <?php foreach ($history as $h): ?>
        <span class="bar <?php echo $h->online?'on':'off'; ?>" style="height:<?php echo min(100, max(4, (int)$h->listeners)); ?>px" title="<?php echo date('d/m H:i', strtotime($h->recorded_at)); ?> — <?php echo (int)$h->listeners; ?> ouvintes"></span>
        <?php endforeach; ?>
    </div>
</div>
