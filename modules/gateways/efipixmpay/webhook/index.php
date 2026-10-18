<?php
/*
 * @ https://EasyToYou.eu - IonCube v14 Decoder Online
 * @ PHP 7.4
 * @ Decoder version: 1.0.2
 * @ Release: 10/08/2022
 */

// Decoded file for php version 74.
header("Content-Type: application/json");
$start_time = microtime(true);
if (!defined("ROOTDIR")) {
    define("ROOTDIR", dirname(__DIR__, 4));
}
if (!defined("WHMCS")) {
    require_once ROOTDIR . "/init.php";
}
include_once ROOTDIR . "/modules/gateways/efipixmpay/functions.php";
$whmcs->load_function("gateway");
$gatewayModuleName = "efipixmpay";
$gatewayAtivo = WHMCS\Database\Capsule::table("tblpaymentgateways")->where("gateway", $gatewayModuleName)->where("setting", "convertto")->first();
if (!$gatewayAtivo) {
    logActivity("[EFI PIX MPAY] Gateway não configurado ou desativado.");
    http_response_code(400);
    exit;
}
$GATEWAY = getGatewayVariables($gatewayModuleName);
if (!$GATEWAY["type"]) {
    logActivity("[EFI PIX MPAY] O Gateway de Pagamento não está ativo.");
} else {
    $clientId = $GATEWAY["efipixmpay_clientIdProd"];
    $clientSecret = $GATEWAY["efipixmpay_clientSecretProd"];
    $certificate = $GATEWAY["efipixmpay_certificado"];
    $usuarioWhmcs = $GATEWAY["efipixmpay_usuario_whmcs"];
    $licenca = $GATEWAY["efipixmpay_licensa"];
    $taxafixa = $GATEWAY["efipixmpay_taxafixa"];
    $taxaperc = $GATEWAY["efipixmpay_taxapercentual"];
    $diasParaDesconto = $GATEWAY["efipixmpay_descontoantecipado"];
    $descperc = $GATEWAY["efipixmpay_percentualdescontoantecipado"];
    $systemurl = $GATEWAY["systemurl"];
    $aplicarCredito = $GATEWAY["efipixmpay_temcredito"];
    $sandbox = false;
    $pixefi = new efipixmpay($licenca);
    $body = json_decode(file_get_contents("php://input"), true);
    if (!$body) {
        logActivity("[EFI PIX MPAY] Erro ao capturar o corpo do retorno.");
        http_response_code(400);
        exit;
    }
    if (!isset($body["pix"][0]["endToEndId"]) || !isset($body["pix"][0]["txid"]) || !isset($body["pix"][0]["valor"]) || !isset($body["pix"][0]["horario"])) {
        exit;
    }
    $txid = $body["pix"][0]["txid"];
    $valorPago = (float) $body["pix"][0]["valor"];
    $transacao = $body["pix"][0]["endToEndId"];
    if (strpos($txid, "EFIMPAY") === false) {
        logActivity("[EFI PIX MPAY] TXID inválido ou não contém \"EFIMPAY\".");
        http_response_code(400);
        exit;
    }
    $explodeTxid = explode("EFIMPAY", $txid);
    $iddainvoice = (int) $explodeTxid[0];
    $horarioExplode = explode("T", $body["pix"][0]["horario"]);
    $horario = $horarioExplode[0];
    if (!$iddainvoice) {
        logActivity("[EFI PIX MPAY] Não foi possível validar a ID da Invoice no retorno da EFI.");
        http_response_code(400);
        exit;
    }
    $invoice = WHMCS\Database\Capsule::table("tblinvoices")->where("id", "=", $iddainvoice)->where("status", "=", "Unpaid")->first();
    if (!$invoice) {
        logActivity("[EFI PIX MPAY] Não foi possível validar a ID da Invoice do WHMCS.");
        http_response_code(400);
        exit;
    }
    $valordaTaxaFixa = !empty($taxafixa) ? (float) $taxafixa : 0;
    $valordaTaxaPerc = !empty($taxaperc) ? (float) $invoice->total * (float) $taxaperc / 100 : 0;
    $valordastaxas = $pixefi->formatValue($valordaTaxaFixa + $valordaTaxaPerc);
    $transacaoexiste = localAPI("gettransactions", ["userid" => $invoice->userid, "transid" => $transacao, "invoiceid" => $iddainvoice]);
    if ($transacaoexiste["result"] === "success" && 0 < $transacaoexiste["totalresults"]) {
        logActivity("[EFI PIX MPAY] Transação já registrada para a Invoice #ID: " . $iddainvoice);
        exit;
    }
    if ($transacaoexiste["result"] != "success") {
        logActivity("[EFI PIX MPAY] Erro ao verificar a Transação para a Invoice #ID: " . $iddainvoice, $invoice->userid);
        exit;
    }
    $taxas = 0;
    $credito = 0;
    $valorinvoice = (float) $invoice->total;
    if (0 < $valordastaxas) {
        $taxas = number_format((float) $valordastaxas, 2, ".", "");
        $atualizarFaturaTaxas = ["invoiceid" => $iddainvoice, "newitemdescription" => ["EFI PIX MPAY: Taxa do Serviço"], "newitemamount" => [$taxas], "newitemtaxed" => [0]];
        $res = localAPI("UpdateInvoice", $atualizarFaturaTaxas);
        logActivity("[EFI PIX MPAY] - Adicionada taxa de serviço à fatura #" . $iddainvoice . " | Valor: R\$ " . $taxas, $invoice->userid);
    }
    $ajuste = $pixefi->calcularMultaJurosDescontos($valorPago, $valorinvoice, $taxas, $invoice->duedate, date("Y-m-d"));
    if ($ajuste < 0) {
        $valorDesconto = $ajuste;
        $aplicarDesconto = ["invoiceid" => $iddainvoice, "newitemdescription" => ["EFI PIX MPAY: Desconto Pagamento Antecipado"], "newitemamount" => [$valorDesconto], "newitemtaxed" => [0]];
        $res = localAPI("UpdateInvoice", $aplicarDesconto);
        logActivity("[EFI PIX MPAY] - Aplicado desconto à fatura #" . $iddainvoice . " | Valor: R\$ " . $valorDesconto, $invoice->userid);
    } else if (0 < $ajuste) {
        $valorMulta = $ajuste;
        $aplicarMulta = ["invoiceid" => $iddainvoice, "newitemdescription" => ["EFI PIX MPAY: Multas e Juros por Atraso"], "newitemamount" => [$valorMulta], "newitemtaxed" => [0]];
        $res = localAPI("UpdateInvoice", $aplicarMulta);
        logActivity("[EFI PIX MPAY] - Aplicada multa/juros à fatura #" . $iddainvoice . " | Valor: R\$ " . $valorMulta, $invoice->userid);
    } else {
        logActivity("[EFI PIX MPAY] - Pagamento sem necessidade de ajuste na fatura #" . $iddainvoice, $invoice->userid);
    }
    $transactionData = ["invoiceid" => $iddainvoice, "gateway" => $gatewayModuleName, "transid" => (string) $transacao, "amount" => (float) $valorPago, "date" => date("Y-m-d H:i:s"), "fees" => (float) ($taxas + $valorMulta)];
    $results = localAPI("addinvoicepayment", $transactionData);
    logActivity("[EFI PIX MPAY] Pagamento Efetuado com Sucesso Invoice ID: " . $iddainvoice, $invoice->userid);
    $qtde = WHMCS\Database\Capsule::table("mod_efimpay")->where("fatura_id", "=", $iddainvoice)->count();
    if (0 < $qtde) {
        $update = ["status" => "CONCLUIDA", "paiddate" => $horario, "transacao" => (string) $transacao, "valor" => (float) $valorPago];
        WHMCS\Database\Capsule::table("mod_efimpay")->where("fatura_id", "=", $iddainvoice)->update($update);
    } else {
        $insert_data = ["transacao" => (string) $transacao, "user_id" => $invoice->userid, "fatura_id" => $invoice->id, "paiddate" => $horario, "status" => "CONCLUIDA", "valor" => (float) $valorPago];
        WHMCS\Database\Capsule::table("mod_efimpay")->insert([$insert_data]);
    }
    $execution_time = round(microtime(true) - $start_time, 4);
    http_response_code(200);
    echo json_encode(["status" => "success", "tempo" => $execution_time]);
    exit;
}

?>