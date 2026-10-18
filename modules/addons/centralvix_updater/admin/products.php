<?php
$products = CentralVixUpdProductService::listAll();
$editId = (int) ($_GET['edit'] ?? 0);
$edit = $editId ? CentralVixUpdProductService::get($editId) : null;
$csrf = CentralVixUpdModule::csrfToken();
?>
<h2>Produtos</h2>
<div class="cvix-upd-split">
<div class="cvix-upd-panel">
<h3><?php echo $edit ? 'Editar' : 'Novo'; ?> produto</h3>
<form method="post" class="cvix-upd-form">
<input type="hidden" name="csrf_token" value="<?php echo CentralVixUpdSecurity::e($csrf); ?>">
<input type="hidden" name="admin_action" value="save_product">
<input type="hidden" name="product_id" value="<?php echo (int)($edit->id ?? 0); ?>">
<label>Nome<input type="text" name="name" required value="<?php echo CentralVixUpdSecurity::e($edit->name ?? ''); ?>"></label>
<label>Slug<input type="text" name="slug" value="<?php echo CentralVixUpdSecurity::e($edit->slug ?? ''); ?>" placeholder="meu-sistema"></label>
<label>Descrição<textarea name="description" rows="3"><?php echo CentralVixUpdSecurity::e($edit->description ?? ''); ?></textarea></label>
<label class="cvix-upd-check"><input type="checkbox" name="require_license" value="1" <?php echo !empty($edit->require_license)?'checked':''; ?>> Exigir licença na API</label>
<label>Slug produto licenciamento<input type="text" name="license_product_slug" value="<?php echo CentralVixUpdSecurity::e($edit->license_product_slug ?? ''); ?>" placeholder="opcional"></label>
<label class="cvix-upd-check"><input type="checkbox" name="active" value="1" <?php echo !isset($edit) || !empty($edit->active)?'checked':''; ?>> Ativo</label>
<button type="submit" class="cvix-upd-btn">Salvar</button>
</form>
</div>
<div class="cvix-upd-panel wide">
<table class="cvix-upd-table">
<thead><tr><th>Nome</th><th>Slug</th><th>Licença</th><th>Versões</th><th>Status</th><th></th></tr></thead>
<tbody>
<?php foreach ($products as $p): ?>
<tr>
<td><?php echo CentralVixUpdSecurity::e($p->name); ?></td>
<td><code><?php echo CentralVixUpdSecurity::e($p->slug); ?></code></td>
<td><?php echo $p->require_license ? 'Sim' : 'Não'; ?></td>
<td><?php echo CentralVixUpdProductService::countVersions($p->id); ?></td>
<td><?php echo $p->active ? 'Ativo' : 'Inativo'; ?></td>
<td><a class="cvix-upd-btn sm" href="<?php echo CentralVixUpdSecurity::e($modulelink.'&tab=products&edit='.$p->id); ?>">Editar</a></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>
