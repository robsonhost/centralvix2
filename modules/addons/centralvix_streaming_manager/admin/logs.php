<?php if (!defined('WHMCS')) { die('This file cannot be accessed directly'); } ?>
<div class="cvix-st-panel">
    <h3>Logs de ações</h3>
    <table class="cvix-st-table">
        <thead><tr><th>Data</th><th>Conta</th><th>Ação</th><th>Resultado</th><th>Detalhes</th><th>IP</th></tr></thead>
        <tbody>
        <?php foreach ($logs as $l): ?>
        <tr>
            <td><?php echo date('d/m/Y H:i', strtotime($l->created_at)); ?></td>
            <td><?php echo (int)$l->account_id ?: '-'; ?></td>
            <td><?php echo CentralVixStreamingSecurity::e($l->action); ?></td>
            <td><?php echo CentralVixStreamingSecurity::e($l->result); ?></td>
            <td><?php echo CentralVixStreamingSecurity::e($l->details); ?></td>
            <td><?php echo CentralVixStreamingSecurity::e($l->ip_address); ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
