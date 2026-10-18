<?php
$licenses = CentralVixLicLicenseService::listAll();
$products = CentralVixLicProductService::listAll();
$clients = WHMCS\Database\Capsule::table('tblclients')->select('id','firstname','lastname')->orderBy('firstname')->get();
?>
<h2>Licenças</h2>
<?php if ($success): ?><div class="cvix-lic-alert ok"><?php echo CentralVixLicSecurity::e($success); ?></div><?php endif; ?>
<?php if ($error): ?><div class="cvix-lic-alert err"><?php echo CentralVixLicSecurity::e($error); ?></div><?php endif; ?>

<div class="cvix-lic-split">
<div class="cvix-lic-panel">
<h3>Nova licença</h3>
<form method="post" class="cvix-lic-form">
<input type="hidden" name="csrf_token" value="<?php echo CentralVixLicSecurity::e($csrf); ?>">
<input type="hidden" name="admin_action" value="create_license">
<label>Cliente<select name="client_id" required><option value="">—</option><?php foreach ($clients as $c): ?><option value="<?php echo (int)$c->id; ?>"><?php echo CentralVixLicSecurity::e($c->firstname.' '.$c->lastname); ?></option><?php endforeach; ?></select></label>
<label>Produto<select name="product_id" required><?php foreach ($products as $p): ?><option value="<?php echo (int)$p->id; ?>"><?php echo CentralVixLicSecurity::e($p->name); ?></option><?php endforeach; ?></select></label>
<label>Domínio<input type="text" name="domain" placeholder="exemplo.com.br"></label>
<label>Service ID<input type="number" name="service_id" min="0" value="0"></label>
<button type="submit" class="cvix-lic-btn">Gerar licença</button>
</form>
</div>
<div class="cvix-lic-panel wide">
<table class="cvix-lic-table">
<thead><tr><th>Chave</th><th>Cliente</th><th>Produto</th><th>Status</th><th>Domínio</th><th>Validade</th><th>Ações</th></tr></thead>
<tbody>
<?php foreach ($licenses as $l): ?>
<tr>
<td><code class="cvix-lic-key"><?php echo CentralVixLicSecurity::e($l->license_key); ?></code></td>
<td><?php echo CentralVixLicSecurity::e($l->client_name); ?></td>
<td><?php echo CentralVixLicSecurity::e($l->product_name); ?></td>
<td><?php echo CentralVixLicSecurity::e(CentralVixLicLicenseService::statusLabel($l->status)); ?></td>
<td><?php echo CentralVixLicSecurity::e($l->domain ?: '—'); ?></td>
<td><?php echo $l->expires_at ? date('d/m/Y', strtotime($l->expires_at)) : '—'; ?></td>
<td class="cvix-lic-actions">
<form method="post" class="cvix-lic-inline" onsubmit="return confirm('Confirmar?');">
<input type="hidden" name="csrf_token" value="<?php echo CentralVixLicSecurity::e($csrf); ?>">
<input type="hidden" name="admin_action" value="license_action">
<input type="hidden" name="license_id" value="<?php echo (int)$l->id; ?>">
<button name="license_act" value="activate" class="cvix-lic-btn sm">Ativar</button>
<button name="license_act" value="suspend" class="cvix-lic-btn sm">Suspender</button>
<button name="license_act" value="cancel" class="cvix-lic-btn sm danger">Cancelar</button>
<button name="license_act" value="renew" class="cvix-lic-btn sm">Renovar</button>
<button name="license_act" value="reset" class="cvix-lic-btn sm">Reset ativ.</button>
<input type="text" name="new_domain" placeholder="Novo domínio" size="12">
<button name="license_act" value="domain" class="cvix-lic-btn sm">Trocar dom.</button>
</form>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>
