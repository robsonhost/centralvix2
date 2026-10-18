<?php
$lic = CentralVixLicLicenseService::get($licenseId, $clientId);
if (!$lic) { echo '<div class="cvix-lic-empty">Licença não encontrada.</div>'; return; }
$product = CentralVixLicProductService::get($lic->product_id);
$activations = CentralVixLicLicenseService::activations($licenseId);
$logs = WHMCS\Database\Capsule::table('mod_centralvix_license_logs')->where('license_id', $licenseId)->orderBy('id','desc')->limit(20)->get();
?>
<div class="cvix-lic-detail">
<h2><?php echo CentralVixLicSecurity::e($product ? $product->name : 'Licença'); ?></h2>
<span class="cvix-lic-pill cvix-lic-pill--<?php echo CentralVixLicSecurity::e($lic->status); ?>"><?php echo CentralVixLicSecurity::e(CentralVixLicLicenseService::statusLabel($lic->status)); ?></span>

<div class="cvix-lic-key-box">
<label>Chave de licença</label>
<input type="text" readonly id="cvix-license-key" class="cvix-lic-key-input" value="<?php echo CentralVixLicSecurity::e($lic->license_key); ?>">
<button type="button" class="cvix-lic-btn sm" id="cvix-copy-key">Copiar chave</button>
</div>

<ul class="cvix-lic-meta">
<li><span>Domínio</span><strong><?php echo CentralVixLicSecurity::e($lic->domain ?: '—'); ?></strong></li>
<li><span>IP registrado</span><strong><?php echo CentralVixLicSecurity::e($lic->ip_address ?: '—'); ?></strong></li>
<li><span>Emitida em</span><strong><?php echo $lic->issued_at ? date('d/m/Y', strtotime($lic->issued_at)) : '—'; ?></strong></li>
<li><span>Validade</span><strong><?php echo $lic->expires_at ? date('d/m/Y H:i', strtotime($lic->expires_at)) : '—'; ?></strong></li>
<li><span>Ativações</span><strong><?php echo CentralVixLicLicenseService::activationCount($licenseId); ?> / <?php echo (int)$lic->max_activations; ?></strong></li>
<li><span>Grace period</span><strong><?php echo $product ? (int)$product->grace_period_hours : 24; ?>h</strong></li>
<li><span>Última verificação</span><strong><?php echo $lic->last_check ? date('d/m/Y H:i', strtotime($lic->last_check)) : '—'; ?></strong></li>
</ul>

<h3>Ativações</h3>
<ul><?php foreach ($activations as $a): ?><li><?php echo CentralVixLicSecurity::e($a->domain); ?> — <?php echo CentralVixLicSecurity::e($a->ip_address); ?> (<?php echo date('d/m/Y', strtotime($a->activated_at)); ?>)</li><?php endforeach; ?>
<?php if (!count($activations)): ?><li>Nenhuma ativação registrada.</li><?php endif; ?></ul>

<h3>Histórico</h3>
<ul class="cvix-lic-history"><?php foreach ($logs as $log): ?><li><?php echo date('d/m H:i', strtotime($log->created_at)); ?> — <?php echo CentralVixLicSecurity::e($log->message); ?></li><?php endforeach; ?></ul>

<p><a href="<?php echo CentralVixLicSecurity::e($modulelink); ?>">← Voltar</a></p>
</div>
