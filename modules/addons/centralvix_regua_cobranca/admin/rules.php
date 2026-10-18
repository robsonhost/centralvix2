<?php if (!defined('WHMCS')) { die('This file cannot be accessed directly'); }
$editId = (int)($_GET['edit'] ?? 0);
$edit = $editId ? CentralVixReguaBillingRuleService::get($editId) : null;
$editProducts = $edit ? json_decode($edit->product_ids ?: '[]', true) : [];
$editProductGroups = $edit ? json_decode($edit->product_group_ids ?: '[]', true) : [];
$editClients = $edit ? json_decode($edit->client_ids ?: '[]', true) : [];
$editClientGroups = $edit ? json_decode($edit->client_group_ids ?: '[]', true) : [];
?>
<div class="cvix-rc-panel">
    <h3><?php echo $edit ? 'Editar régua' : 'Nova régua'; ?></h3>
    <form id="cvixRuleForm" class="cvix-rc-form">
        <input type="hidden" name="token" value="<?php echo CentralVixReguaSecurity::e($csrfToken); ?>">
        <input type="hidden" name="id" value="<?php echo (int)($edit->id ?? 0); ?>">
        <div class="cvix-rc-grid">
            <label>Nome<input name="name" required value="<?php echo CentralVixReguaSecurity::e($edit->name ?? ''); ?>"></label>
            <label>Dias relativos<input type="number" name="days_relative" min="0" value="<?php echo (int)($edit->days_relative ?? 0); ?>"></label>
            <label>Antes / no / depois<select name="timing">
                <option value="before" <?php echo ($edit->timing ?? 'before') === 'before' ? 'selected' : ''; ?>>Antes do vencimento</option>
                <option value="on" <?php echo ($edit->timing ?? '') === 'on' ? 'selected' : ''; ?>>No vencimento</option>
                <option value="after" <?php echo ($edit->timing ?? '') === 'after' ? 'selected' : ''; ?>>Depois do vencimento</option>
            </select></label>
            <label>Status fatura<input name="invoice_status" value="<?php echo CentralVixReguaSecurity::e($edit->invoice_status ?? 'Unpaid'); ?>" placeholder="Unpaid, Payment Pending"></label>
            <label>Template<select name="template_id" required>
                <option value="">Selecione</option>
                <?php foreach ($templates as $t): ?>
                <option value="<?php echo (int)$t->id; ?>" <?php echo (int)($edit->template_id ?? 0) === (int)$t->id ? 'selected' : ''; ?>><?php echo CentralVixReguaSecurity::e($t->name); ?></option>
                <?php endforeach; ?>
            </select></label>
            <label>Horário início<input type="time" name="time_from" value="<?php echo CentralVixReguaSecurity::e(substr($edit->time_from ?? '08:00:00', 0, 5)); ?>"></label>
            <label>Horário fim<input type="time" name="time_to" value="<?php echo CentralVixReguaSecurity::e(substr($edit->time_to ?? '20:00:00', 0, 5)); ?>"></label>
            <label>Ordem<input type="number" name="display_order" value="<?php echo (int)($edit->display_order ?? 0); ?>"></label>
        </div>
        <div class="cvix-rc-checks">
            <label><input type="checkbox" name="send_email" value="1" <?php echo !isset($edit) || $edit->send_email ? 'checked' : ''; ?>> Enviar e-mail</label>
            <label><input type="checkbox" name="send_whatsapp" value="1" <?php echo !empty($edit->send_whatsapp) ? 'checked' : ''; ?>> Enviar WhatsApp</label>
            <label><input type="checkbox" name="active" value="1" <?php echo !isset($edit) || $edit->active ? 'checked' : ''; ?>> Régua ativa</label>
        </div>
        <label>Grupos de produtos</label>
        <div class="cvix-rc-checkboxes">
            <?php foreach ($productGroups as $pg): ?>
            <label><input type="checkbox" name="product_group_ids[]" value="<?php echo (int)$pg->id; ?>" <?php echo in_array($pg->id, (array)$editProductGroups) ? 'checked' : ''; ?>> <?php echo CentralVixReguaSecurity::e($pg->name); ?></label>
            <?php endforeach; ?>
        </div>
        <label>Produtos específicos</label>
        <div class="cvix-rc-checkboxes">
            <?php foreach ($products as $p): ?>
            <label><input type="checkbox" name="product_ids[]" value="<?php echo (int)$p->id; ?>" <?php echo in_array($p->id, (array)$editProducts) ? 'checked' : ''; ?>> <?php echo CentralVixReguaSecurity::e($p->name); ?></label>
            <?php endforeach; ?>
        </div>
        <label>Grupos de clientes</label>
        <div class="cvix-rc-checkboxes">
            <?php foreach ($clientGroups as $cg): ?>
            <label><input type="checkbox" name="client_group_ids[]" value="<?php echo (int)$cg->id; ?>" <?php echo in_array($cg->id, (array)$editClientGroups) ? 'checked' : ''; ?>> <?php echo CentralVixReguaSecurity::e($cg->groupname); ?></label>
            <?php endforeach; ?>
        </div>
        <label>IDs clientes (separados por vírgula)<input name="client_ids_raw" value="<?php echo CentralVixReguaSecurity::e(implode(',', (array)$editClients)); ?>" placeholder="Ex: 1,5,10"></label>
        <button type="submit" class="cvix-rc-btn primary">Salvar régua</button>
    </form>
</div>

<div class="cvix-rc-panel">
    <h3>Réguas cadastradas</h3>
    <table class="cvix-rc-table">
        <thead><tr><th>Nome</th><th>Timing</th><th>Canais</th><th>Horário</th><th>Status</th><th>Ações</th></tr></thead>
        <tbody>
        <?php foreach ($rules as $r): ?>
        <tr>
            <td><?php echo CentralVixReguaSecurity::e($r->name); ?></td>
            <td><?php echo CentralVixReguaSecurity::e(CentralVixReguaBillingRuleService::timingLabel($r)); ?></td>
            <td><?php echo ($r->send_email ? 'E-mail ' : '') . ($r->send_whatsapp ? 'WA' : ''); ?></td>
            <td><?php echo substr($r->time_from, 0, 5) . ' - ' . substr($r->time_to, 0, 5); ?></td>
            <td><?php echo $r->active ? '<span class="cvix-rc-badge ok">Ativa</span>' : '<span class="cvix-rc-badge muted">Off</span>'; ?></td>
            <td class="cvix-rc-actions">
                <a href="<?php echo CentralVixReguaSecurity::e($modulelink . '&tab=rules&edit=' . $r->id); ?>" class="cvix-rc-btn sm">Editar</a>
                <button type="button" class="cvix-rc-btn sm cvix-toggle-rule" data-id="<?php echo (int)$r->id; ?>">Ativar/Off</button>
                <button type="button" class="cvix-rc-btn sm danger cvix-delete-rule" data-id="<?php echo (int)$r->id; ?>" data-name="<?php echo CentralVixReguaSecurity::e($r->name); ?>">Excluir</button>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div id="cvixConfirmModal" class="cvix-rc-modal hidden">
    <div class="cvix-rc-modal-box">
        <h4>Confirmar exclusão</h4>
        <p id="cvixConfirmText"></p>
        <div class="cvix-rc-modal-actions">
            <button type="button" class="cvix-rc-btn" id="cvixConfirmCancel">Cancelar</button>
            <button type="button" class="cvix-rc-btn danger" id="cvixConfirmOk">Excluir</button>
        </div>
    </div>
</div>
