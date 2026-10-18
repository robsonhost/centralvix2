<?php
/*
 * @ https://EasyToYou.eu - IonCube v14 Decoder Online
 * @ PHP 7.4
 * @ Decoder version: 1.0.2
 * @ Release: 10/08/2022
 */

// Decoded file for php version 74.
header("Content-Type: text/plain");
if (!defined("ROOTDIR")) {
    define("ROOTDIR", dirname(__DIR__, 3));
}
if (!defined("WHMCS")) {
    include_once ROOTDIR . "/init.php";
}
include_once ROOTDIR . "/modules/gateways/efipixmpay/functions.php";
$whmcs->load_function("gateway");
$gatewayModuleName = "efipixmpay";
$gateway_ativo = WHMCS\Database\Capsule::table("tblpaymentgateways")->where("gateway", $gatewayModuleName)->where("setting", "convertto")->first();
if (!$gateway_ativo) {
    return NULL;
}
$GATEWAY = [];
$GATEWAY = getGatewayVariables($gatewayModuleName);
if (!$GATEWAY["type"]) {
    logActivity("[EFI BOLETO MPAY] O Gateway de Pagamento não está ativo.");
} else {
    $systemurl = $GATEWAY["systemurl"];
    $licenca = $GATEWAY["efipixmpay_licensa"];
    $certificate = $GATEWAY["efipixmpay_certificado"];
    $clientId = $GATEWAY["efipixmpay_clientIdProd"];
    $clientSecret = $GATEWAY["efipixmpay_clientSecretProd"];
    $pixefi = new efipixmpay($licenca);
    $PathCertificado = ROOTDIR . "/modules/gateways/efipixmpay/certs/" . $certificate;
    $autorizacao = base64_encode($clientId . ":" . $clientSecret);
    $accessTokenRetorno = $pixefi->ApiGetAcessToken($autorizacao, $PathCertificado);
    $accessToken = $accessTokenRetorno["access_token"];
    if (isset($_POST["iddainvoice"])) {
        $iddainvoice = $_POST["iddainvoice"];
        $cobranca = WHMCS\Database\Capsule::table("mod_efimpay")->where("fatura_id", (int) $iddainvoice)->first();
        $invoice = WHMCS\Database\Capsule::table("tblinvoices")->where("id", (int) $iddainvoice)->first();
        if ($cobranca) {
            $dadosDoPix = $pixefi->PegarStatusDoPix($cobranca->txid, $PathCertificado, $accessToken);
            if ($cobranca->status === "CONCLUIDA" || $dadosDoPix["status"] === "CONCLUIDA") {
                if ($invoice->status === "Unpaid") {
                }
                echo "OK";
            } else {
                echo "NAO";
            }
        }
    } else {
        echo "ERROR: iddainvoice não fornecido";
    }
}

?>