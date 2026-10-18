<?php
if (!defined('WHMCS')) { die('This file cannot be accessed directly'); }
$docs = CentralVixSignDocumentService::listByClient($clientId);
$legal = CentralVixSignModule::dbSetting('legal_notice', '');
?>
<div class="cvix-sig-hero"><h2>Assinatura Digital</h2><p class="cvix-sig-legal-client"><?php echo CentralVixSignSecurity::e($legal); ?></p></div>

<h3>Para assinar</h3>
<div class="cvix-sig-doc-grid">
<?php foreach ($docs as $d): if (!in_array($d->status, ['enviado','visualizado'], true)) continue; ?>
<a class="cvix-sig-doc-card pending" href="<?php echo CentralVixSignSecurity::e($modulelink.'&action=sign&id='.(int)$d->id); ?>">
<h4><?php echo CentralVixSignSecurity::e($d->title); ?></h4>
<span><?php echo CentralVixSignSecurity::e(CentralVixSignDocumentService::statusLabel($d->status)); ?></span>
<p>Expira: <?php echo $d->expires_at ? date('d/m/Y', strtotime($d->expires_at)) : '—'; ?></p>
</a>
<?php endforeach; ?>
</div>

<h3>Assinados</h3>
<div class="cvix-sig-doc-grid">
<?php foreach ($docs as $d): if ($d->status !== 'assinado') continue; ?>
<div class="cvix-sig-doc-card signed">
<h4><?php echo CentralVixSignSecurity::e($d->title); ?></h4>
<p>Assinado em <?php echo $d->signed_at ? date('d/m/Y H:i', strtotime($d->signed_at)) : '—'; ?></p>
<p><small>ID: <?php echo CentralVixSignSecurity::e($d->transaction_id); ?></small></p>
<a href="<?php echo CentralVixSignSecurity::e(CentralVixSignModule::downloadUrl($d->id, 'original', true)); ?>">Documento</a>
<?php if ($d->receipt_filename): ?> · <a href="<?php echo CentralVixSignSecurity::e(CentralVixSignModule::downloadUrl($d->id, 'receipt', true)); ?>">Comprovante</a><?php endif; ?>
</div>
<?php endforeach; ?>
</div>

<h3>Histórico</h3>
<ul class="cvix-sig-history">
<?php foreach ($docs as $d): ?>
<li><strong><?php echo CentralVixSignSecurity::e($d->title); ?></strong> — <?php echo CentralVixSignSecurity::e(CentralVixSignDocumentService::statusLabel($d->status)); ?> · <?php echo date('d/m/Y', strtotime($d->updated_at ?: $d->created_at)); ?></li>
<?php endforeach; ?>
</ul>
