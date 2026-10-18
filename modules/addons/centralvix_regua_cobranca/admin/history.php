<?php if (!defined('WHMCS')) { die('This file cannot be accessed directly'); }
$rows = $history['rows'];
$total = $history['total'];
$pages = max(1, (int) ceil($total / 50));
?>
<div class="cvix-rc-panel">
    <h3>Filtros</h3>
    <form method="get" action="addonmodules.php" class="cvix-rc-form cvix-rc-filters">
        <input type="hidden" name="module" value="centralvix_regua_cobranca">
        <input type="hidden" name="tab" value="history">
        <div class="cvix-rc-grid">
            <label>Fatura ID<input type="number" name="invoice_id" value="<?php echo (int)$historyFilters['invoice_id']; ?>"></label>
            <label>Cliente ID<input type="number" name="client_id" value="<?php echo (int)$historyFilters['client_id']; ?>"></label>
            <label>Régua<select name="rule_id">
                <option value="0">Todas</option>
                <?php foreach ($rules as $r): ?>
                <option value="<?php echo (int)$r->id; ?>" <?php echo (int)$historyFilters['rule_id'] === (int)$r->id ? 'selected' : ''; ?>><?php echo CentralVixReguaSecurity::e($r->name); ?></option>
                <?php endforeach; ?>
            </select></label>
            <label>Canal<select name="channel">
                <option value="">Todos</option>
                <option value="email" <?php echo $historyFilters['channel'] === 'email' ? 'selected' : ''; ?>>E-mail</option>
                <option value="whatsapp" <?php echo $historyFilters['channel'] === 'whatsapp' ? 'selected' : ''; ?>>WhatsApp</option>
            </select></label>
            <label>Status<select name="status">
                <option value="">Todos</option>
                <option value="success" <?php echo $historyFilters['status'] === 'success' ? 'selected' : ''; ?>>Sucesso</option>
                <option value="failed" <?php echo $historyFilters['status'] === 'failed' ? 'selected' : ''; ?>>Falha</option>
            </select></label>
            <label>De<input type="date" name="date_from" value="<?php echo CentralVixReguaSecurity::e($historyFilters['date_from']); ?>"></label>
            <label>Até<input type="date" name="date_to" value="<?php echo CentralVixReguaSecurity::e($historyFilters['date_to']); ?>"></label>
        </div>
        <button type="submit" class="cvix-rc-btn primary">Filtrar</button>
    </form>
</div>

<div class="cvix-rc-panel">
    <h3>Histórico de disparos (<?php echo (int)$total; ?>)</h3>
    <table class="cvix-rc-table">
        <thead><tr><th>Data</th><th>Cliente</th><th>Fatura</th><th>Etapa</th><th>Canal</th><th>Status</th><th>Resposta</th></tr></thead>
        <tbody>
        <?php if (count($rows) === 0): ?>
        <tr><td colspan="7">Nenhum registro encontrado.</td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $l): ?>
        <tr>
            <td><?php echo date('d/m/Y H:i', strtotime($l->executed_at)); ?></td>
            <td><?php echo CentralVixReguaSecurity::e(trim($l->client_name) ?: '#' . $l->client_id); ?></td>
            <td>#<?php echo (int)$l->invoice_id; ?></td>
            <td><?php echo CentralVixReguaSecurity::e($l->rule_name ?: ($l->rule_id ? 'Régua #' . $l->rule_id : 'Pagamento')); ?></td>
            <td><?php echo CentralVixReguaSecurity::e($l->channel); ?></td>
            <td><span class="cvix-rc-badge <?php echo $l->status === 'success' ? 'ok' : 'danger'; ?>"><?php echo CentralVixReguaSecurity::e($l->status); ?></span></td>
            <td class="cvix-rc-response"><?php echo CentralVixReguaSecurity::e($l->response); ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php if ($pages > 1): ?>
    <p class="cvix-rc-pagination">Página <?php echo (int)$historyPage; ?> de <?php echo $pages; ?></p>
    <?php endif; ?>
</div>
