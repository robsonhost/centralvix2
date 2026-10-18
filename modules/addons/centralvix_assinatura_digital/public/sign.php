<?php

define('CLIENTAREA', true);

$whmcsPath = dirname(__DIR__, 3);
if (!is_file($whmcsPath . '/init.php')) {
    $whmcsPath = dirname(__DIR__, 4);
}
require_once $whmcsPath . '/init.php';

require_once dirname(__DIR__) . '/lib/Security.php';
CentralVixSignModule::autoload();

$token = $_GET['t'] ?? '';
if (strlen($token) < 32) {
    http_response_code(403);
    exit('Token inválido.');
}

$valid = CentralVixSignSignatureService::validateToken($token);
if (!$valid['success']) {
    http_response_code(403);
    exit(CentralVixSignSecurity::e($valid['message']));
}

$documentId = (int) $valid['document_id'];
$clientId = (int) $valid['client_id'];
$doc = CentralVixSignDocumentService::get($documentId, $clientId);

if (!$doc || !in_array($doc->status, ['enviado', 'visualizado'], true)) {
    http_response_code(404);
    exit('Documento indisponível.');
}

CentralVixSignDocumentService::markViewed($documentId, $clientId);

$pdfUrl = CentralVixSignModule::systemUrl() . '/modules/addons/centralvix_assinatura_digital/public/view.php?t=' . urlencode($token);
$legal = CentralVixSignModule::dbSetting('legal_notice', '');

?><!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Assinar — <?php echo CentralVixSignSecurity::e($doc->title); ?></title>
<link rel="stylesheet" href="../assets/css/client.css?v=1.0.0">
</head>
<body class="cvix-sig-public">
<div class="cvix-sig-sign-page">
<h1><?php echo CentralVixSignSecurity::e($doc->title); ?></h1>
<div class="cvix-sig-legal-box"><?php echo CentralVixSignSecurity::e($legal); ?></div>
<iframe src="<?php echo CentralVixSignSecurity::e($pdfUrl); ?>" class="cvix-sig-pdf-frame"></iframe>
<label class="cvix-sig-check"><input type="checkbox" id="cvix-accept-terms"> Li e concordo com o documento apresentado.</label>
<label>CPF/CNPJ<input type="text" id="cvix-signer-doc" maxlength="50"></label>
<canvas id="cvix-signature-pad" width="400" height="120"></canvas>
<button type="button" id="cvix-clear-sig" class="cvix-sig-btn outline sm">Limpar</button>
<button type="button" id="cvix-sign-btn" class="cvix-sig-btn">Assinar documento</button>
<button type="button" id="cvix-reject-btn" class="cvix-sig-btn danger outline">Recusar</button>
<p id="cvix-sign-msg"></p>
</div>
<script>
window.CVIX_SIGN = {
    documentId: <?php echo $documentId; ?>,
    accessToken: <?php echo json_encode($token); ?>,
    signUrl: <?php echo json_encode(CentralVixSignModule::systemUrl() . '/modules/addons/centralvix_assinatura_digital/public/api.php?action=sign'); ?>,
    rejectUrl: <?php echo json_encode(CentralVixSignModule::systemUrl() . '/modules/addons/centralvix_assinatura_digital/public/api.php?action=reject'); ?>,
    publicMode: true
};
</script>
<script src="../assets/js/client.js?v=1.0.0"></script>
</body>
</html>
