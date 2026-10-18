<?php
$products = CentralVixUpdProductService::listAll(true);
$productId = (int) ($_GET['product_id'] ?? ($products[0]->id ?? 0));
$editId = (int) ($_GET['edit'] ?? 0);
$edit = $editId ? CentralVixUpdVersionService::get($editId) : null;
$versions = $productId ? CentralVixUpdVersionService::listByProduct($productId) : [];
$csrf = CentralVixUpdModule::csrfToken();
?>
<h2>Versões</h2>
<form method="get" class="cvix-upd-inline" style="margin-bottom:12px">
<input type="hidden" name="module" value="centralvix_updater">
<input type="hidden" name="tab" value="versions">
<label>Produto
<select name="product_id" onchange="this.form.submit()">
<?php foreach ($products as $p): ?>
<option value="<?php echo (int)$p->id; ?>" <?php echo $productId===$p->id?'selected':''; ?>><?php echo CentralVixUpdSecurity::e($p->name); ?></option>
<?php endforeach; ?>
</select></label>
</form>
<div class="cvix-upd-split">
<div class="cvix-upd-panel">
<h3><?php echo $edit ? 'Editar' : 'Nova'; ?> versão</h3>
<form method="post" class="cvix-upd-form">
<input type="hidden" name="csrf_token" value="<?php echo CentralVixUpdSecurity::e($csrf); ?>">
<input type="hidden" name="admin_action" value="save_version">
<input type="hidden" name="version_id" value="<?php echo (int)($edit->id ?? 0); ?>">
<input type="hidden" name="product_id" value="<?php echo $productId; ?>">
<label>Versão<input type="text" name="version" required placeholder="1.2.0" value="<?php echo CentralVixUpdSecurity::e($edit->version ?? ''); ?>"></label>
<label>Canal
<select name="channel">
<option value="stable" <?php echo ($edit->channel ?? 'stable')==='stable'?'selected':''; ?>>Stable</option>
<option value="beta" <?php echo ($edit->channel ?? '')==='beta'?'selected':''; ?>>Beta</option>
</select></label>
<label>Versão mínima (upgrade)<input type="text" name="min_version" value="<?php echo CentralVixUpdSecurity::e($edit->min_version ?? '0.0.0'); ?>"></label>
<label>PHP mínimo<input type="text" name="min_php" value="<?php echo CentralVixUpdSecurity::e($edit->min_php ?? '7.4'); ?>"></label>
<label>WHMCS mínimo (opcional)<input type="text" name="min_whmcs" value="<?php echo CentralVixUpdSecurity::e($edit->min_whmcs ?? ''); ?>"></label>
<label>Release notes<textarea name="release_notes" rows="5"><?php echo CentralVixUpdSecurity::e($edit->release_notes ?? ''); ?></textarea></label>
<label class="cvix-upd-check"><input type="checkbox" name="is_required" value="1" <?php echo !empty($edit->is_required)?'checked':''; ?>> Atualização obrigatória</label>
<label class="cvix-upd-check"><input type="checkbox" name="active" value="1" <?php echo !isset($edit) || !empty($edit->active)?'checked':''; ?>> Ativa</label>
<button type="submit" class="cvix-upd-btn">Salvar</button>
</form>
</div>
<div class="cvix-upd-panel wide">
<table class="cvix-upd-table">
<thead><tr><th>Versão</th><th>Canal</th><th>Pacote</th><th>SHA-256</th><th>Obrig.</th><th>Data</th><th></th></tr></thead>
<tbody>
<?php foreach ($versions as $v): ?>
<tr>
<td><strong><?php echo CentralVixUpdSecurity::e($v->version); ?></strong></td>
<td><?php echo CentralVixUpdSecurity::e($v->channel); ?></td>
<td><?php echo $v->file_path ? 'Sim (' . number_format($v->file_size/1024,1) . ' KB)' : '—'; ?></td>
<td><code class="cvix-upd-hash"><?php echo CentralVixUpdSecurity::e($v->sha256_hash ? substr($v->sha256_hash,0,16).'…' : '—'); ?></code></td>
<td><?php echo $v->is_required ? 'Sim' : 'Não'; ?></td>
<td><?php echo CentralVixUpdSecurity::e(substr($v->released_at ?? $v->created_at,0,10)); ?></td>
<td><a class="cvix-upd-btn sm" href="<?php echo CentralVixUpdSecurity::e($modulelink.'&tab=versions&product_id='.$productId.'&edit='.$v->id); ?>">Editar</a></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>
