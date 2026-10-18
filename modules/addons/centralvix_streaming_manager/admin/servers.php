<?php if (!defined('WHMCS')) { die('This file cannot be accessed directly'); }
$editId = (int)($_GET['edit'] ?? 0);
$edit = $editId ? CentralVixStreamingService::getServer($editId) : null;
?>
<div class="cvix-st-panel">
    <h3><?php echo $edit ? 'Editar servidor' : 'Novo servidor'; ?></h3>
    <form id="cvixServerForm" class="cvix-st-form">
        <input type="hidden" name="token" value="<?php echo CentralVixStreamingSecurity::e($csrfToken); ?>">
        <input type="hidden" name="id" value="<?php echo (int)($edit->id ?? 0); ?>">
        <div class="cvix-st-grid">
            <label>Nome<input name="name" required value="<?php echo CentralVixStreamingSecurity::e($edit->name ?? ''); ?>"></label>
            <label>Tipo<select name="type"><?php foreach ($serverTypes as $k=>$v): ?><option value="<?php echo $k; ?>" <?php echo ($edit->type ?? 'icecast')===$k?'selected':''; ?>><?php echo CentralVixStreamingSecurity::e($v); ?></option><?php endforeach; ?></select></label>
            <label>Hostname/IP<input name="hostname" required value="<?php echo CentralVixStreamingSecurity::e($edit->hostname ?? ''); ?>"></label>
            <label>Porta API<input type="number" name="api_port" value="<?php echo (int)($edit->api_port ?? 8000); ?>"></label>
            <label>Porta stream<input type="number" name="stream_port" value="<?php echo (int)($edit->stream_port ?? 8000); ?>"></label>
            <label>Usuário admin<input name="api_user" value="<?php echo CentralVixStreamingSecurity::e($edit->api_user ?? 'admin'); ?>"></label>
            <label>Senha/token<input type="password" name="api_password" value="<?php echo $edit ? '********' : ''; ?>" autocomplete="new-password"></label>
            <label>Capacidade<input type="number" name="capacity" value="<?php echo (int)($edit->capacity ?? 100); ?>"></label>
            <label>Localização<input name="location" value="<?php echo CentralVixStreamingSecurity::e($edit->location ?? ''); ?>"></label>
            <label>URL provisionamento (webhook)<input name="provisioning_url" value="<?php echo CentralVixStreamingSecurity::e($edit->provisioning_url ?? ''); ?>" placeholder="https://painel/api/stream"></label>
        </div>
        <label><input type="checkbox" name="use_ssl" value="1" <?php echo !empty($edit->use_ssl)?'checked':''; ?>> SSL</label>
        <label><input type="checkbox" name="active" value="1" <?php echo !isset($edit) || $edit->active ? 'checked' : ''; ?>> Ativo</label>
        <button type="submit" class="cvix-st-btn primary">Salvar servidor</button>
        <?php if ($edit): ?><button type="button" class="cvix-st-btn cvix-test-server" data-id="<?php echo (int)$edit->id; ?>">Testar conexão</button><?php endif; ?>
    </form>
    <div id="cvixServerTestResult"></div>
</div>
<div class="cvix-st-panel">
    <h3>Servidores</h3>
    <table class="cvix-st-table">
        <thead><tr><th>Nome</th><th>Tipo</th><th>Host</th><th>Porta</th><th>Cap.</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($servers as $s): ?>
        <tr>
            <td><?php echo CentralVixStreamingSecurity::e($s->name); ?></td>
            <td><?php echo CentralVixStreamingSecurity::e($serverTypes[$s->type] ?? $s->type); ?></td>
            <td><?php echo CentralVixStreamingSecurity::e($s->hostname); ?></td>
            <td><?php echo (int)$s->api_port; ?></td>
            <td><?php echo (int)$s->capacity; ?></td>
            <td><?php echo $s->active ? '<span class="cvix-st-badge ok">Ativo</span>' : '<span class="cvix-st-badge muted">Off</span>'; ?></td>
            <td><a class="cvix-st-btn sm" href="<?php echo CentralVixStreamingSecurity::e($modulelink.'&tab=servers&edit='.$s->id); ?>">Editar</a></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
