<?php if (!defined('WHMCS')) { die('This file cannot be accessed directly'); }
$incidents = CentralVixStatusIncidentService::listIncidents(false, 100);
$allServices = CentralVixStatusService::listServices(false, false);
$editId = (int)($_GET['edit'] ?? 0);
$edit = $editId ? CentralVixStatusIncidentService::getIncident($editId) : null;
$updates = $edit ? CentralVixStatusIncidentService::getUpdates($editId) : [];
$affected = $edit ? json_decode($edit->affected_services, true) : [];
?>
<div class="cvix-sp-panel">
    <h3><?php echo $edit ? 'Incidente #'.(int)$edit->id : 'Novo incidente'; ?></h3>
    <form id="cvixIncidentForm" class="cvix-sp-form">
        <input type="hidden" name="token" value="<?php echo CentralVixStatusSecurity::e($csrfToken); ?>">
        <input type="hidden" name="id" value="<?php echo (int)($edit->id ?? 0); ?>">
        <label>Título<input name="title" required value="<?php echo CentralVixStatusSecurity::e($edit->title ?? ''); ?>"></label>
        <label>Descrição<textarea name="description" rows="3"><?php echo CentralVixStatusSecurity::e($edit->description ?? ''); ?></textarea></label>
        <div class="cvix-sp-grid">
            <label>Severidade<select name="severity"><?php foreach ($statuses as $k=>$v): if(!in_array($k,['operational','resolved'])): ?><option value="<?php echo $k; ?>" <?php echo ($edit->severity ?? '')===$k?'selected':''; ?>><?php echo CentralVixStatusSecurity::e($v); ?></option><?php endif; endforeach; ?></select></label>
            <label>Status<select name="status"><?php foreach ($statuses as $k=>$v): ?><option value="<?php echo $k; ?>" <?php echo ($edit->status ?? 'investigating')===$k?'selected':''; ?>><?php echo CentralVixStatusSecurity::e($v); ?></option><?php endforeach; ?></select></label>
            <label>Início<input type="datetime-local" name="started_at" value="<?php echo $edit ? date('Y-m-d\TH:i', strtotime($edit->started_at)) : date('Y-m-d\TH:i'); ?>"></label>
        </div>
        <label>Serviços afetados</label>
        <div class="cvix-sp-checkboxes">
            <?php foreach ($allServices as $s): ?>
            <label><input type="checkbox" name="affected_services[]" value="<?php echo (int)$s->id; ?>" <?php echo in_array($s->id, (array)$affected)?'checked':''; ?>> <?php echo CentralVixStatusSecurity::e($s->name); ?></label>
            <?php endforeach; ?>
        </div>
        <?php if ($edit): ?>
        <label>Atualização<textarea name="update_message" rows="2" placeholder="Nova atualização na timeline"></textarea></label>
        <?php endif; ?>
        <label><input type="checkbox" name="is_public" value="1" <?php echo !isset($edit) || $edit->is_public ? 'checked' : ''; ?>> Público</label>
        <button type="submit" class="cvix-sp-btn primary">Salvar incidente</button>
    </form>
    <?php if ($edit && count($updates)): ?>
    <div class="cvix-sp-timeline">
        <h4>Timeline</h4>
        <?php foreach ($updates as $u): ?>
        <div class="cvix-sp-timeline-item">
            <span class="time"><?php echo date('d/m/Y H:i', strtotime($u->created_at)); ?></span>
            <span class="cvix-sp-badge <?php echo CentralVixStatusModule::statusBadgeClass($u->status); ?>"><?php echo CentralVixStatusSecurity::e($statuses[$u->status] ?? $u->status); ?></span>
            <p><?php echo CentralVixStatusSecurity::e($u->message); ?></p>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<div class="cvix-sp-panel">
    <h3>Incidentes</h3>
    <table class="cvix-sp-table">
        <thead><tr><th>ID</th><th>Título</th><th>Status</th><th>Início</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($incidents as $i): ?>
        <tr>
            <td>#<?php echo (int)$i->id; ?></td>
            <td><?php echo CentralVixStatusSecurity::e($i->title); ?></td>
            <td><span class="cvix-sp-badge <?php echo CentralVixStatusModule::statusBadgeClass($i->status); ?>"><?php echo CentralVixStatusSecurity::e($statuses[$i->status] ?? $i->status); ?></span></td>
            <td><?php echo date('d/m/Y H:i', strtotime($i->started_at)); ?></td>
            <td><a href="<?php echo CentralVixStatusSecurity::e($modulelink.'&tab=incidents&edit='.$i->id); ?>" class="cvix-sp-btn sm">Ver</a></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
