<?php if (!defined('WHMCS')) { die('This file cannot be accessed directly'); }
$items = CentralVixStatusMaintenanceService::listAll(false, 100);
$allServices = CentralVixStatusService::listServices(false, false);
$editId = (int)($_GET['edit'] ?? 0);
$edit = $editId ? CentralVixStatusMaintenanceService::get($editId) : null;
$affected = $edit ? json_decode($edit->affected_services, true) : [];
?>
<div class="cvix-sp-panel">
    <h3><?php echo $edit ? 'Editar manutenção' : 'Nova manutenção programada'; ?></h3>
    <form id="cvixMaintenanceForm" class="cvix-sp-form">
        <input type="hidden" name="token" value="<?php echo CentralVixStatusSecurity::e($csrfToken); ?>">
        <input type="hidden" name="id" value="<?php echo (int)($edit->id ?? 0); ?>">
        <label>Título<input name="title" required value="<?php echo CentralVixStatusSecurity::e($edit->title ?? ''); ?>"></label>
        <label>Descrição<textarea name="description" rows="3"><?php echo CentralVixStatusSecurity::e($edit->description ?? ''); ?></textarea></label>
        <div class="cvix-sp-grid">
            <label>Início<input type="datetime-local" name="start_at" value="<?php echo $edit ? date('Y-m-d\TH:i', strtotime($edit->start_at)) : ''; ?>"></label>
            <label>Fim previsto<input type="datetime-local" name="end_at" value="<?php echo $edit ? date('Y-m-d\TH:i', strtotime($edit->end_at)) : ''; ?>"></label>
            <label>Status<select name="status">
                <?php
                $mStatuses = ['scheduled' => 'Agendada', 'in_progress' => 'Em andamento', 'completed' => 'Concluída', 'cancelled' => 'Cancelada'];
                foreach ($mStatuses as $mk => $ml):
                ?>
                <option value="<?php echo $mk; ?>" <?php echo ($edit->status ?? 'scheduled') === $mk ? 'selected' : ''; ?>><?php echo CentralVixStatusSecurity::e($ml); ?></option>
                <?php endforeach; ?>
            </select></label>
        </div>
        <label>Serviços afetados</label>
        <div class="cvix-sp-checkboxes">
            <?php foreach ($allServices as $s): ?>
            <label><input type="checkbox" name="affected_services[]" value="<?php echo (int)$s->id; ?>" <?php echo in_array($s->id, (array)$affected)?'checked':''; ?>> <?php echo CentralVixStatusSecurity::e($s->name); ?></label>
            <?php endforeach; ?>
        </div>
        <label><input type="checkbox" name="is_public" value="1" checked> Publicar na página pública</label>
        <button type="submit" class="cvix-sp-btn primary">Salvar</button>
    </form>
</div>

<div class="cvix-sp-panel">
    <h3>Manutenções</h3>
    <table class="cvix-sp-table">
        <thead><tr><th>Título</th><th>Início</th><th>Fim</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($items as $m): ?>
        <tr>
            <td><?php echo CentralVixStatusSecurity::e($m->title); ?></td>
            <td><?php echo date('d/m/Y H:i', strtotime($m->start_at)); ?></td>
            <td><?php echo date('d/m/Y H:i', strtotime($m->end_at)); ?></td>
            <td><?php echo CentralVixStatusSecurity::e($m->status); ?></td>
            <td><a href="<?php echo CentralVixStatusSecurity::e($modulelink.'&tab=maintenance&edit='.$m->id); ?>" class="cvix-sp-btn sm">Editar</a></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
