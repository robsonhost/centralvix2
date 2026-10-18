<?php if (!defined('WHMCS')) { die('This file cannot be accessed directly'); } ?>
<div class="cvix-rc-cards">
    <div class="cvix-rc-card"><span>Faturas vencendo (7d)</span><strong><?php echo (int)$stats['due_soon']; ?></strong></div>
    <div class="cvix-rc-card warn"><span>Faturas vencidas</span><strong><?php echo (int)$stats['overdue']; ?></strong></div>
    <div class="cvix-rc-card"><span>Mensagens hoje</span><strong><?php echo (int)$stats['messages_today']; ?></strong></div>
    <div class="cvix-rc-card ok"><span>Sucesso hoje</span><strong><?php echo (int)$stats['success_today']; ?></strong></div>
    <div class="cvix-rc-card danger"><span>Falhas hoje</span><strong><?php echo (int)$stats['failed_today']; ?></strong></div>
    <div class="cvix-rc-card info"><span>Valores em aberto</span><strong>R$ <?php echo number_format($stats['open_amount'], 2, ',', '.'); ?></strong></div>
    <div class="cvix-rc-card ok"><span>Clientes recuperados</span><strong><?php echo (int)$stats['recovered_clients']; ?></strong></div>
</div>

<div class="cvix-rc-panel">
    <h3>Execução automática</h3>
    <p>Última execução: <strong><?php echo CentralVixReguaSecurity::e($lastExecution); ?></strong></p>
    <p>Janela global: <?php echo CentralVixReguaSecurity::e($settings['global_time_from'] ?? '08:00'); ?> — <?php echo CentralVixReguaSecurity::e($settings['global_time_to'] ?? '20:00'); ?></p>
    <p>Réguas ativas: <?php echo count(array_filter($rules, function ($r) { return $r->active; })); ?> de <?php echo count($rules); ?></p>
</div>

<div class="cvix-rc-panel">
    <h3>Réguas configuradas</h3>
    <table class="cvix-rc-table">
        <thead><tr><th>Etapa</th><th>Timing</th><th>Canais</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($rules as $r): ?>
        <tr>
            <td><?php echo CentralVixReguaSecurity::e($r->name); ?></td>
            <td><?php echo CentralVixReguaSecurity::e(CentralVixReguaBillingRuleService::timingLabel($r)); ?></td>
            <td><?php echo ($r->send_email ? 'E-mail ' : '') . ($r->send_whatsapp ? 'WhatsApp' : ''); ?></td>
            <td><?php echo $r->active ? '<span class="cvix-rc-badge ok">Ativa</span>' : '<span class="cvix-rc-badge muted">Inativa</span>'; ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
