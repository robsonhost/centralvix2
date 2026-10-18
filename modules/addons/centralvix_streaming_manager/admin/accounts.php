<?php if (!defined('WHMCS')) { die('This file cannot be accessed directly'); }
$editId = (int)($_GET['edit'] ?? 0);
$edit = $editId ? CentralVixStreamingService::getAccount($editId) : null;
?>
<div class="cvix-st-panel">
    <h3><?php echo $edit ? 'Editar conta #'.(int)$edit->id : 'Nova conta'; ?></h3>
    <form method="post" action="<?php echo CentralVixStreamingSecurity::e($modulelink.'&tab=accounts'); ?>" class="cvix-st-form">
        <input type="hidden" name="token" value="<?php echo CentralVixStreamingSecurity::e($csrfToken); ?>">
        <input type="hidden" name="cvix_save_account" value="1">
        <input type="hidden" name="id" value="<?php echo (int)($edit->id ?? 0); ?>">
        <div class="cvix-st-grid">
            <label>Cliente ID<input type="number" name="client_id" required value="<?php echo (int)($edit->client_id ?? 0); ?>"></label>
            <label>Serviço WHMCS ID<input type="number" name="service_id" value="<?php echo (int)($edit->service_id ?? 0); ?>"></label>
            <label>Servidor<select name="server_id" required><?php foreach ($servers as $s): ?><option value="<?php echo (int)$s->id; ?>" <?php echo (int)($edit->server_id ?? 0)===(int)$s->id?'selected':''; ?>><?php echo CentralVixStreamingSecurity::e($s->name); ?></option><?php endforeach; ?></select></label>
            <label>Usuário stream<input name="stream_username" required value="<?php echo CentralVixStreamingSecurity::e($edit->stream_username ?? ''); ?>"></label>
            <label>Senha stream<input type="password" name="stream_password" value="<?php echo $edit?'********':''; ?>"></label>
            <label>Porta<input type="number" name="port" value="<?php echo (int)($edit->port ?? 8000); ?>"></label>
            <label>Mountpoint<input name="mountpoint" value="<?php echo CentralVixStreamingSecurity::e($edit->mountpoint ?? '/stream'); ?>"></label>
            <label>Máx. ouvintes<input type="number" name="max_listeners" value="<?php echo (int)($edit->max_listeners ?? 100); ?>"></label>
            <label>Bitrate<input type="number" name="bitrate" value="<?php echo (int)($edit->bitrate ?? 128); ?>"></label>
            <label>Espaço MB<input type="number" name="disk_space_mb" value="<?php echo (int)($edit->disk_space_mb ?? 0); ?>"></label>
            <label>Status<select name="status"><?php foreach ($accountStatuses as $k=>$v): ?><option value="<?php echo $k; ?>" <?php echo ($edit->status ?? 'pending')===$k?'selected':''; ?>><?php echo CentralVixStreamingSecurity::e($v); ?></option><?php endforeach; ?></select></label>
        </div>
        <label><input type="checkbox" name="autodj" value="1" <?php echo !empty($edit->autodj)?'checked':''; ?>> AutoDJ</label>
        <button type="submit" class="cvix-st-btn primary">Salvar conta</button>
        <?php if ($edit): ?>
        <button type="button" class="cvix-st-btn cvix-account-action" data-id="<?php echo (int)$edit->id; ?>" data-action="create">Provisionar remoto</button>
        <?php endif; ?>
    </form>
</div>
<div class="cvix-st-panel">
    <h3>Contas de streaming</h3>
    <table class="cvix-st-table">
        <thead><tr><th>ID</th><th>Cliente</th><th>Servidor</th><th>Usuário</th><th>Porta</th><th>Status</th><th>Ações</th></tr></thead>
        <tbody>
        <?php foreach ($accounts as $a): ?>
        <tr>
            <td>#<?php echo (int)$a->id; ?></td>
            <td><?php echo (int)$a->client_id; ?></td>
            <td><?php echo CentralVixStreamingSecurity::e($a->server_name); ?></td>
            <td><?php echo CentralVixStreamingSecurity::e($a->stream_username); ?></td>
            <td><?php echo (int)$a->port; ?></td>
            <td><span class="cvix-st-badge"><?php echo CentralVixStreamingSecurity::e($accountStatuses[$a->status] ?? $a->status); ?></span></td>
            <td class="cvix-st-actions">
                <a class="cvix-st-btn sm" href="<?php echo CentralVixStreamingSecurity::e($modulelink.'&tab=accounts&edit='.$a->id); ?>">Editar</a>
                <button type="button" class="cvix-st-btn sm cvix-account-action" data-id="<?php echo (int)$a->id; ?>" data-action="suspend">Suspender</button>
                <button type="button" class="cvix-st-btn sm cvix-account-action" data-id="<?php echo (int)$a->id; ?>" data-action="unsuspend">Reativar</button>
                <button type="button" class="cvix-st-btn sm cvix-account-action" data-id="<?php echo (int)$a->id; ?>" data-action="stop">Parar</button>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
