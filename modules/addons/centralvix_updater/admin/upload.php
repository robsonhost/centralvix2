<?php
$products = CentralVixUpdProductService::listAll(true);
$productId = (int) ($_GET['product_id'] ?? 0);
$versions = [];
if ($productId) {
    $versions = CentralVixUpdVersionService::listByProduct($productId);
}
$csrf = CentralVixUpdModule::csrfToken();
?>
<h2>Upload de pacotes</h2>
<p class="cvix-upd-muted">Envie arquivos ZIP. O servidor valida MIME, tamanho e ZIP Slip. Pacotes ficam em storage protegido.</p>
<form method="get" class="cvix-upd-inline" style="margin-bottom:12px">
<input type="hidden" name="module" value="centralvix_updater">
<input type="hidden" name="tab" value="upload">
<label>Produto
<select name="product_id" onchange="this.form.submit()">
<option value="">Selecione…</option>
<?php foreach ($products as $p): ?>
<option value="<?php echo (int)$p->id; ?>" <?php echo $productId===$p->id?'selected':''; ?>><?php echo CentralVixUpdSecurity::e($p->name); ?></option>
<?php endforeach; ?>
</select></label>
</form>
<?php if ($productId && count($versions)): ?>
<form method="post" enctype="multipart/form-data" class="cvix-upd-panel cvix-upd-form">
<input type="hidden" name="csrf_token" value="<?php echo CentralVixUpdSecurity::e($csrf); ?>">
<input type="hidden" name="admin_action" value="upload_package">
<label>Versão
<select name="version_id" required>
<?php foreach ($versions as $v): ?>
<option value="<?php echo (int)$v->id; ?>"><?php echo CentralVixUpdSecurity::e($v->version . ' (' . $v->channel . ')'); ?></option>
<?php endforeach; ?>
</select></label>
<label>Arquivo ZIP (máx. 500 MB)<input type="file" name="package" accept=".zip,application/zip" required></label>
<button type="submit" class="cvix-upd-btn" onclick="return confirm('Confirmar upload do pacote?');">Enviar pacote</button>
</form>
<?php elseif ($productId): ?>
<p class="cvix-upd-muted">Cadastre uma versão antes de enviar o pacote.</p>
<?php endif; ?>
