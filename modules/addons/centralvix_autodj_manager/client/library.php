<?php if (!defined('WHMCS')) { die('This file cannot be accessed directly'); }
if (!$account) { echo '<p>Conta indisponível.</p>'; return; }
include __DIR__ . '/_nav.php';
?>
<div class="cvix-adj-panel">
    <h3>Upload</h3>
    <form id="cvixUploadForm" enctype="multipart/form-data" class="cvix-adj-form">
        <input type="hidden" name="token" value="<?php echo CentralVixAutoDJSecurity::e($csrfToken); ?>">
        <input type="hidden" name="account_id" value="<?php echo (int)$account->id; ?>">
        <label>Tipo<select name="media_type"><?php foreach ($mediaTypes as $k=>$v): ?><option value="<?php echo $k; ?>"><?php echo CentralVixAutoDJSecurity::e($v); ?></option><?php endforeach; ?></select></label>
        <label>Arquivo (MP3<?php echo CentralVixAutoDJModule::setting('allow_aac')==='on'?', AAC':''; ?>)<input type="file" name="file" accept=".mp3<?php echo CentralVixAutoDJModule::setting('allow_aac')==='on'?',.aac,.m4a':''; ?>" required></label>
        <button type="submit" class="cvix-adj-btn primary">Enviar</button>
    </form>
</div>
<div class="cvix-adj-panel">
    <h3>Biblioteca (<?php echo count($media); ?>)</h3>
    <table class="cvix-adj-table">
        <thead><tr><th>Nome</th><th>Tipo</th><th>Tamanho</th><th>Duração</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($media as $m): ?>
        <tr>
            <td><?php echo CentralVixAutoDJSecurity::e($m->original_name); ?></td>
            <td><?php echo CentralVixAutoDJSecurity::e($mediaTypes[$m->media_type] ?? $m->media_type); ?></td>
            <td><?php echo round($m->size_bytes/1048576, 2); ?> MB</td>
            <td><?php echo (int)$m->duration_seconds; ?>s</td>
            <td><button type="button" class="cvix-adj-btn sm danger cvix-delete-media" data-id="<?php echo (int)$m->id; ?>">Excluir</button></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
