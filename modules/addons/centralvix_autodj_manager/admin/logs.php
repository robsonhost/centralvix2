<?php if (!defined('WHMCS')) { die('This file cannot be accessed directly'); } ?>
<div class="cvix-adj-panel">
    <h3>Logs</h3>
    <table class="cvix-adj-table">
        <thead><tr><th>Data</th><th>Conta</th><th>Cliente</th><th>Ação</th><th>Resultado</th><th>Detalhes</th></tr></thead>
        <tbody>
        <?php foreach ($logs as $l): ?>
        <tr>
            <td><?php echo date('d/m/Y H:i', strtotime($l->created_at)); ?></td>
            <td><?php echo (int)$l->account_id ?: '-'; ?></td>
            <td><?php echo (int)$l->client_id; ?></td>
            <td><?php echo CentralVixAutoDJSecurity::e($l->action); ?></td>
            <td><?php echo CentralVixAutoDJSecurity::e($l->result); ?></td>
            <td><?php echo CentralVixAutoDJSecurity::e($l->details); ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
