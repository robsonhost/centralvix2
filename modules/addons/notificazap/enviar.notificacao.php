<?php
/*
 * @ https://EasyToYou.eu - IonCube v14 Decoder Online
 * @ PHP 7.4
 * @ Decoder version: 1.0.2
 * @ Release: 10/08/2022
 */

// Decoded file for php version 74.
require __DIR__ . "/../../../init.php";
include_once __DIR__ . "/../../../modules/addons/notificazap/config.php";
include_once __DIR__ . "/../../../modules/addons/notificazap/lib/notificazapCore.php";
include_once __DIR__ . "/../../../modules/addons/notificazap/lib/notificaWhatsApp.php";
include_once __DIR__ . "/../../../modules/addons/notificazap/notificavalida.php";
header("Content-Type: application/json");
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["status" => "error", "message" => "Método não permitido."]);
    exit;
}
if (!isset($_POST["template"]) || !isset($_POST["idinvoice"])) {
    echo json_encode(["status" => "error", "message" => "Dados inválidos."]);
    exit;
}
$template = filter_var($_POST["template"], FILTER_SANITIZE_STRING);
$idInvoice = filter_var($_POST["idinvoice"], FILTER_SANITIZE_NUMBER_INT);
if (empty($template) || empty($idInvoice)) {
    echo json_encode(["status" => "error", "message" => "Parâmetros obrigatórios estão ausentes."]);
    exit;
}
try {
    $core = new notificazapCore();
    $whatsapp = new notificaWhatsAppMpay($core->NOTIFICAZAP_Server(), $core->NOTIFICAZAP_Descriptografar($core->NOTIFICAZAP_KEY()), $core->NOTIFICAZAP_Token_Geral(), $core->NOTIFICAZAP_Telefone_Origem());
    $DOMINIO_WHMCS = $core->NOTIFICAZAP_Pega_Info_tblconfiguration("SystemURL");
    $obtem_Template = $core->NOTIFICAZAP_Pegar_Status_Mensagem_Template($template);
    $corpo_mensagem = $obtem_Template["corpo_mensagem"];
    $status_mensagem = $obtem_Template["status_mensagem"];
    if ($status_mensagem != "active") {
        echo json_encode(["status" => "error", "message" => "Verifique o Status do Template é requerido estar ativo para ser enviado."]);
        exit;
    }
    $invoice_MPAY = WHMCS\Database\Capsule::table("tblinvoices")->where("id", $idInvoice)->first();
    if ($invoice_MPAY->total === 0) {
        echo json_encode(["status" => "error", "message" => "A fatura está com valor '0.00'"]);
        exit;
    }
    $id_campo_receber_notificacao = $core->NOTIFICAZAP_Pega_Info_tbladdonmodules("addon_notificazap_receber_whatsapp");
    $receber_notificacao = $core->NOTIFICAZAP_Pega_Info_tblcustomfieldsvalues($id_campo_receber_notificacao, $invoice_MPAY->userid);
    if ($receber_notificacao == "Não") {
        echo json_encode(["status" => "error", "message" => "O Cliente não deseja receber Notificação por WhatsApp."]);
        exit;
    }
    if ($receber_notificacao != "Sim") {
        echo json_encode(["status" => "error", "message" => "Não há uma definição registrada no banco de dados informando se o cliente deseja ou não receber notificações. Acesse o perfil do cliente e clique em “Atualizar” para que essa informação seja salva corretamente."]);
        exit;
    }
    $linkautologin = NULL;
    if (WHMCS\Database\Capsule::table("tbladdonmodules")->where("module", "autologinmpay")->exists()) {
        $linkautologin = $core->NOTIFICAZAP_generateAutologinLink($invoice_MPAY->userid, $invoice_MPAY->id, "invoice");
    }
    if (WHMCS\Database\Capsule::schema()->hasTable("mod_nfasaasmpay")) {
        $notafiscal = WHMCS\Database\Capsule::table("mod_nfasaasmpay")->where("id_fatura", $invoice_MPAY->id)->first();
        if ($notafiscal) {
            $nfnum = $notafiscal->nf_num;
            $dataemissaonf = $notafiscal->data_emissao;
            $pdf = $notafiscal->pdf;
            $nfiddainvoice = $notafiscal->id_fatura;
            $nfvalor = $core->NOTIFICAZAP_AdjustValue($notafiscal->valor);
        }
    }
    $client = WHMCS\User\Client::find($invoice_MPAY->userid);
    $enviarlembretes = false;
    $enviarfaturapaga = false;
    $enviarnotafiscal = false;
    if ($template == "invoicereminder" || $template == "createdinvoice" || $template == "invoicecancelled" || $template == "faturavencendohoje") {
        $corpo_mensagem = str_replace(["%name%", "%invoiceid%", "%duedate%", "%value%", "%assinatura%", "%dominiowhmcs%", "%linkdainvoice%"], [$client->firstname, $invoice_MPAY->id, $core->NOTIFICAZAP_AdjustDate($invoice_MPAY->duedate), $core->NOTIFICAZAP_AdjustValue($invoice_MPAY->total), $core->NOTIFICAZAP_Assinatura_WhatsApp(), $DOMINIO_WHMCS, $linkautologin ?: $DOMINIO_WHMCS . "viewinvoice.php?id=" . $invoice_MPAY->id], $corpo_mensagem);
        $enviarlembretes = true;
    }
    if ($template == "servicealerta") {
        $corpo_mensagem = str_replace(["%name%", "%invoiceid%", "%duedate%", "%value%", "%assinatura%", "%dominiowhmcs%", "%days%", "%linkdainvoice%"], [$client->firstname, $invoice_MPAY->id, $core->NOTIFICAZAP_AdjustDate($invoice_MPAY->duedate), $core->NOTIFICAZAP_AdjustValue($invoice_MPAY->total), $core->NOTIFICAZAP_Assinatura_WhatsApp($invoice_MPAY->duedate), $DOMINIO_WHMCS, $core->NOTIFICAZAP_diasEntreDatas($invoice_MPAY->duedate), $linkautologin ?: $DOMINIO_WHMCS . "viewinvoice.php?id=" . $invoice_MPAY->id], $corpo_mensagem);
        $enviarlembretes = true;
    }
    if ($template == "invoicepaid") {
        $corpo_mensagem = str_replace(["%name%", "%invoiceid%", "%datapagamento%", "%value%", "%assinatura%", "%dominiowhmcs%", "%linkdainvoice%"], [$client->firstname, $invoice_MPAY->id, $core->NOTIFICAZAP_converterDataHoraParaBrasileira($invoice_MPAY->datepaid), $core->NOTIFICAZAP_AdjustValue($invoice_MPAY->total), $core->NOTIFICAZAP_Assinatura_WhatsApp(), $DOMINIO_WHMCS, $linkautologin ?: $DOMINIO_WHMCS . "viewinvoice.php?id=" . $invoice_MPAY->id], $corpo_mensagem);
        $enviarfaturapaga = true;
    }
    if ($template == "notafiscalasaas" && isset($nfnum)) {
        $corpo_mensagem = str_replace(["%name%", "%assinatura%", "%dominiowhmcs%", "%numeronf%", "%nfdataemissao%", "%invoiceid%", "%value%"], [$client->firstname, $core->NOTIFICAZAP_Assinatura_WhatsApp(), $DOMINIO_WHMCS, $nfnum, $core->NOTIFICAZAP_AdjustDate($dataemissaonf), $nfiddainvoice, $nfvalor], $corpo_mensagem);
        $enviarnotafiscal = true;
    }
    $paymentDescription = WHMCS\Database\Capsule::table("tblpaymentgateways")->where(["gateway" => $invoice_MPAY->paymentmethod, "setting" => "name"])->value("value");
    if ($paymentDescription) {
        $corpo_mensagem = str_replace("%formapgto%", $paymentDescription, $corpo_mensagem);
    }
    $nomedoproduto = $core->NOTIFICAZAP_getProductNameByInvoiceId($invoice_MPAY->id);
    $nomedoproduto != "naoexiste" ? substr($nomedoproduto, 0, 250) : "";
    $corpo_mensagem = str_replace();
    $phone = $core->NOTIFICAZAP_AdjustNumber($client);
    $phone_whatsapp = $whatsapp->Valida_Number_WhatsApp($phone);
    $existsPhone = $phone_whatsapp[0]["exists"] ?? NULL;
    if (!$existsPhone) {
        $core->NOTIFICAZAP_LogWarning("Número de telefone não é WhatsApp válido", ["cliente id" => $client->id, "phone" => $phone, "action" => "invoice_notification_send"]);
        $core->NOTIFICAZAP_CadastrarLogFalhas($client->id, "Telefone " . $phone . " do Cliente não é um número de WhatsApp");
        echo json_encode(["status" => "error", "message" => "Número de telefone não é WhatsApp válido."]);
        return NULL;
    }
    switch ($invoice_MPAY->paymentmethod) {
        case "asaasboletompay":
        case "asaaspixmpay":
        case "asaascartaompay":
        case "cobrancaasaasmpay":
            if ($enviarlembretes) {
                $cobrancaasaas = WHMCS\Database\Capsule::table("mod_cobrancaasaasmpay")->where("fatura_id", "=", $invoice_MPAY->id)->where("user_id", "=", $invoice_MPAY->userid)->where("status", "=", "unpaid")->first();
                if ($cobrancaasaas) {
                    include "notifica.asaas.php";
                } else {
                    $core->NOTIFICAZAP_LogWarning("Fatura #" . $invoice_MPAY->id . " Inexistente na Tabela mod_cobrancaasaasmpay", ["Fatura ID" => $invoice_MPAY->id, "action" => "Clique no Botão >>Ver Como Cliente<< que o módulo irá recriar a cobrança!"]);
                    echo json_encode(["status" => "error", "message" => "Cobrança Inexistente na tabela mod_cobrancaasaasmpay - Clique no Botão >>Ver Como Cliente<< que o módulo irá recriar a cobrança!"]);
                }
            }
            if ($enviarnotafiscal) {
                $retorno = $core->EnviarMensagemComBoleto_NOTIFICAZAP($phone, $pdf, $nfnum, $corpo_mensagem);
            }
            break;
        case "paghiper":
        case "paghiper_pix":
            if ($enviarlembretes) {
                $paghiper = WHMCS\Database\Capsule::table("mod_paghiper")->where("order_id", "=", $invoice_MPAY->id)->first();
                if ($paghiper) {
                    include "notifica.paghiper.php";
                }
            }
            break;
        case "mpagopixmpay":
            if ($enviarlembretes) {
                $mercadoPix = WHMCS\Database\Capsule::table("mod_mpagopixmpay")->where("fatura_id", $invoice_MPAY->id)->where("status", "pending")->first();
                if ($mercadoPix) {
                    include "notifica.mpago.php";
                }
            }
            break;
        case "efipixmpay":
            $enviodocron = false;
            if ($enviarlembretes) {
                $efipaypix = WHMCS\Database\Capsule::table("mod_efimpay")->where("fatura_id", "=", $invoice_MPAY->id)->where("status", "=", "ATIVA")->first();
                if ($efipaypix) {
                    include "notifica.efipay.php";
                }
            }
            break;
        default:
            if ($enviarlembretes) {
                $retorno = $core->NOTIFICAZAP_EnviarMensagem($phone, $corpo_mensagem);
            }
            if ($enviarfaturapaga) {
                $retorno = $core->NOTIFICAZAP_EnviarMensagem($phone, $corpo_mensagem);
            }
            if (isset($retorno["status"]) && $retorno["status"] == "success") {
                $core->NOTIFICAZAP_CadastrarLogWhatsapp($client->id, $corpo_mensagem, "sendmessage", $template, $invoice_MPAY->id);
                echo json_encode(["status" => "success", "message" => "A notificação foi enviada com sucesso!"]);
            } else {
                $core->NOTIFICAZAP_LogWarning("Mensagem de WhatsApp não foi enviada", ["phone" => $phone, "cliente ID" => $client->id, "hook" => "enviar.notificacao", "dados do retorno" => json_encode($retorno)]);
                $erroMensagem = $retorno["error"] ?? "Erro desconhecido ao enviar a notificação.";
                echo json_encode(["status" => "error", "message" => "Erro ao enviar a notificação para o cliente! " . $erroMensagem]);
            }
            exit;
    }
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Erro ao processar a solicitação: " . $e->getMessage()]);
    exit;
}

?>