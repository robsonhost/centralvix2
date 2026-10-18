<?php if (!defined('WHMCS')) { die('This file cannot be accessed directly'); }
$editId = (int)($_GET['edit'] ?? 0);
$edit = $editId ? CentralVixAutoDJService::getAccount($editId) : null;
$config = $edit ? json_decode($edit->provider_config ?: '{}', true) : [];
?>
<div class="cvix-adj-panel">
    <h3><?php echo $edit ? 'Editar conta AutoDJ' : 'Nova conta'; ?></h3>
    <form method="post" action="<?php echo CentralVixAutoDJSecurity::e($modulelink.'&tab=accounts'); ?>" class="cvix-adj-form">
        <input type="hidden" name="token" value="<?php echo CentralVixAutoDJSecurity::e($csrfToken); ?>">
        <input type="hidden" name="cvix_save_account" value="1">
        <input type="hidden" name="id" value="<?php echo (int)($edit->id ?? 0); ?>">
        <div class="cvix-adj-grid">
            <label>Cliente ID<input type="number" name="client_id" required value="<?php echo (int)($edit->client_id ?? 0); ?>"></label>
            <label>Serviço WHMCS<input type="number" name="service_id" value="<?php echo (int)($edit->service_id ?? 0); ?>"></label>
            <label>Nome rádio<input name="name" value="<?php echo CentralVixAutoDJSecurity::e($edit->name ?? 'Minha Rádio'); ?>"></label>
            <label>Quota MB<input type="number" name="quota_mb" value="<?php echo (int)($edit->quota_mb ?? 5120); ?>" placeholder="5120=5GB"></label>
            <label>Provider<select name="provider_type"><?php foreach ($providers as $k=>$v): ?><option value="<?php echo $k; ?>" <?php echo ($edit->provider_type ?? 'none')===$k?'selected':''; ?>><?php echo CentralVixAutoDJSecurity::e($v); ?></option><?php endforeach; ?></select></label>
            <label>Status<select name="status"><option value="active">Ativo</option><option value="suspended" <?php echo ($edit->status ?? '')==='suspended'?'selected':''; ?>>Suspenso</option></select></label>
        </div>
        <h4>Config provider (JSON ou campos)</h4>
        <label>Base URL / API URL<input name="provider_base_url" value="<?php echo CentralVixAutoDJSecurity::e($config['base_url'] ?? $config['api_url'] ?? ''); ?>"></label>
        <label>API Key / Token<input type="password" name="provider_api_key" value="" placeholder="<?php echo $edit ? '•••• (mantém se vazio)' : ''; ?>"></label>
        <label>Station ID (AzuraCast)<input name="provider_station_id" value="<?php echo CentralVixAutoDJSecurity::e($config['station_id'] ?? ''); ?>"></label>
        <button type="submit" class="cvix-adj-btn primary">Salvar conta</button>
    </form>
</div>
<div class="cvix-adj-panel">
    <h3>Contas</h3>
    <table class="cvix-adj-table">
        <thead><tr><th>ID</th><th>Cliente</th><th>Nome</th><th>Provider</th><th>Quota</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($accounts as $a):
            $q = CentralVixAutoDJStorageService::quotaInfo($a);
        ?>
        <tr>
            <td>#<?php echo (int)$a->id; ?></td>
            <td><?php echo CentralVixAutoDJSecurity::e(trim($a->client_name ?? '') ?: $a->client_id); ?></td>
            <td><?php echo CentralVixAutoDJSecurity::e($a->name); ?></td>
            <td><?php echo CentralVixAutoDJSecurity::e($providers[$a->provider_type] ?? $a->provider_type); ?></td>
            <td><?php echo CentralVixAutoDJSecurity::e($q['used_mb']); ?>/<?php echo (int)$a->quota_mb; ?> MB</td>
            <td><?php echo CentralVixAutoDJSecurity::e($a->status); ?></td>
            <td><a class="cvix-adj-btn sm" href="<?php echo CentralVixAutoDJSecurity::e($modulelink.'&tab=accounts&edit='.$a->id); ?>">Editar</a></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
