<?php
/*
 * @ https://EasyToYou.eu - IonCube v14 Decoder Online
 * @ PHP 7.4
 * @ Decoder version: 1.0.2
 * @ Release: 10/08/2022
 */

// Decoded file for php version 74.
if (!defined("WHMCS")) {
    exit("This file cannot be accessed directly");
}
date_default_timezone_set("America/Sao_Paulo");
if (!defined("ROOTDIR")) {
    define("ROOTDIR", dirname(__DIR__, 2));
}
include_once ROOTDIR . "/modules/gateways/efipixmpay/functions.php";
add_hook("InvoiceCreation", 1, "efipixmpay_Criar_Fatura");
add_hook("InvoiceCreated", 1, "efipixmpay_Criar_Fatura");
add_hook("DailyCronJob", 1, function ($vars) {
    try {
        WHMCS\Database\Capsule::connection()->getPdo();
        $logDirBase = __DIR__ . "/../modules/gateways/efipixmpay/logs";
        if (!file_exists($logDirBase)) {
            return NULL;
        }
        $thirtyDaysAgo = Carbon\Carbon::now()->subDays(30);
        $directories = glob($logDirBase . "/*", GLOB_ONLYDIR);
        foreach ($directories as $dir) {
            $dirName = basename($dir);
            if (preg_match("/^\\d{4}-\\d{2}-\\d{2}\$/", $dirName)) {
                $dirDate = Carbon\Carbon::createFromFormat("Y-m-d", $dirName);
                if ($dirDate && $dirDate->lessThan($thirtyDaysAgo)) {
                    $files = glob($dir . "/*");
                    foreach ($files as $file) {
                        if (is_file($file)) {
                            unlink($file);
                        }
                    }
                    rmdir($dir);
                }
            }
        }
    } catch (Exception $e) {
        WHMCS\Database\Capsule::table("tblactivitylog")->insert(["description" => "Erro ao limpar logs de gateway: " . $e->getMessage(), "date" => Carbon\Carbon::now()->toDateTimeString(), "user" => "System", "ipaddr" => "127.0.0.1"]);
    }
});
add_hook("EmailPreSend", 1, function ($vars) {
    try {
        if (empty($vars["messagename"]) || empty($vars["relid"])) {
            logActivity("EmailPreSend: Parâmetros inválidos - " . json_encode($vars));
            return [];
        }
        $email_template_name = $vars["messagename"];
        $relid = (int) $vars["relid"];
        $templates_relevantes = ["Invoice Created", "Invoice Modified", "Invoice Payment Reminder", "First Invoice Overdue Notice", "Second Invoice Overdue Notice", "Third Invoice Overdue Notice", "Invoice Payment Confirmation"];
        if (!in_array($email_template_name, $templates_relevantes, true)) {
            return [];
        }
        $invoice = WHMCS\Database\Capsule::table("tblinvoices")->where("id", $relid)->value("total");
        if ($invoice->paymentmethod != "efipixmpay") {
            return [];
        }
        if ($invoice === NULL) {
            logActivity("EmailPreSend: Fatura não encontrada - ID: " . $relid);
            return [];
        }
        $tolerance = 0;
        $is_zero = abs((float) $invoice - 0) < $tolerance;
        if ($is_zero) {
            logActivity("EmailPreSend: Abortado envio para fatura ID: " . $relid . " - Template: " . $email_template_name . " - Valor: " . $invoice);
            return ["abortsend" => true];
        }
    } catch (Exception $e) {
        logActivity("EmailPreSend ERRO: " . $e->getMessage() . " - RelID: " . ($relid ?? "N/A"));
    }
    return [];
});
add_hook("EmailTplMergeFields", 1, function ($vars) {
    try {
        $gatewayModuleName = "efipixmpay";
        $gatewayAtivo = WHMCS\Database\Capsule::table("tblpaymentgateways")->where("gateway", $gatewayModuleName)->where("setting", "convertto")->first();
        if (!$gatewayAtivo) {
            logActivity("EmailTplMergeFields: Gateway " . $gatewayModuleName . " não está ativo");
            return [];
        }
        $merge_fields = ["efipix_button" => "Button com link pagamento via Pix", "efipix_qrcode" => "QrCode do Pix", "efipix_copiaecola" => "Copia e Cola do Pix"];
        logActivity("EmailTplMergeFields: Campos do Pix adicionados para o gateway " . $gatewayModuleName);
        return $merge_fields;
    } catch (Exception $e) {
        logActivity("EmailTplMergeFields ERRO: " . $e->getMessage());
        return [];
    }
});
add_hook("EmailPreSend", 1, function ($vars) {
    $invoiceid = $vars["relid"];
    $email_template = $vars["messagename"];
    $target_templates = ["Invoice Created", "Invoice Modified", "Invoice Payment Reminder", "First Invoice Overdue Notice", "Second Invoice Overdue Notice", "Third Invoice Overdue Notice"];
    $merge_fields = [];
    $gatewayModuleName = "efipixmpay";
    $gateway_ativo = WHMCS\Database\Capsule::table("tblpaymentgateways")->where("gateway", $gatewayModuleName)->where("setting", "convertto")->first();
    if (!$gateway_ativo) {
        return NULL;
    }
    $GATEWAY = [];
    $tblEfi = WHMCS\Database\Capsule::table("mod_efimpay")->where("fatura_id", $invoiceid)->where("status", "ATIVA")->first();
    if (!$tblEfi) {
        return NULL;
    }
    if (in_array($email_template, $target_templates)) {
        $GATEWAY = getGatewayVariables($gatewayModuleName);
        if (empty($GATEWAY["type"])) {
            logActivity("[EFI PIX MPAY] EmailPreSend ignorado: gateway " . $gatewayModuleName . " desativado.");
            return NULL;
        }
        if ($GATEWAY["paymentmethod"] == $gatewayModuleName) {
            $url_destino = $tblEfi->link;
            $copiaecola = $tblEfi->qrcode;
            $qrcode = $tblEfi->qrcode_image;
            $pixefi = new efipixmpay($GATEWAY["efipixmpay_licensa"]);
            $statuslicenca = $pixefi->checkLicense();
            if ($statuslicenca["status"] != "Active") {
                logActivity("[EFI PIX MPAY] EmailPreSend A Licença do Módulo não está ATIVA.");
                return NULL;
            }
            $systemurl = rtrim(App::getSystemUrl(), "/");
            $nomeArquivo = $invoiceid . ".png";
            $imagemdoqrcode = $systemurl . "/modules/gateways/efipixmpay/pix/" . $nomeArquivo;
            $retorno = $pixefi->salvarQrCodeComoPng($qrcode, $nomeArquivo);
            $imagemdopix = empty($GATEWAY["efipixmpay_imagem_botao"]) ? $systemurl . "/modules/gateways/" . $gatewayModuleName . "/images/gerar_pix.png" : $GATEWAY["efipixmpay_imagem_botao"];
            $merge_fields["efipix_button"] = "\r\n      <div style=\"text-align: center;\">\r\n      <a href=\"" . $url_destino . "\" title=\"Pagar com pix - Clique Aqui\">\r\n      <img border=\"0\" src=\"" . $imagemdopix . "\" alt=\"Pagar com Pix\">\r\n      </a>\r\n      </div>";
            $merge_fields["efipix_copiaecola"] = "\r\n      <br>\r\n      <div style=\"text-align: center;\">\r\n      Você também pode utilizar o Copia e Cola do Pix: \r\n      <br>\r\n      <span style=\"font-size: 12px; color: #000000\">\r\n      <strong>\r\n      <br>" . $copiaecola . "<br>\r\n      </strong>\r\n      </span>\r\n      </div>";
            if ($retorno) {
                $merge_fields["efipix_qrcode"] = "\r\n        <div style=\"text-align: center;\">\r\n        Pague seu pix com o QrCode abaixo: \r\n        <br>\r\n        <br>\r\n        <span style=\"font-size: 12px; color: #000000\">\r\n        <strong>\r\n        <img border=\"0\" width=\"200\" height=\"200\" src=\"" . $imagemdoqrcode . "\">\r\n        </strong>\r\n        </span>\r\n        </div>";
            }
        }
    }
    return $merge_fields;
});
add_hook("InvoicePaid", 1, function ($vars) {
    $iddainvoice = $vars["invoiceid"];
    $gatewayModuleName = "efipixmpay";
    $gateway_ativo = WHMCS\Database\Capsule::table("tblpaymentgateways")->where("gateway", $gatewayModuleName)->where("setting", "convertto")->first();
    if (!$gateway_ativo) {
        return NULL;
    }
    $GATEWAY = [];
    $GATEWAY = getGatewayVariables($gatewayModuleName);
    if (empty($GATEWAY["type"])) {
        logActivity("[EFI PIX MPAY] InvoicePaid ignorado: gateway " . $gatewayModuleName . " desativado.");
    } else {
        $invoice = WHMCS\Database\Capsule::table("tblinvoices")->where("id", $iddainvoice)->first();
        if ($invoice->paymentmethod != $gatewayModuleName) {
            return NULL;
        }
        $clientId = $GATEWAY["efipixmpay_clientIdProd"];
        $clientSecret = $GATEWAY["efipixmpay_clientSecretProd"];
        $certificate = $GATEWAY["efipixmpay_certificado"];
        $chavepix = $GATEWAY["efipixmpay_chavepix"];
        $systemurl = $GATEWAY["systemurl"];
        $licenca = $GATEWAY["efipixmpay_licensa"];
        $PathCertificado = ROOTDIR . "/modules/gateways/efipixmpay/certs/" . $certificate;
        $pixefi = new efipixmpay($licenca);
        $statuslicenca = $pixefi->checkLicense();
        if ($statuslicenca["status"] != "Active") {
            logActivity("[EFI PIX MPAY] InvoicePaid A Licença do Módulo não está ATIVA.");
        } else {
            $tblEfi = WHMCS\Database\Capsule::table("mod_efimpay")->where("fatura_id", $iddainvoice)->first();
            if (!$tblEfi) {
                return NULL;
            }
            $update = ["status" => "REMOVIDA_PELO_USUARIO_RECEBEDOR"];
            WHMCS\Database\Capsule::table("mod_efimpay")->where("fatura_id", "=", $iddainvoice)->update($update);
            $autorizacao = base64_encode($clientId . ":" . $clientSecret);
            $accessTokenRetorno = $pixefi->ApiGetAcessToken($autorizacao, $PathCertificado);
            $accessToken = $accessTokenRetorno["access_token"];
            $urldocallback = $systemurl . "modules/gateways/" . $gatewayModuleName . "/webhook/?hmac=" . $gatewayModuleName . "&ignorar=";
            $WEBHOOK = $pixefi->WebhookExiste($chavepix, $accessToken, $PathCertificado);
            if (isset($WEBHOOK["nome"])) {
                $cadastrar_WEBHOOK = $pixefi->CadastrarWebhook($urldocallback, $chavepix, $accessToken, $PathCertificado);
            }
            $txid = $tblEfi->txid;
            $dadosdopix = $pixefi->PegarStatusDoPix($txid, $PathCertificado, $accessToken);
            $status = $dadosdopix["status"];
            if ($status == "REMOVIDA_PELO_USUARIO_RECEBEDOR") {
                return NULL;
            }
            $body = ["status" => "REMOVIDA_PELO_USUARIO_RECEBEDOR"];
            $retorno = $pixefi->ModificarDadosdoPix($txid, $body, $accessToken, $PathCertificado);
            if (NULL == $retorno["calendario"]["criacao"] && NULL == $retorno["txid"] && isset($retorno["type"])) {
                $erroMsg = $retorno["detail"];
                logActivity($erroMsg);
            } else if (NULL !== $retorno["calendario"]["criacao"] && NULL !== $retorno["txid"] && $retorno["status"] == "REMOVIDA_PELO_USUARIO_RECEBEDOR") {
                logActivity("[EFI PIX MPAY] Removido na EFI o Pix - Invoice ID: " . $iddainvoice);
            }
        }
    }
});
add_hook("InvoiceCancelled", 1, function ($vars) {
    $iddainvoice = $vars["invoiceid"];
    $gatewayModuleName = "efipixmpay";
    $gateway_ativo = WHMCS\Database\Capsule::table("tblpaymentgateways")->where("gateway", $gatewayModuleName)->where("setting", "convertto")->first();
    if (!$gateway_ativo) {
        return NULL;
    }
    $GATEWAY = [];
    $GATEWAY = getGatewayVariables($gatewayModuleName);
    if (empty($GATEWAY["type"])) {
        logActivity("[EFI PIX MPAY] InvoiceCancelled ignorado: gateway " . $gatewayModuleName . " desativado.");
    } else {
        $invoice = WHMCS\Database\Capsule::table("tblinvoices")->where("id", $iddainvoice)->first();
        if ($invoice->paymentmethod != $gatewayModuleName) {
            return NULL;
        }
        $clientId = $GATEWAY["efipixmpay_clientIdProd"];
        $clientSecret = $GATEWAY["efipixmpay_clientSecretProd"];
        $certificate = $GATEWAY["efipixmpay_certificado"];
        $chavepix = $GATEWAY["efipixmpay_chavepix"];
        $systemurl = $GATEWAY["systemurl"];
        $licenca = $GATEWAY["efipixmpay_licensa"];
        $PathCertificado = ROOTDIR . "/modules/gateways/efipixmpay/certs/" . $certificate;
        $pixefi = new efipixmpay($licenca);
        $statuslicenca = $pixefi->checkLicense();
        if ($statuslicenca["status"] != "Active") {
            logActivity("[EFI PIX MPAY] InvoiceCancelled A Licença do Módulo não está ATIVA.");
        } else {
            $tblEfi = WHMCS\Database\Capsule::table("mod_efimpay")->where("fatura_id", $iddainvoice)->first();
            if (!$tblEfi) {
                return NULL;
            }
            $update = ["status" => "REMOVIDA_PELO_USUARIO_RECEBEDOR"];
            WHMCS\Database\Capsule::table("mod_efimpay")->where("fatura_id", "=", $iddainvoice)->update($update);
            $autorizacao = base64_encode($clientId . ":" . $clientSecret);
            $accessTokenRetorno = $pixefi->ApiGetAcessToken($autorizacao, $PathCertificado);
            $accessToken = $accessTokenRetorno["access_token"];
            $urldocallback = $systemurl . "modules/gateways/" . $gatewayModuleName . "/webhook/?hmac=" . $gatewayModuleName . "&ignorar=";
            $WEBHOOK = $pixefi->WebhookExiste($chavepix, $accessToken, $PathCertificado);
            if (isset($WEBHOOK["nome"])) {
                $cadastrar_WEBHOOK = $pixefi->CadastrarWebhook($urldocallback, $chavepix, $accessToken, $PathCertificado);
            }
            $txid = $tblEfi->txid;
            $dadosdopix = $pixefi->PegarStatusDoPix($txid, $PathCertificado, $accessToken);
            if (isset($dadosdopix["error"])) {
                logActivity("[EFI PIX MPAY] Falha ao cancelar a Invoice ID: " . $iddainvoice . " na EFI. Motivo: " . $dadosdopix["error"] . " - " . $dadosdopix["error_description"]);
            } else {
                $status = $dadosdopix["status"];
                if ($status == "REMOVIDA_PELO_USUARIO_RECEBEDOR") {
                    return NULL;
                }
                $body = ["status" => "REMOVIDA_PELO_USUARIO_RECEBEDOR"];
                $retorno = $pixefi->ModificarDadosdoPix($txid, $body, $accessToken, $PathCertificado);
                if (NULL == $retorno["calendario"]["criacao"] && NULL == $retorno["txid"] && isset($retorno["type"])) {
                    $erroMsg = $retorno["detail"];
                    logActivity($erroMsg);
                } else if (NULL !== $retorno["calendario"]["criacao"] && NULL !== $retorno["txid"] && $retorno["status"] == "REMOVIDA_PELO_USUARIO_RECEBEDOR") {
                    logActivity("[EFI PIX MPAY] Removido na EFI o Pix - Invoice ID: " . $iddainvoice);
                }
            }
        }
    }
});
add_hook("InvoiceUnpaid", 1, function ($vars) {
    $iddainvoice = $vars["invoiceid"];
    $gatewayModuleName = "efipixmpay";
    $gateway_ativo = WHMCS\Database\Capsule::table("tblpaymentgateways")->where("gateway", $gatewayModuleName)->where("setting", "convertto")->first();
    if (!$gateway_ativo) {
        return NULL;
    }
    $GATEWAY = [];
    $GATEWAY = getGatewayVariables($gatewayModuleName);
    if (empty($GATEWAY["type"])) {
        logActivity("[EFI PIX MPAY] InvoiceUnpaid ignorado: gateway " . $gatewayModuleName . " desativado.");
    } else {
        $invoice = WHMCS\Database\Capsule::table("tblinvoices")->where("id", $iddainvoice)->first();
        if ($invoice->paymentmethod != $gatewayModuleName) {
            return NULL;
        }
        $clientId = $GATEWAY["efipixmpay_clientIdProd"];
        $clientSecret = $GATEWAY["efipixmpay_clientSecretProd"];
        $certificate = $GATEWAY["efipixmpay_certificado"];
        $chavepix = $GATEWAY["efipixmpay_chavepix"];
        $systemurl = $GATEWAY["systemurl"];
        $licenca = $GATEWAY["efipixmpay_licensa"];
        $descontoantecipado = $GATEWAY["efipixmpay_descontoantecipado"];
        $percentualdescontoantecipado = $GATEWAY["efipixmpay_percentualdescontoantecipado"];
        $PathCertificado = ROOTDIR . "/modules/gateways/efipixmpay/certs/" . $certificate;
        $pixefi = new efipixmpay($licenca);
        $statuslicenca = $pixefi->checkLicense();
        if ($statuslicenca["status"] != "Active") {
            logActivity("[EFI PIX MPAY] InvoicePaid A Licença do Módulo não está ATIVA.");
        } else {
            $tblEfi = WHMCS\Database\Capsule::table("mod_efimpay")->where("fatura_id", $iddainvoice)->first();
            if (!$tblEfi) {
                return NULL;
            }
            $update = ["status" => "ATIVA"];
            $client = WHMCS\Database\Capsule::table("tblclients")->where("id", $invoice->userid)->first();
            WHMCS\Database\Capsule::table("mod_efimpay")->where("fatura_id", "=", $iddainvoice)->update($update);
            $autorizacao = base64_encode($clientId . ":" . $clientSecret);
            $accessTokenRetorno = $pixefi->ApiGetAcessToken($autorizacao, $PathCertificado);
            $accessToken = $accessTokenRetorno["access_token"];
            $urldocallback = $systemurl . "modules/gateways/" . $gatewayModuleName . "/webhook/?hmac=" . $gatewayModuleName . "&ignorar=";
            $WEBHOOK = $pixefi->WebhookExiste($chavepix, $accessToken, $PathCertificado);
            if (isset($WEBHOOK["nome"]) && $WEBHOOK["nome"] === "webhook_nao_encontrado") {
                $cadastrar_WEBHOOK = $pixefi->CadastrarWebhook($urldocallback, $chavepix, $accessToken, $PathCertificado);
            }
            $txid = $tblEfi->txid;
            $dadosdopix = $pixefi->PegarStatusDoPix($txid, $PathCertificado, $accessToken);
            $status = $dadosdopix["status"];
            if ($status == "ATIVA") {
                return NULL;
            }
            if ($status === "REMOVIDA_PELO_USUARIO_RECEBEDOR") {
                $dataVencida = $pixefi->ValidarDatas($invoice->duedate, date("Y-m-d"));
                $valorInvoice = $invoice->total;
                $aplicarCredito = $GATEWAY["efipixmpay_temcredito"];
                if (!empty($aplicarCredito) && $aplicarCredito === "on" && $client->credit != "0.00") {
                    $valorInvoice = $pixefi->aplicarCreditoNaInvoice($client->credit, $invoice->total);
                }
                list($valordaInvoice, $dataVencimento, $valordastaxas, $totalJurosMulta) = $pixefi->calculateInvoiceValues($pixefi, $valorInvoice, $invoice->duedate, $dataVencida, $GATEWAY);
                $valordoDesconto = 0;
                $valorSemDesconto = (float) ($valordaInvoice + $valordastaxas + $totalJurosMulta);
                if (!empty($descontoantecipado) && !empty($percentualdescontoantecipado)) {
                    $dias = (int) $descontoantecipado;
                    $percentualdesc = (float) $percentualdescontoantecipado;
                    $datalimite = date("Y-m-d", strtotime("-" . $dias . " days", strtotime($dataVencimento)));
                    $dataLimiteDesconto = $pixefi->ValidarDatas($datalimite, date("Y-m-d"));
                    if (!$dataLimiteDesconto) {
                        $valorDoDesconto = $pixefi->naoArredondar($valorSemDesconto * $percentualdesc / 100);
                    }
                }
                $valorTotal = $pixefi->formatValue($valorSemDesconto - $valorDoDesconto);
                $resultado = $pixefi->createNewPix($pixefi, $invoice, $client, $GATEWAY, $valorSemDesconto, $dataVencimento, $accessToken);
                if (isset($resultado) && $resultado["status"] === "ATIVA") {
                    if ($resultado["loc"]["id"]) {
                        $responseLoc = $pixefi->GerarPixLocation($resultado["loc"]["id"], $PathCertificado, $accessToken);
                        $PixCopiaCola = $responseLoc["qrcode"];
                        $imagemQrcode = $responseLoc["imagemQrcode"];
                        $linkvisual = $responseLoc["linkVisualizacao"];
                        $pixefi->UpdateInsertTbl_Efi(["iddainvoice" => $invoice->id, "clientId" => $invoice->userid, "txid" => $resultado["txid"], "qrcode" => $PixCopiaCola, "qrcode_image" => $imagemQrcode, "link" => $linkvisual, "valor" => $pixefi->formatValue($resultado["valor"]["original"]), "tipo" => "pix", "duedate" => $resultado["calendario"]["dataDeVencimento"], "status" => $resultado["status"]]);
                    }
                } else if (in_array($resultado["status"], [400, 403, 404, 503])) {
                    logActivity("[EFI PIX MPAY] Erro: " . $resultado["status"] . " - " . $resultado["title"] . " - " . $resultado["detail"]);
                    return "<div class=\"alert alert-warning text-center\">\r\n        Erro: " . $resultado["status"] . " - " . $resultado["title"] . " - " . $resultado["detail"] . "\r\n        </div>";
                }
            } else {
                $body = ["status" => "ATIVA"];
                $retorno = $pixefi->ModificarDadosdoPix($txid, $body, $accessToken, $PathCertificado);
                if (NULL == $retorno["calendario"]["criacao"] && NULL == $retorno["txid"] && isset($retorno["type"])) {
                    $erroMsg = $retorno["detail"];
                    logActivity($erroMsg);
                    return NULL;
                }
                if (NULL !== $retorno["calendario"]["criacao"] && NULL !== $retorno["txid"] && $retorno["status"] == "ATIVA") {
                    logActivity("[EFI PIX MPAY] Cobrança Reativada na EFI o Pix - Invoice ID: " . $iddainvoice);
                }
            }
        }
    }
});
add_hook("UpdateInvoiceTotal", 1, function ($vars) {
    $invoiceId = $vars["invoiceid"];
    $gatewayModuleName = "efipixmpay";
    if (!efipixmpay_gateway_esta_configurado($gatewayModuleName)) {
        return NULL;
    }
    $GATEWAY = getGatewayVariables($gatewayModuleName);
    if (!$GATEWAY || empty($GATEWAY["type"])) {
        logActivity("[EFI PIX MPAY] UpdateInvoiceTotal ignorado: gateway " . $gatewayModuleName . " desativado.");
    } else {
        $valor_minimo = $GATEWAY["efipixmpay_valorminimo"];
        $pixefi = new efipixmpay($GATEWAY["efipixmpay_licensa"]);
        $invoice = $pixefi->getInvoice_Unpaid($invoiceId);
        if (!$invoice) {
            logActivity("[EFI PIX MPAY] Invoice ID: " . $invoiceId . " não localizada.");
        } else {
            if ($invoice->paymentmethod != $gatewayModuleName) {
                return NULL;
            }
            $client = WHMCS\Database\Capsule::table("tblclients")->where("id", $invoice->userid)->first();
            if (!$client) {
                logActivity("[EFI PIX MPAY] Client ID: " . $invoice->userid . " não localizado.");
            } else {
                $statuslicenca = $pixefi->checkLicense();
                if ($statuslicenca["status"] != "Active") {
                    logActivity("[EFI PIX MPAY] InvoiceUnpaid A Licença do Módulo não está ATIVA.");
                } else {
                    $cpfCnpj = $pixefi->PegaCPFCNPJ($GATEWAY["efipixmpay_campocpfcnpj"], $client->id);
                    $cpfCnpj = preg_replace("/\\D/", "", $cpfCnpj);
                    if (!$pixefi->validador_cpf_cnpj($cpfCnpj)) {
                        logActivity("[EFI PIX MPAY] O CPF/CNPJ do client ID: " . $invoice->userid . " está em branco ou não é um documento válido.");
                    } else {
                        $endparts = explode(",", $client->address1, 2);
                        $endereco = trim($endparts[0]);
                        $endnumero = isset($endparts[1]) ? trim($endparts[1]) : "S/N";
                        $enderecocompleto = $endereco . ", " . $endnumero . ", " . $client->address2;
                        $PathCertificado = ROOTDIR . "/modules/gateways/efipixmpay/certs/" . $GATEWAY["efipixmpay_certificado"];
                        $urldocallback = $GATEWAY["systemurl"] . "modules/gateways/efipixmpay/webhook/?hmac=efipixmpay&ignorar=";
                        $autorizacao = base64_encode($GATEWAY["efipixmpay_clientIdProd"] . ":" . $GATEWAY["efipixmpay_clientSecretProd"]);
                        $accessTokenRetorno = $pixefi->ApiGetAcessToken($autorizacao, $PathCertificado);
                        $accessToken = $accessTokenRetorno["access_token"];
                        if (isset($accessTokenRetorno["error"])) {
                            logActivity("[EFI PIX MPAY] O Token Gerado é Inválido: " . $accessTokenRetorno["error_description"]);
                        } else {
                            $listarHook = $pixefi->ListarWebhook($autorizacao, "2024-12-30T16:01:35Z", date("Y-m-d") . "T16:01:35Z", $PathCertificado, $accessToken);
                            $webhook_Existe = $pixefi->getWebhookUrlByKey($listarHook, $GATEWAY["efipixmpay_chavepix"]);
                            if (!$webhook_Existe || $webhook_Existe != $urldocallback) {
                                $pixefi->configureWebhook($urldocallback, $accessToken, $PathCertificado, $GATEWAY["efipixmpay_chavepix"]);
                            }
                            $cobranca = WHMCS\Database\Capsule::table("mod_efimpay")->where("fatura_id", (int) $invoiceId)->first();
                            $dataVencida = $pixefi->ValidarDatas($invoice->duedate, date("Y-m-d"));
                            $valorInvoice = $invoice->total;
                            $aplicarCredito = $GATEWAY["efipixmpay_temcredito"];
                            if (!empty($aplicarCredito) && $aplicarCredito === "on" && $client->credit != 0) {
                                $valorInvoice = $pixefi->aplicarCreditoNaInvoice($client->credit, $invoice->total);
                            }
                            list($valordaInvoice, $dataVencimento, $valordastaxas, $totalJurosMulta) = $pixefi->calculateInvoiceValues($pixefi, $valorInvoice, $invoice->duedate, $dataVencida, $GATEWAY);
                            $valordoDesconto = 0;
                            $valorSemDesconto = (float) ($valordaInvoice + $valordastaxas + $totalJurosMulta);
                            if (!empty($GATEWAY["efipixmpay_descontoantecipado"]) && !empty($GATEWAY["efipixmpay_percentualdescontoantecipado"])) {
                                $dias = (int) $GATEWAY["efipixmpay_descontoantecipado"];
                                $percentualdesc = (float) $GATEWAY["efipixmpay_percentualdescontoantecipado"];
                                $datalimite = date("Y-m-d", strtotime("-" . $dias . " days", strtotime($dataVencimento)));
                                $dataLimiteDesconto = $pixefi->ValidarDatas($datalimite, date("Y-m-d"));
                                if (!$dataLimiteDesconto) {
                                    $valorDoDesconto = $pixefi->naoArredondar($valorSemDesconto * $percentualdesc / 100);
                                }
                            }
                            $valorTotal = $pixefi->formatValue($valorSemDesconto - $valorDoDesconto);
                            if ($cobranca) {
                                $txid = $cobranca->txid;
                            } else {
                                $txid = $pixefi->gerarTxidPix($invoiceId);
                            }
                            $dadosdopix = $pixefi->PegarStatusDoPix($txid, $PathCertificado, $accessToken);
                            $criarPix = false;
                            $atualizarPix = false;
                            $manterPix = false;
                            if ($dadosdopix["status"] === 404) {
                                $criarPix = true;
                            }
                            if ($dadosdopix["status"] === "CONCLUIDA") {
                                if ($cobranca->status != "CONCLUIDA") {
                                    $pixefi->UpdateTbl_Efi_Status($invoice->id, "CONCLUIDA");
                                }
                                logActivity("[EFI PIX MPAY] Invoice #" . $invoice->id . " Já está com o Status PAGO.");
                            } else {
                                if ($dadosdopix["status"] === "REMOVIDA_PELO_USUARIO_RECEBEDOR") {
                                    if ($cobranca->status != "REMOVIDA_PELO_USUARIO_RECEBEDOR") {
                                        $pixefi->UpdateTbl_Efi_Status($invoice->id, "REMOVIDA_PELO_USUARIO_RECEBEDOR");
                                    }
                                    logActivity("[EFI PIX MPAY] Invoice #" . $invoice->id . " Não existe na EFI.");
                                    $criarPix = true;
                                }
                                if ($dadosdopix["status"] === "ATIVA") {
                                    if ($dataVencida || $cobranca->valor != $pixefi->formatValue($valorSemDesconto) || $dadosdopix["valor"]["original"] != $pixefi->formatValue($valorSemDesconto)) {
                                        $atualizarPix = true;
                                    } else {
                                        $manterPix = true;
                                    }
                                }
                                if ($criarPix) {
                                    $resultado = $pixefi->createNewPix($pixefi, $invoice, $client, $GATEWAY, $valorSemDesconto, $dataVencimento, $accessToken);
                                    if (isset($resultado) && $resultado["status"] === "ATIVA") {
                                        if ($resultado["loc"]["id"]) {
                                            $responseLoc = $pixefi->GerarPixLocation($resultado["loc"]["id"], $PathCertificado, $accessToken);
                                            $PixCopiaCola = $responseLoc["qrcode"];
                                            $imagemQrcode = $responseLoc["imagemQrcode"];
                                            $linkvisual = $responseLoc["linkVisualizacao"];
                                            $pixefi->UpdateInsertTbl_Efi(["iddainvoice" => $invoice->id, "clientId" => $invoice->userid, "txid" => $resultado["txid"], "qrcode" => $PixCopiaCola, "qrcode_image" => $imagemQrcode, "link" => $linkvisual, "valor" => $pixefi->formatValue($resultado["valor"]["original"]), "tipo" => "pix", "duedate" => $resultado["calendario"]["dataDeVencimento"], "status" => $resultado["status"]]);
                                        }
                                    } else if (in_array($resultado["status"], [400, 403, 404, 503])) {
                                        logActivity("[EFI PIX MPAY] Erro: " . $resultado["status"] . " - " . $resultado["title"] . " - " . $resultado["detail"]);
                                        return NULL;
                                    }
                                }
                                if ($manterPix) {
                                    if (isset($dadosdopix) && $dadosdopix["status"] === "ATIVA") {
                                        if ($dadosdopix["loc"]["id"]) {
                                            $responseLocation = $pixefi->GerarPixLocation($dadosdopix["loc"]["id"], $PathCertificado, $accessToken);
                                            $PixCopiaCola = $responseLocation["qrcode"];
                                            $imagemQrcode = $responseLocation["imagemQrcode"];
                                            $linkvisual = $responseLocation["linkVisualizacao"];
                                            $pixefi->UpdateInsertTbl_Efi(["iddainvoice" => $invoice->id, "clientId" => $invoice->userid, "txid" => $dadosdopix["txid"], "qrcode" => $PixCopiaCola, "qrcode_image" => $imagemQrcode, "link" => $linkvisual, "valor" => $pixefi->formatValue($dadosdopix["valor"]["original"]), "tipo" => "pix", "duedate" => $dadosdopix["calendario"]["dataDeVencimento"], "status" => $dadosdopix["status"]]);
                                        }
                                    } else if (in_array($dadosdopix["status"], [400, 403, 404, 503])) {
                                        logActivity("[EFI PIX MPAY] Erro: " . $dadosdopix["status"] . " - " . $dadosdopix["title"] . " - " . $dadosdopix["detail"]);
                                        return NULL;
                                    }
                                }
                                if ($atualizarPix && isset($dadosdopix) && $dadosdopix["status"] === "ATIVA") {
                                    $body = $pixefi->preparePixBody($pixefi, $invoice, $client, $cpfCnpj, $GATEWAY, $valorSemDesconto, $dataVencimento);
                                    if (isset($body["calendario"]["dataDeVencimento"])) {
                                        $responsePix = $pixefi->ModificarDadosdoPix($dadosdopix["txid"], $body, $accessToken, $PathCertificado);
                                    }
                                    if (isset($responsePix["loc"]["id"])) {
                                        $idLocationPix = $responsePix["loc"]["id"];
                                        $responseLoc = $pixefi->GerarPixLocation($idLocationPix, $PathCertificado, $accessToken);
                                        $PixCopiaCola = $responseLoc["qrcode"];
                                        $imagemQrcode = $responseLoc["imagemQrcode"];
                                        $linkvisual = $responseLoc["linkVisualizacao"];
                                        $pixefi->UpdateInsertTbl_Efi(["iddainvoice" => $invoice->id, "clientId" => $invoice->userid, "txid" => $responsePix["txid"], "qrcode" => $PixCopiaCola, "qrcode_image" => $imagemQrcode, "link" => $linkvisual, "valor" => $pixefi->formatValue($responsePix["valor"]["original"]), "tipo" => "pix", "duedate" => $responsePix["calendario"]["dataDeVencimento"], "status" => $responsePix["status"]]);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
});
add_hook("AfterCronJob", 1, function ($vars) {
    $gatewayModuleName = "efipixmpay";
    $gateway_ativo = WHMCS\Database\Capsule::table("tblpaymentgateways")->where("gateway", $gatewayModuleName)->where("setting", "convertto")->first();
    if (!$gateway_ativo) {
        return NULL;
    }
    $GATEWAY = [];
    $GATEWAY = getGatewayVariables($gatewayModuleName);
    if (empty($GATEWAY["type"])) {
        logActivity("[EFI PIX MPAY] AfterCronJob ignorado: gateway " . $gatewayModuleName . " desativado.");
    } else {
        $clientId = $GATEWAY["efipixmpay_clientIdProd"];
        $clientSecret = $GATEWAY["efipixmpay_clientSecretProd"];
        $licenca = $GATEWAY["efipixmpay_licensa"];
        $certificate = $GATEWAY["efipixmpay_certificado"];
        $taxafixa = $GATEWAY["efipixmpay_taxafixa"];
        $taxaperc = $GATEWAY["efipixmpay_taxapercentual"];
        $diasParaDesconto = $GATEWAY["efipixmpay_descontoantecipado"];
        $descperc = $GATEWAY["efipixmpay_percentualdescontoantecipado"];
        $PathCertificado = ROOTDIR . "/modules/gateways/efipixmpay/certs/" . $certificate;
        $pixefi = new efipixmpay($licenca);
        $autorizacao = base64_encode($clientId . ":" . $clientSecret);
        $accessTokenRetorno = $pixefi->ApiGetAcessToken($autorizacao, $PathCertificado);
        $accessToken = $accessTokenRetorno["access_token"];
        $invoicesEfi = WHMCS\Database\Capsule::table("mod_efimpay")->where("status", "ATIVA")->get();
        if (!$invoicesEfi) {
            return NULL;
        }
        foreach ($invoicesEfi as $invoiceEfi) {
            $txid = $invoiceEfi->txid;
            $dadosdopix = $pixefi->PegarStatusDoPix($txid, $PathCertificado, $accessToken);
            $status = $dadosdopix["status"];
            if ($status === "CONCLUIDA") {
                $transacao = $dadosdopix["pix"][0]["endToEndId"];
                $valorPago = (float) $dadosdopix["pix"][0]["valor"];
                $dataPag = explode("T", $dadosdopix["pix"][0]["horario"]);
                $update = ["status" => "CONCLUIDA", "paiddate" => $dataPag[0], "transacao" => $transacao];
                WHMCS\Database\Capsule::table("mod_efimpay")->where("fatura_id", "=", $invoiceEfi->fatura_id)->update($update);
                $invoice = $pixefi->getInvoice_Unpaid($invoiceEfi->fatura_id);
                if ($invoice) {
                    $valordaTaxaFixa = !empty($taxafixa) ? (float) $taxafixa : 0;
                    $valordaTaxaPerc = !empty($taxaperc) ? (float) $invoice->total * (float) $taxaperc / 100 : 0;
                    $valordastaxas = $pixefi->formatValue($valordaTaxaFixa + $valordaTaxaPerc);
                    $transacaoexiste = localAPI("gettransactions", ["userid" => $invoice->userid, "transid" => $transacao, "invoiceid" => $invoiceEfi->fatura_id]);
                    if ($transacaoexiste["result"] == "success" && $transacaoexiste["totalresults"] == 0) {
                        $taxas = 0;
                        $credito = 0;
                        $valorinvoice = (float) $invoice->total;
                        if (0 < $valordastaxas) {
                            $taxas = number_format((float) $valordastaxas, 2, ".", "");
                            $atualizarFaturaTaxas = ["invoiceid" => $invoiceEfi->fatura_id, "newitemdescription" => ["EFI PIX MPAY: Taxa do Serviço"], "newitemamount" => [$taxas], "newitemtaxed" => [0]];
                            $res = localAPI("UpdateInvoice", $atualizarFaturaTaxas);
                            logActivity("[EFI PIX MPAY] - Adicionada taxa de serviço à fatura #" . $invoiceEfi->fatura_id . " | Valor: R\$ " . $taxas, $invoice->userid);
                        }
                        $ajuste = $pixefi->calcularMultaJurosDescontos($valorPago, $valorinvoice, $taxas, $invoice->duedate, date("Y-m-d"));
                        if ($ajuste < 0) {
                            $valorDesconto = $ajuste;
                            $aplicarDesconto = ["invoiceid" => $invoiceEfi->fatura_id, "newitemdescription" => ["EFI PIX MPAY: Desconto Pagamento Antecipado"], "newitemamount" => [$valorDesconto], "newitemtaxed" => [0]];
                            $res = localAPI("UpdateInvoice", $aplicarDesconto);
                            logActivity("[EFI PIX MPAY] - Aplicado desconto à fatura #" . $invoiceEfi->fatura_id . " | Valor: R\$ " . $valorDesconto, $invoice->userid);
                        } else if (0 < $ajuste) {
                            $valorMulta = $ajuste;
                            $aplicarMulta = ["invoiceid" => $invoiceEfi->fatura_id, "newitemdescription" => ["EFI PIX MPAY: Multas e Juros por Atraso"], "newitemamount" => [$valorMulta], "newitemtaxed" => [0]];
                            $res = localAPI("UpdateInvoice", $aplicarMulta);
                            logActivity("[EFI PIX MPAY] - Aplicada multa/juros à fatura #" . $invoiceEfi->fatura_id . " | Valor: R\$ " . $valorMulta, $invoice->userid);
                        } else {
                            logActivity("[EFI PIX MPAY] - Pagamento sem necessidade de ajuste na fatura #" . $invoiceEfi->fatura_id, $invoice->userid);
                        }
                        $transactionData = ["invoiceid" => $invoiceEfi->fatura_id, "gateway" => $gatewayModuleName, "transid" => (string) $transacao, "amount" => (float) $valorPago, "date" => date("Y-m-d H:i:s"), "fees" => (float) ($taxas + $valorMulta)];
                        localAPI("addinvoicepayment", $transactionData);
                    }
                }
            }
        }
    }
});
add_hook("DailyCronJob", 1, function ($vars) {
    $gatewayModuleName = "efipixmpay";
    $gateway_ativo = WHMCS\Database\Capsule::table("tblpaymentgateways")->where("gateway", $gatewayModuleName)->where("setting", "convertto")->first();
    if (!$gateway_ativo) {
        return NULL;
    }
    $GATEWAY = [];
    $GATEWAY = getGatewayVariables($gatewayModuleName);
    if (empty($GATEWAY["type"])) {
        logActivity("[EFI PIX MPAY] DailyCronJob ignorado: gateway " . $gatewayModuleName . " desativado.");
    } else {
        $clientId = $GATEWAY["efipixmpay_clientIdProd"];
        $clientSecret = $GATEWAY["efipixmpay_clientSecretProd"];
        $certificate = $GATEWAY["efipixmpay_certificado"];
        $chavepix = $GATEWAY["efipixmpay_chavepix"];
        $taxafixa = $GATEWAY["efipixmpay_taxafixa"];
        $taxaperc = $GATEWAY["efipixmpay_taxapercentual"];
        $desconto = $GATEWAY["efipixmpay_descontoantecipado"];
        $descperc = $GATEWAY["efipixmpay_percentualdescontoantecipado"];
        $timeout = (int) $GATEWAY["efipixmpay_tempoexpiracao"];
        $licenca = $GATEWAY["efipixmpay_licensa"];
        $multa = (int) $GATEWAY["efipixmpay_multaporatraso"];
        $juros = $GATEWAY["efipixmpay_jurosporatraso"];
        $paymentmethod = $GATEWAY["paymentmethod"];
        $systemurl = $GATEWAY["systemurl"];
        $aplicarCredito = $GATEWAY["efipixmpay_temcredito"];
        $descontoantecipado = $GATEWAY["efipixmpay_descontoantecipado"];
        $percentualdescontoantecipado = $GATEWAY["efipixmpay_percentualdescontoantecipado"];
        $PathCertificado = ROOTDIR . "/modules/gateways/efipixmpay/certs/" . $certificate;
        $pixefi = new efipixmpay($licenca);
        $statuslicenca = $pixefi->checkLicense();
        if ($statuslicenca["status"] != "Active") {
            return NULL;
        }
        if (!$pixefi->ArquivoExiste($PathCertificado)) {
            return NULL;
        }
        $hoje = date("Y-m-d");
        $novaData = date("Y-m-d", strtotime("-15 days", strtotime($hoje)));
        foreach (WHMCS\Database\Capsule::table("tblinvoices")->where("status", "=", "Unpaid")->where("paymentmethod", "=", "efipixmpay")->whereRaw("DATE(date) BETWEEN ? AND ?", [$novaData, $hoje])->get() as $invoices) {
            if (!$invoices || $invoices->total === 0 || $invoices->paymentmethod !== $gatewayModuleName) {
            } else {
                $valorminimo = !empty($GATEWAY["efipixmpay_valor_minimo"]) ?? 0;
                if ((float) $invoices->total < (float) $valorminimo) {
                } else if ($invoices) {
                    $cobranca = WHMCS\Database\Capsule::table("mod_efimpay")->where("fatura_id", "=", $invoices->id)->first();
                    $client = WHMCS\Database\Capsule::table("tblclients")->where("id", $invoices->userid)->first();
                    if (!$client) {
                    } else {
                        $cpfcnpj = $pixefi->PegaCPFCNPJ($GATEWAY["efipixmpay_campocpfcnpj"], $client->id);
                        $cpfcnpj = preg_replace("/\\D/", "", $cpfcnpj);
                        if (empty($cpfcnpj)) {
                        } else {
                            $dadodocliente = $pixefi->Get_DadosClientWHMCS($client);
                            $autorizacao = base64_encode($clientId . ":" . $clientSecret);
                            $accessTokenRetorno = $pixefi->ApiGetAcessToken($autorizacao, $PathCertificado);
                            $accessToken = $accessTokenRetorno["access_token"];
                            if (isset($accessTokenRetorno["error"])) {
                            } else {
                                $listarHook = $pixefi->ListarWebhook($autorizacao, "2024-12-30T16:01:35Z", date("Y-m-d") . "T16:01:35Z", $PathCertificado, $accessToken);
                                $webhook_Existe = $pixefi->getWebhookUrlByKey($listarHook, $chavepix);
                                if (!$webhook_Existe || $webhook_Existe != $urldocallback) {
                                    $pixefi->configureWebhook($urldocallback, $accessToken, $PathCertificado, $chavepix);
                                }
                                $valorInvoice = $invoices->total;
                                $dataVencida = $pixefi->ValidarDatas($invoices->duedate, date("Y-m-d"));
                                if (!empty($aplicarCredito) && $aplicarCredito === "on" && $client->credit != 0) {
                                    $valorInvoice = $pixefi->aplicarCreditoNaInvoice($client->credit, $invoices->total);
                                }
                                list($valordaInvoice, $dataVencimento, $valordastaxas, $totalJurosMulta) = $pixefi->calculateInvoiceValues($pixefi, $valorInvoice, $invoices->duedate, $dataVencida, $GATEWAY);
                                $valordoDesconto = 0;
                                $valorSemDesconto = (float) ($valordaInvoice + $valordastaxas + $totalJurosMulta);
                                if (!empty($descontoantecipado) && !empty($percentualdescontoantecipado)) {
                                    $dias = (int) $descontoantecipado;
                                    $percentualdesc = (float) $percentualdescontoantecipado;
                                    $datalimite = date("Y-m-d", strtotime("-" . $dias . " days", strtotime($dataVencimento)));
                                    $dataLimiteDesconto = $pixefi->ValidarDatas($datalimite, date("Y-m-d"));
                                    if (!$dataLimiteDesconto) {
                                        $valorDoDesconto = $pixefi->naoArredondar($valorSemDesconto * $percentualdesc / 100);
                                    }
                                }
                                $valorTotal = $pixefi->formatValue($valorSemDesconto - $valorDoDesconto);
                                if ($cobranca) {
                                    $txid = $cobranca->txid;
                                } else {
                                    $txid = $pixefi->gerarTxidPix($iddainvoice);
                                }
                                $dadosdopix = $pixefi->PegarStatusDoPix($txid, $PathCertificado, $accessToken);
                                if ($dadosdopix["status"] === "CONCLUIDA") {
                                    if ($cobranca->status != "CONCLUIDA") {
                                        $pixefi->UpdateTbl_Efi_Status($invoice->id, "CONCLUIDA");
                                    }
                                } else if ($dadosdopix["status"] === "REMOVIDA_PELO_USUARIO_RECEBEDOR") {
                                    if ($cobranca->status != "REMOVIDA_PELO_USUARIO_RECEBEDOR") {
                                        $pixefi->UpdateTbl_Efi_Status($invoice->id, "REMOVIDA_PELO_USUARIO_RECEBEDOR");
                                    }
                                } else if ($dadosdopix["status"] == 404) {
                                    $resultado = $pixefi->createNewPix($pixefi, $invoices, $client, $GATEWAY, $valorSemDesconto, $dataVencimento, $accessToken);
                                    if (isset($resultado) && $resultado["status"] === "ATIVA") {
                                        if ($resultado["loc"]["id"]) {
                                            $responseLoc = $pixefi->GerarPixLocation($resultado["loc"]["id"], $PathCertificado, $accessToken);
                                            $PixCopiaCola = $responseLoc["qrcode"];
                                            $imagemQrcode = $responseLoc["imagemQrcode"];
                                            $linkvisual = $responseLoc["linkVisualizacao"];
                                            $pixefi->UpdateInsertTbl_Efi(["iddainvoice" => $invoices->id, "clientId" => $invoices->userid, "txid" => $resultado["txid"], "qrcode" => $PixCopiaCola, "qrcode_image" => $imagemQrcode, "link" => $linkvisual, "valor" => $pixefi->formatValue($resultado["valor"]["original"]), "tipo" => "pix", "duedate" => $resultado["calendario"]["dataDeVencimento"], "status" => $resultado["status"]]);
                                        }
                                    } else if (in_array($resultado["status"], [400, 403, 404, 503])) {
                                        logActivity("[EFI PIX MPAY] Erro: " . $resultado["status"] . " - " . $resultado["title"] . " - " . $resultado["detail"]);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
});
function efipixmpay_gateway_esta_configurado($gatewayModuleName = "efipixmpay")
{
    return (bool) WHMCS\Database\Capsule::table("tblpaymentgateways")->where("gateway", $gatewayModuleName)->where("setting", "convertto")->first();
}
function efipixmpay_Criar_Fatura($vars)
{
    $iddainvoice = $vars["invoiceid"];
    $gatewayModuleName = "efipixmpay";
    $gateway_ativo = WHMCS\Database\Capsule::table("tblpaymentgateways")->where("gateway", $gatewayModuleName)->where("setting", "convertto")->first();
    if (!$gateway_ativo) {
        return NULL;
    }
    $GATEWAY = [];
    $GATEWAY = getGatewayVariables($gatewayModuleName);
    if (empty($GATEWAY["type"])) {
        logActivity("[EFI PIX MPAY] InvoiceCreation ignorado: gateway " . $gatewayModuleName . " desativado.");
    } else {
        $clientId = $GATEWAY["efipixmpay_clientIdProd"];
        $clientSecret = $GATEWAY["efipixmpay_clientSecretProd"];
        $certificate = $GATEWAY["efipixmpay_certificado"];
        $chavepix = $GATEWAY["efipixmpay_chavepix"];
        $taxafixa = $GATEWAY["efipixmpay_taxafixa"];
        $taxaperc = $GATEWAY["efipixmpay_taxapercentual"];
        $desconto = $GATEWAY["efipixmpay_descontoantecipado"];
        $descperc = $GATEWAY["efipixmpay_percentualdescontoantecipado"];
        $timeout = (int) $GATEWAY["efipixmpay_tempoexpiracao"];
        $licenca = $GATEWAY["efipixmpay_licensa"];
        $multa = (int) $GATEWAY["efipixmpay_multaporatraso"];
        $juros = $GATEWAY["efipixmpay_jurosporatraso"];
        $paymentmethod = $GATEWAY["paymentmethod"];
        $systemurl = $GATEWAY["systemurl"];
        $aplicarCredito = $GATEWAY["efipixmpay_temcredito"];
        $descontoantecipado = $GATEWAY["efipixmpay_descontoantecipado"];
        $percentualdescontoantecipado = $GATEWAY["efipixmpay_percentualdescontoantecipado"];
        $PathCertificado = ROOTDIR . "/modules/gateways/efipixmpay/certs/" . $certificate;
        $urldocallback = $systemurl . "modules/gateways/efipixmpay/webhook/?hmac=efipixmpay&ignorar=";
        $pixefi = new efipixmpay($licenca);
        $invoice = WHMCS\Database\Capsule::table("tblinvoices")->where("id", $iddainvoice)->where("status", "Unpaid")->first();
        if (!$invoice || $invoice->total === "0.00" || $invoice->paymentmethod !== $paymentmethod) {
            return NULL;
        }
        $client = WHMCS\Database\Capsule::table("tblclients")->where("id", $invoice->userid)->first();
        if (!$client) {
            return NULL;
        }
        $cobranca = WHMCS\Database\Capsule::table("mod_efimpay")->where("fatura_id", $iddainvoice)->first();
        $statuslicenca = $pixefi->checkLicense();
        if ($statuslicenca["status"] != "Active") {
            logActivity("[EFI PIX MPAY] InvoiceCreation A Licença do Módulo não está ATIVA.");
        } else if (!$pixefi->ArquivoExiste($PathCertificado)) {
            logActivity("[EFI BOLETO MPAY] Certificado de Produção não encontrado.");
        } else {
            $cpfcnpj = $pixefi->PegaCPFCNPJ($GATEWAY["efipixmpay_campocpfcnpj"], $client->id);
            $cpfcnpj = preg_replace("/\\D/", "", $cpfcnpj);
            if (empty($cpfcnpj)) {
                logActivity("[EFI PIX MPAY] CPF/CNPJ vazio para o cliente: " . $client->firstname . " " . $client->lastname);
            } else {
                $valorminimo = !empty($GATEWAY["efipixmpay_valor_minimo"]) ?? 0;
                if ((float) $invoice->total < (float) $valorminimo) {
                    logActivity("[EFI PIX MPAY] O Valor da Cobrança R\$ " . $invoice->total . " é inferior ao Valor Mínimo do Gateway que é de R\$ : (float)" . $valorminimo);
                } else {
                    $dadodocliente = $pixefi->Get_DadosClientWHMCS($client);
                    $autorizacao = base64_encode($clientId . ":" . $clientSecret);
                    $accessTokenRetorno = $pixefi->ApiGetAcessToken($autorizacao, $PathCertificado);
                    $accessToken = $accessTokenRetorno["access_token"];
                    if (isset($accessTokenRetorno["error"])) {
                        logActivity("[EFI PIX MPAY] O Token Gerado é Inválido: " . $accessTokenRetorno["error_description"]);
                    } else {
                        $listarHook = $pixefi->ListarWebhook($autorizacao, "2024-12-30T16:01:35Z", date("Y-m-d") . "T16:01:35Z", $PathCertificado, $accessToken);
                        $webhook_Existe = $pixefi->getWebhookUrlByKey($listarHook, $chavepix);
                        if (!$webhook_Existe || $webhook_Existe != $urldocallback) {
                            $pixefi->configureWebhook($urldocallback, $accessToken, $PathCertificado, $chavepix);
                        }
                        $tblEfi = WHMCS\Database\Capsule::table("mod_efimpay")->where("fatura_id", $iddainvoice)->first();
                        if ($tblEfi) {
                            return NULL;
                        }
                        $valorInvoice = $invoice->total;
                        $dataVencida = $pixefi->ValidarDatas($invoice->duedate, date("Y-m-d"));
                        if (!empty($aplicarCredito) && $aplicarCredito === "on" && $client->credit != "0.00") {
                            $valorInvoice = $pixefi->aplicarCreditoNaInvoice($client->credit, $invoice->total);
                        }
                        list($valordaInvoice, $dataVencimento, $valordastaxas, $totalJurosMulta) = $pixefi->calculateInvoiceValues($pixefi, $valorInvoice, $invoice->duedate, $dataVencida, $GATEWAY);
                        $valordoDesconto = 0;
                        $valorSemDesconto = (float) ($valordaInvoice + $valordastaxas + $totalJurosMulta);
                        if (!empty($descontoantecipado) && !empty($percentualdescontoantecipado)) {
                            $dias = (int) $descontoantecipado;
                            $percentualdesc = (float) $percentualdescontoantecipado;
                            $datalimite = date("Y-m-d", strtotime("-" . $dias . " days", strtotime($dataVencimento)));
                            $dataLimiteDesconto = $pixefi->ValidarDatas($datalimite, date("Y-m-d"));
                            if (!$dataLimiteDesconto) {
                                $valorDoDesconto = $pixefi->naoArredondar($valorSemDesconto * $percentualdesc / 100);
                            }
                        }
                        $valorTotal = $pixefi->formatValue($valorSemDesconto - $valorDoDesconto);
                        if ($cobranca) {
                            $txid = $cobranca->txid;
                        } else {
                            $txid = $pixefi->gerarTxidPix($iddainvoice);
                        }
                        $dadosdopix = $pixefi->PegarStatusDoPix($txid, $PathCertificado, $accessToken);
                        $criarPix = false;
                        $atualizarPix = false;
                        $manterPix = false;
                        if ($dadosdopix["status"] == 404) {
                            $criarPix = true;
                        }
                        if ($dadosdopix["status"] === "CONCLUIDA") {
                            if ($cobranca->status != "CONCLUIDA") {
                                $pixefi->UpdateTbl_Efi_Status($invoice->id, "CONCLUIDA");
                            }
                            logActivity("[EFI PIX MPAY] Invoice #" . $invoice->id . " Já está com o Status PAGO.");
                        } else {
                            if ($dadosdopix["status"] === "REMOVIDA_PELO_USUARIO_RECEBEDOR") {
                                if ($cobranca->status != "REMOVIDA_PELO_USUARIO_RECEBEDOR") {
                                    $pixefi->UpdateTbl_Efi_Status($invoice->id, "REMOVIDA_PELO_USUARIO_RECEBEDOR");
                                }
                                logActivity("[EFI PIX MPAY] Invoice #" . $invoice->id . " Não existe na EFI.");
                                $criarPix = true;
                            }
                            if ($dadosdopix["status"] === "ATIVA") {
                                if ($dataVencida || $cobranca->valor != $pixefi->formatValue($valorSemDesconto) || $dadosdopix["valor"]["original"] != $pixefi->formatValue($valorSemDesconto)) {
                                    $atualizarPix = true;
                                } else {
                                    $manterPix = true;
                                }
                            }
                            if ($criarPix) {
                                $resultado = $pixefi->createNewPix($pixefi, $invoice, $client, $GATEWAY, $valorSemDesconto, $dataVencimento, $accessToken);
                                if (isset($resultado) && $resultado["status"] === "ATIVA") {
                                    if ($resultado["loc"]["id"]) {
                                        $responseLoc = $pixefi->GerarPixLocation($resultado["loc"]["id"], $PathCertificado, $accessToken);
                                        $PixCopiaCola = $responseLoc["qrcode"];
                                        $imagemQrcode = $responseLoc["imagemQrcode"];
                                        $linkvisual = $responseLoc["linkVisualizacao"];
                                        $pixefi->UpdateInsertTbl_Efi(["iddainvoice" => $invoice->id, "clientId" => $invoice->userid, "txid" => $resultado["txid"], "qrcode" => $PixCopiaCola, "qrcode_image" => $imagemQrcode, "link" => $linkvisual, "valor" => $pixefi->formatValue($resultado["valor"]["original"]), "tipo" => "pix", "duedate" => $resultado["calendario"]["dataDeVencimento"], "status" => $resultado["status"]]);
                                    }
                                } else if (in_array($resultado["status"], [400, 403, 404, 503])) {
                                    logActivity("[EFI PIX MPAY] Erro: " . $resultado["status"] . " - " . $resultado["title"] . " - " . $resultado["detail"]);
                                    return NULL;
                                }
                            }
                            if ($manterPix) {
                                if (isset($dadosdopix) && $dadosdopix["status"] === "ATIVA") {
                                    if ($dadosdopix["loc"]["id"]) {
                                        $responseLocation = $pixefi->GerarPixLocation($dadosdopix["loc"]["id"], $PathCertificado, $accessToken);
                                        $PixCopiaCola = $responseLocation["qrcode"];
                                        $imagemQrcode = $responseLocation["imagemQrcode"];
                                        $linkvisual = $responseLocation["linkVisualizacao"];
                                        $pixefi->UpdateInsertTbl_Efi(["iddainvoice" => $invoice->id, "clientId" => $invoice->userid, "txid" => $dadosdopix["txid"], "qrcode" => $PixCopiaCola, "qrcode_image" => $imagemQrcode, "link" => $linkvisual, "valor" => $pixefi->formatValue($dadosdopix["valor"]["original"]), "tipo" => "pix", "duedate" => $dadosdopix["calendario"]["dataDeVencimento"], "status" => $dadosdopix["status"]]);
                                    }
                                } else if (in_array($dadosdopix["status"], [400, 403, 404, 503])) {
                                    logActivity("[EFI PIX MPAY] Erro: " . $dadosdopix["status"] . " - " . $dadosdopix["title"] . " - " . $dadosdopix["detail"]);
                                    return NULL;
                                }
                            }
                            if ($atualizarPix && isset($dadosdopix) && $dadosdopix["status"] === "ATIVA") {
                                $body = $pixefi->preparePixBody($pixefi, $invoice, $client, $cpfCnpj, $GATEWAY, $valorSemDesconto, $dataVencimento);
                                if (isset($body["calendario"]["dataDeVencimento"])) {
                                    $responsePix = $pixefi->ModificarDadosdoPix($dadosdopix["txid"], $body, $accessToken, $PathCertificado);
                                }
                                if (isset($responsePix["loc"]["id"])) {
                                    $idLocationPix = $responsePix["loc"]["id"];
                                    $responseLoc = $pixefi->GerarPixLocation($idLocationPix, $PathCertificado, $accessToken);
                                    $PixCopiaCola = $responseLoc["qrcode"];
                                    $imagemQrcode = $responseLoc["imagemQrcode"];
                                    $linkvisual = $responseLoc["linkVisualizacao"];
                                    $pixefi->UpdateInsertTbl_Efi(["iddainvoice" => $invoice->id, "clientId" => $invoice->userid, "txid" => $responsePix["txid"], "qrcode" => $PixCopiaCola, "qrcode_image" => $imagemQrcode, "link" => $linkvisual, "valor" => $pixefi->formatValue($responsePix["valor"]["original"]), "tipo" => "pix", "duedate" => $responsePix["calendario"]["dataDeVencimento"], "status" => $responsePix["status"]]);
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}

?>