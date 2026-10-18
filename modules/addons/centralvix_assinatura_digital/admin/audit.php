<?php if (!defined('WHMCS')) { die('This file cannot be accessed directly'); }
$events = CentralVixSignEvidenceService::auditList(150);
?>
<h2>Auditoria</h2>
<table class="cvix-sig-table">
<thead><tr><th>Data</th><th>Documento</th><th>Evento</th><th>IP</th><th>Detalhes</th></tr></thead>
<tbody>
<?php foreach ($events as $e): ?>
<tr>
<td><?php echo date('d/m/Y H:i:s', strtotime($e->created_at)); ?></td>
<td><?php echo CentralVixSignSecurity::e($e->document_title); ?><br><small><?php echo CentralVixSignSecurity::e($e->transaction_id); ?></small></td>
<td><?php echo CentralVixSignSecurity::e($e->event_type); ?></td>
<td><?php echo CentralVixSignSecurity::e($e->ip_address); ?></td>
<td><?php echo CentralVixSignSecurity::e($e->message); ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
