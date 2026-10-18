<?php
if (!defined('WHMCS')) { die('This file cannot be accessed directly'); }
$doc = CentralVixSignDocumentService::get($documentId, $clientId);
if (!$doc) { echo '<div class="cvix-sig-empty">Documento não encontrado.</div>'; return; }
if (!in_array($doc->status, ['enviado', 'visualizado'], true)) {
    echo '<div class="cvix-sig-empty">Este documento não está pendente de assinatura.</div>';
    return;
}
CentralVixSignDocumentService::markViewed($documentId, $clientId);
$pdfUrl = CentralVixSignModule::downloadUrl($documentId, 'original', true);
$ajaxSign = CentralVixSignModule::ajaxUrl('sign', true);
$ajaxReject = CentralVixSignModule::ajaxUrl('reject', true);
$legal = CentralVixSignModule::dbSetting('legal_notice', '');
$timeline = CentralVixSignEvidenceService::timeline($documentId);
?>
<div class="cvix-sig-sign-page">
<h2><?php echo CentralVixSignSecurity::e($doc->title); ?></h2>
<div class="cvix-sig-meta">
<span>Versão <?php echo (int)$doc->version; ?></span>
<span>Hash: <code><?php echo CentralVixSignSecurity::e(substr($doc->document_hash, 0, 16)); ?>…</code></span>
<span>ID: <?php echo CentralVixSignSecurity::e($doc->transaction_id); ?></span>
</div>

<div class="cvix-sig-legal-box"><?php echo CentralVixSignSecurity::e($legal); ?></div>

<section class="cvix-sig-viewer">
<h3>Documento completo</h3>
<iframe src="<?php echo CentralVixSignSecurity::e($pdfUrl); ?>" class="cvix-sig-pdf-frame" title="Documento PDF"></iframe>
</section>

<section class="cvix-sig-sign-form">
<h3>Aceite eletrônico</h3>
<label class="cvix-sig-check required"><input type="checkbox" id="cvix-accept-terms"> Li e concordo com o documento apresentado.</label>
<label>CPF/CNPJ (quando aplicável)<input type="text" id="cvix-signer-doc" maxlength="50" placeholder="Opcional"></label>

<div class="cvix-sig-draw-wrap">
<p>Assinatura desenhada (opcional — evidência complementar)</p>
<canvas id="cvix-signature-pad" width="400" height="120"></canvas>
<button type="button" id="cvix-clear-sig" class="cvix-sig-btn outline sm">Limpar</button>
</div>

<div class="cvix-sig-actions">
<button type="button" id="cvix-sign-btn" class="cvix-sig-btn">Assinar documento</button>
<button type="button" id="cvix-reject-btn" class="cvix-sig-btn danger outline">Recusar</button>
</div>
<p id="cvix-sign-msg"></p>
</section>

<section class="cvix-sig-timeline">
<h3>Registro</h3>
<ul><?php foreach ($timeline as $e): $f = CentralVixSignEvidenceService::formatEvent($e); ?>
<li><?php echo CentralVixSignSecurity::e($f['date']); ?> — <?php echo CentralVixSignSecurity::e($e->message); ?></li>
<?php endforeach; ?></ul>
</section>
</div>

<input type="hidden" id="cvix-csrf" value="<?php echo CentralVixSignSecurity::e($csrf); ?>">
<script>
window.CVIX_SIGN = {
    documentId: <?php echo (int)$documentId; ?>,
    signUrl: <?php echo json_encode($ajaxSign); ?>,
    rejectUrl: <?php echo json_encode($ajaxReject); ?>,
    listUrl: <?php echo json_encode($modulelink); ?>
};
</script>
