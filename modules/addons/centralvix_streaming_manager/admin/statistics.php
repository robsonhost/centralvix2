<?php if (!defined('WHMCS')) { die('This file cannot be accessed directly'); } ?>
<div class="cvix-st-panel">
    <h3>Estatísticas globais</h3>
    <p>Coleta automática via cron (hook AfterCronJob). Retenção configurável nas settings do addon.</p>
    <button type="button" class="cvix-st-btn primary" id="cvixCollectStats">Coletar stats agora</button>
    <div id="cvixStatsResult"></div>
</div>
<div class="cvix-st-panel">
    <h3>Contas recentes</h3>
    <table class="cvix-st-table">
        <thead><tr><th>Conta</th><th>Servidor</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php $i=0; foreach ($accounts as $a): if ($i++ >= 20) break; ?>
        <tr>
            <td>#<?php echo (int)$a->id; ?> — <?php echo CentralVixStreamingSecurity::e($a->stream_username); ?></td>
            <td><?php echo CentralVixStreamingSecurity::e($a->server_name); ?></td>
            <td><?php echo CentralVixStreamingSecurity::e($a->status); ?></td>
            <td><button type="button" class="cvix-st-btn sm cvix-fetch-stats" data-id="<?php echo (int)$a->id; ?>">Stats</button></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
