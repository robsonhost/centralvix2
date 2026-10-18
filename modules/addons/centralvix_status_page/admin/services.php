<?php if (!defined('WHMCS')) { die('This file cannot be accessed directly'); }
$services = CentralVixStatusService::listServices(false, false);
$editId = (int)($_GET['edit'] ?? 0);
$edit = $editId ? CentralVixStatusService::getService($editId) : null;
?>
<div class="cvix-sp-panel">
    <h3><?php echo $edit ? 'Editar serviço' : 'Novo serviço'; ?></h3>
    <form id="cvixServiceForm" class="cvix-sp-form">
        <input type="hidden" name="token" value="<?php echo CentralVixStatusSecurity::e($csrfToken); ?>">
        <input type="hidden" name="id" value="<?php echo (int)($edit->id ?? 0); ?>">
        <div class="cvix-sp-grid">
            <label>Nome<input name="name" required value="<?php echo CentralVixStatusSecurity::e($edit->name ?? ''); ?>"></label>
            <label>Categoria<select name="category"><?php foreach ($categories as $k=>$v): ?><option value="<?php echo $k; ?>" <?php echo ($edit->category ?? '')===$k?'selected':''; ?>><?php echo CentralVixStatusSecurity::e($v); ?></option><?php endforeach; ?></select></label>
            <label>Tipo monitor<select name="monitor_type"><?php foreach ($monitorTypes as $k=>$v): ?><option value="<?php echo $k; ?>" <?php echo ($edit->monitor_type ?? 'https')===$k?'selected':''; ?>><?php echo CentralVixStatusSecurity::e($v); ?></option><?php endforeach; ?></select></label>
            <label>Host<input name="host" value="<?php echo CentralVixStatusSecurity::e($edit->host ?? ''); ?>"></label>
            <label>Porta<input name="port" type="number" value="<?php echo (int)($edit->port ?? 0); ?>"></label>
            <label>URL<input name="url" value="<?php echo CentralVixStatusSecurity::e($edit->url ?? ''); ?>"></label>
            <label>HTTP esperado<input name="expected_http_code" type="number" value="<?php echo (int)($edit->expected_http_code ?? 200); ?>"></label>
            <label>Timeout (s)<input name="timeout" type="number" value="<?php echo (int)($edit->timeout ?? 10); ?>"></label>
            <label>Ordem<input name="display_order" type="number" value="<?php echo (int)($edit->display_order ?? 0); ?>"></label>
            <label>Status<select name="status"><?php foreach ($statuses as $k=>$v): ?><option value="<?php echo $k; ?>" <?php echo ($edit->status ?? 'operational')===$k?'selected':''; ?>><?php echo CentralVixStatusSecurity::e($v); ?></option><?php endforeach; ?></select></label>
        </div>
        <label>Descrição<textarea name="description" rows="2"><?php echo CentralVixStatusSecurity::e($edit->description ?? ''); ?></textarea></label>
        <label><input type="checkbox" name="is_public" value="1" <?php echo !isset($edit) || $edit->is_public ? 'checked' : ''; ?>> Público</label>
        <label><input type="checkbox" name="enabled" value="1" <?php echo !isset($edit) || $edit->enabled ? 'checked' : ''; ?>> Ativo</label>
        <button type="submit" class="cvix-sp-btn primary">Salvar serviço</button>
    </form>
</div>

<div class="cvix-sp-panel">
    <h3>Serviços cadastrados</h3>
    <table class="cvix-sp-table">
        <thead><tr><th>Nome</th><th>Categoria</th><th>Status</th><th>Uptime</th><th>RT</th><th>Ações</th></tr></thead>
        <tbody>
        <?php foreach ($services as $s): ?>
        <tr>
            <td><?php echo CentralVixStatusSecurity::e($s->name); ?></td>
            <td><?php echo CentralVixStatusSecurity::e($categories[$s->category] ?? $s->category); ?></td>
            <td><span class="cvix-sp-badge <?php echo CentralVixStatusModule::statusBadgeClass($s->status); ?>"><?php echo CentralVixStatusSecurity::e($statuses[$s->status] ?? $s->status); ?></span></td>
            <td><?php echo CentralVixStatusSecurity::e($s->uptime_percentage); ?>%</td>
            <td><?php echo (int)$s->response_time; ?>ms</td>
            <td>
                <a href="<?php echo CentralVixStatusSecurity::e($modulelink.'&tab=services&edit='.$s->id); ?>" class="cvix-sp-btn sm">Editar</a>
                <button type="button" class="cvix-sp-btn sm cvix-check-now" data-id="<?php echo (int)$s->id; ?>">Checar</button>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
