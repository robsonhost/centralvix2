<?php
if (!defined('WHMCS')) { die('This file cannot be accessed directly'); }
$players = CentralVixPlayerService::listAll(200);
?>
<h2>Players</h2>
<table class="cvix-pg-table">
<thead><tr><th>ID</th><th>Rádio</th><th>Cliente</th><th>Status</th><th>Views</th><th>URL</th></tr></thead>
<tbody>
<?php foreach ($players as $p): ?>
<tr>
<td><?php echo (int)$p->id; ?></td>
<td><?php echo CentralVixPlayerSecurity::e($p->radio_name); ?></td>
<td><?php echo CentralVixPlayerSecurity::e($p->client_name ?: '—'); ?></td>
<td><?php echo $p->active ? '<span class="cvix-pg-badge ok">Ativo</span>' : '<span class="cvix-pg-badge off">Inativo</span>'; ?></td>
<td><?php echo number_format((int)$p->views_count, 0, ',', '.'); ?></td>
<td><a href="<?php echo CentralVixPlayerSecurity::e(CentralVixPlayerModule::publicPlayerUrl($p->public_hash)); ?>" target="_blank" rel="noopener">Abrir</a></td>
</tr>
<?php endforeach; ?>
<?php if (count($players) === 0): ?>
<tr><td colspan="6">Nenhum player cadastrado.</td></tr>
<?php endif; ?>
</tbody>
</table>
