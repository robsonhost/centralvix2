<?php $rows = CentralVixUpdLogger::recentDownloads(150); ?>
<h2>Downloads e eventos</h2>
<table class="cvix-upd-table">
<thead><tr><th>Data</th><th>Produto</th><th>Versão</th><th>Status</th><th>IP</th><th>Mensagem</th></tr></thead>
<tbody>
<?php foreach ($rows as $r): ?>
<tr>
<td><?php echo CentralVixUpdSecurity::e($r->created_at); ?></td>
<td><?php echo CentralVixUpdSecurity::e($r->product_name ?? '-'); ?></td>
<td><?php echo CentralVixUpdSecurity::e($r->version ?? '-'); ?></td>
<td><span class="cvix-upd-badge <?php echo CentralVixUpdSecurity::e($r->status); ?>"><?php echo CentralVixUpdSecurity::e($r->status); ?></span></td>
<td><?php echo CentralVixUpdSecurity::e($r->ip_address); ?></td>
<td><?php echo CentralVixUpdSecurity::e($r->message); ?></td>
</tr>
<?php endforeach; ?>
<?php if (!count($rows)): ?><tr><td colspan="6">Nenhum registro.</td></tr><?php endif; ?>
</tbody>
</table>
