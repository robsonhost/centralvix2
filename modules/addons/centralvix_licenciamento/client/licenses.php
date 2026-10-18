<?php
$licenses = CentralVixLicLicenseService::listByClient($clientId);
?>
<div class="cvix-lic-hero"><h2>Minhas Licenças</h2><p>Gerencie chaves, domínios e validade dos seus sistemas CentralVix.</p></div>
<div class="cvix-lic-grid">
<?php foreach ($licenses as $l): ?>
<a class="cvix-lic-card-link" href="<?php echo CentralVixLicSecurity::e($modulelink.'&action=license&id='.(int)$l->id); ?>">
<h3><?php echo CentralVixLicSecurity::e($l->product_name ?: 'Licença'); ?></h3>
<span class="cvix-lic-pill cvix-lic-pill--<?php echo CentralVixLicSecurity::e($l->status); ?>"><?php echo CentralVixLicSecurity::e(CentralVixLicLicenseService::statusLabel($l->status)); ?></span>
<p>Validade: <?php echo $l->expires_at ? date('d/m/Y', strtotime($l->expires_at)) : 'Sem expiração'; ?></p>
</a>
<?php endforeach; ?>
</div>
<?php if (!count($licenses)): ?><div class="cvix-lic-empty">Nenhuma licença disponível.</div><?php endif; ?>
