<?php if (!defined('WHMCS')) { die('This file cannot be accessed directly'); }
if (!$account) { return; }
include __DIR__ . '/_nav.php';
?>
<div class="cvix-adj-panel">
    <h3>Nova playlist</h3>
    <form id="cvixPlaylistForm" class="cvix-adj-form">
        <input type="hidden" name="token" value="<?php echo CentralVixAutoDJSecurity::e($csrfToken); ?>">
        <input type="hidden" name="account_id" value="<?php echo (int)$account->id; ?>">
        <label>Nome<input name="name" required></label>
        <label>Descrição<textarea name="description" rows="2"></textarea></label>
        <label>Tipo<select name="playlist_type"><option value="general">Geral</option><option value="clock">Relógio musical</option><option value="commercial">Comercial</option><option value="jingle">Vinhetas</option></select></label>
        <label>Faixas<select name="media_ids[]" multiple size="8"><?php foreach ($media as $m): ?><option value="<?php echo (int)$m->id; ?>"><?php echo CentralVixAutoDJSecurity::e($m->original_name); ?></option><?php endforeach; ?></select></label>
        <label><input type="checkbox" name="random_mode" value="1"> Aleatório</label>
        <label><input type="checkbox" name="active" value="1" checked> Ativa</label>
        <button type="submit" class="cvix-adj-btn primary">Criar playlist</button>
    </form>
</div>
<div class="cvix-adj-panel">
    <h3>Playlists</h3>
    <table class="cvix-adj-table">
        <thead><tr><th>Nome</th><th>Tipo</th><th>Aleatório</th><th>Ativa</th></tr></thead>
        <tbody>
        <?php foreach ($playlists as $p): ?>
        <tr>
            <td><?php echo CentralVixAutoDJSecurity::e($p->name); ?></td>
            <td><?php echo CentralVixAutoDJSecurity::e($p->playlist_type); ?></td>
            <td><?php echo $p->random_mode ? 'Sim' : 'Não'; ?></td>
            <td><?php echo $p->active ? 'Sim' : 'Não'; ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
