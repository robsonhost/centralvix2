<?php $products = CentralVixLicProductService::listAll(); ?>
<h2>Produtos licenciáveis</h2>
<?php if ($success): ?><div class="cvix-lic-alert ok"><?php echo CentralVixLicSecurity::e($success); ?></div><?php endif; ?>
<form method="post" class="cvix-lic-form cvix-lic-panel">
<input type="hidden" name="csrf_token" value="<?php echo CentralVixLicSecurity::e($csrf); ?>">
<input type="hidden" name="admin_action" value="save_product">
<label>Nome<input type="text" name="name" required></label>
<label>Slug<input type="text" name="slug"></label>
<label>ID produto WHMCS<input type="number" name="whmcs_product_id" min="0" value="0"></label>
<label>Máx. ativações<input type="number" name="max_activations" min="1" value="1"></label>
<label>Validade (dias)<input type="number" name="validity_days" min="0" value="365"></label>
<label>Grace period (horas)<select name="grace_period_hours">
<?php foreach ([24,48,72] as $h): ?><option value="<?php echo $h; ?>"><?php echo $h; ?>h</option><?php endforeach; ?>
</select></label>
<label class="cvix-lic-check"><input type="checkbox" name="active" value="1" checked> Ativo</label>
<button type="submit" class="cvix-lic-btn">Salvar</button>
</form>
<table class="cvix-lic-table"><thead><tr><th>Nome</th><th>WHMCS PID</th><th>Ativações</th><th>Grace</th><th>Validade</th></tr></thead>
<tbody><?php foreach ($products as $p): ?><tr>
<td><?php echo CentralVixLicSecurity::e($p->name); ?></td>
<td><?php echo (int)$p->whmcs_product_id; ?></td>
<td><?php echo (int)$p->max_activations; ?></td>
<td><?php echo (int)$p->grace_period_hours; ?>h</td>
<td><?php echo (int)$p->validity_days; ?>d</td>
</tr><?php endforeach; ?></tbody></table>
