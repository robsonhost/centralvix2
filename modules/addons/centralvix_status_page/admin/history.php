<?php if (!defined('WHMCS')) { die('This file cannot be accessed directly'); }
use WHMCS\Database\Capsule;
$page = max(1, (int)($_GET['page'] ?? 1));
$logs = Capsule::table('mod_centralvix_status_history')->orderBy('id','desc')->forPage($page, 50)->get();
$total = Capsule::table('mod_centralvix_status_history')->count();
?>
<div class="cvix-sp-panel">
    <h3>Histórico de ações</h3>
    <table class="cvix-sp-table">
        <thead><tr><th>Data</th><th>Admin</th><th>Ação</th><th>Serviço</th><th>IP</th><th>Resultado</th><th>Detalhes</th></tr></thead>
        <tbody>
        <?php foreach ($logs as $l): ?>
        <tr>
            <td><?php echo date('d/m/Y H:i', strtotime($l->created_at)); ?></td>
            <td>#<?php echo (int)$l->admin_id; ?></td>
            <td><?php echo CentralVixStatusSecurity::e($l->action); ?></td>
            <td><?php echo (int)$l->service_id ?: '-'; ?></td>
            <td><?php echo CentralVixStatusSecurity::e($l->ip_address); ?></td>
            <td><?php echo CentralVixStatusSecurity::e($l->result); ?></td>
            <td><?php echo CentralVixStatusSecurity::e($l->details); ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php if ($total > 50): ?>
    <p>Página <?php echo $page; ?> — <?php echo (int)$total; ?> registros</p>
    <?php endif; ?>
</div>
