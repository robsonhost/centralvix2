<?php
$logs = CentralVixLicLogger::listRecent(200);
?>
<h2>Logs</h2>
<table class="cvix-lic-table">
<thead><tr><th>Data</th><th>Licença</th><th>Ação</th><th>Mensagem</th><th>IP</th></tr></thead>
<tbody>
<?php foreach ($logs as $log): ?>
<tr>
<td><?php echo date('d/m/Y H:i:s', strtotime($log->created_at)); ?></td>
<td><?php echo $log->license_key ? CentralVixLicSecurity::e(CentralVixLicSecurity::maskLicenseKey($log->license_key)) : '—'; ?></td>
<td><?php echo CentralVixLicSecurity::e($log->action); ?></td>
<td><?php echo CentralVixLicSecurity::e($log->message); ?></td>
<td><?php echo CentralVixLicSecurity::e($log->ip_address); ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
