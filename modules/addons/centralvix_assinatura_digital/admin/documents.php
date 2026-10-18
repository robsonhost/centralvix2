<?php
if (!defined('WHMCS')) { die('This file cannot be accessed directly'); }
$docs = CentralVixSignDocumentService::listAll();
$clients = WHMCS\Database\Capsule::table('tblclients')->select('id','firstname','lastname')->orderBy('firstname')->get();
$templates = CentralVixSignDocumentService::listTemplates();
?>
<h2>Documentos</h2>
<?php if ($success): ?><div class="cvix-sig-alert ok"><?php echo CentralVixSignSecurity::e($success); ?><?php if (!empty($tokenLink)): ?><br>Link: <input readonly class="cvix-sig-copy" value="<?php echo CentralVixSignSecurity::e($tokenLink); ?>"><?php endif; ?></div><?php endif; ?>
<?php if ($error): ?><div class="cvix-sig-alert err"><?php echo CentralVixSignSecurity::e($error); ?></div><?php endif; ?>

<div class="cvix-sig-split">
<div class="cvix-sig-panel">
<h3>Upload PDF</h3>
<form method="post" enctype="multipart/form-data" class="cvix-sig-form">
<input type="hidden" name="csrf_token" value="<?php echo CentralVixSignSecurity::e($csrf); ?>">
<input type="hidden" name="upload_document" value="1">
<label>Cliente<select name="client_id" required><option value="">—</option><?php foreach ($clients as $c): ?><option value="<?php echo (int)$c->id; ?>"><?php echo CentralVixSignSecurity::e($c->firstname.' '.$c->lastname); ?></option><?php endforeach; ?></select></label>
<label>Título<input type="text" name="title" required maxlength="200"></label>
<label>Template<select name="template_id"><option value="0">—</option><?php foreach ($templates as $t): ?><option value="<?php echo (int)$t->id; ?>"><?php echo CentralVixSignSecurity::e($t->name); ?></option><?php endforeach; ?></select></label>
<label>PDF<input type="file" name="document" accept="application/pdf" required></label>
<button type="submit" class="cvix-sig-btn">Criar rascunho</button>
</form>
</div>
<div class="cvix-sig-panel wide">
<table class="cvix-sig-table">
<thead><tr><th>ID</th><th>Título</th><th>Cliente</th><th>Status</th><th>Hash</th><th>Transação</th><th>Ações</th></tr></thead>
<tbody>
<?php foreach ($docs as $d): ?>
<tr>
<td><?php echo (int)$d->id; ?></td>
<td><?php echo CentralVixSignSecurity::e($d->title); ?></td>
<td><?php echo CentralVixSignSecurity::e($d->client_name); ?></td>
<td><span class="cvix-sig-status cvix-sig-status--<?php echo CentralVixSignSecurity::e($d->status); ?>"><?php echo CentralVixSignSecurity::e(CentralVixSignDocumentService::statusLabel($d->status)); ?></span></td>
<td><code title="<?php echo CentralVixSignSecurity::e($d->document_hash); ?>"><?php echo CentralVixSignSecurity::e(substr($d->document_hash, 0, 12)); ?>…</code></td>
<td><small><?php echo CentralVixSignSecurity::e($d->transaction_id); ?></small></td>
<td class="cvix-sig-actions">
<?php if ($d->status === 'rascunho'): ?>
<form method="post" style="display:inline"><input type="hidden" name="csrf_token" value="<?php echo CentralVixSignSecurity::e($csrf); ?>"><input type="hidden" name="send_document" value="1"><input type="hidden" name="document_id" value="<?php echo (int)$d->id; ?>"><button class="cvix-sig-btn sm">Enviar</button></form>
<?php endif; ?>
<a href="<?php echo CentralVixSignSecurity::e(CentralVixSignModule::downloadUrl($d->id, 'original')); ?>" target="_blank">PDF</a>
<?php if ($d->status === 'assinado' && $d->receipt_filename): ?> · <a href="<?php echo CentralVixSignSecurity::e(CentralVixSignModule::downloadUrl($d->id, 'receipt')); ?>">Comprovante</a><?php endif; ?>
<form method="post" style="display:inline" onsubmit="return confirm('Cancelar?');"><input type="hidden" name="csrf_token" value="<?php echo CentralVixSignSecurity::e($csrf); ?>"><input type="hidden" name="cancel_document" value="1"><input type="hidden" name="document_id" value="<?php echo (int)$d->id; ?>"><button class="cvix-sig-btn sm danger">Cancelar</button></form>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>
