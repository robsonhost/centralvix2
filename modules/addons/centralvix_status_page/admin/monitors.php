<?php if (!defined('WHMCS')) { die('This file cannot be accessed directly'); }
$services = CentralVixStatusService::listServices(false, true);
?>
<div class="cvix-sp-panel">
    <h3>Monitores ativos</h3>
    <p>Execute checagens manualmente ou aguarde o cron do WHMCS (hook AfterCronJob).</p>
    <button type="button" id="cvixRunAllChecks" class="cvix-sp-btn primary">Executar todos os monitores agora</button>
    <div id="cvixMonitorResults" class="cvix-sp-monitor-results"></div>
    <table class="cvix-sp-table">
        <thead><tr><th>Serviço</th><th>Tipo</th><th>Alvo</th><th>Status</th><th>Última RT</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($services as $s): ?>
        <tr>
            <td><?php echo CentralVixStatusSecurity::e($s->name); ?></td>
            <td><?php echo CentralVixStatusSecurity::e($monitorTypes[$s->monitor_type] ?? $s->monitor_type); ?></td>
            <td><?php echo CentralVixStatusSecurity::e($s->url ?: $s->host); ?></td>
            <td><span class="cvix-sp-badge <?php echo CentralVixStatusModule::statusBadgeClass($s->status); ?>"><?php echo CentralVixStatusSecurity::e($statuses[$s->status] ?? $s->status); ?></span></td>
            <td><?php echo (int)$s->response_time; ?>ms</td>
            <td><button type="button" class="cvix-sp-btn sm cvix-check-now" data-id="<?php echo (int)$s->id; ?>">Checar</button></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
