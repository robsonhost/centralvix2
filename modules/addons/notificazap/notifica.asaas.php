<?php
/*
 * @ https://EasyToYou.eu - IonCube v14 Decoder Online
 * @ PHP 7.4
 * @ Decoder version: 1.0.2
 * @ Release: 10/08/2022
 */

// Decoded file for php version 74.
$enviar_boleto = WHMCS\Database\Capsule::table("tbladdonmodules")->where("module", "=", "notificazap")->where("setting", "=", "enviar_boleto")->where("value", "=", "on")->count();
$enviar_codigo_barra = WHMCS\Database\Capsule::table("tbladdonmodules")->where("module", "=", "notificazap")->where("setting", "=", "enviar_codigo_barra")->where("value", "=", "on")->count();
$enviar_qrcode = WHMCS\Database\Capsule::table("tbladdonmodules")->where("module", "=", "notificazap")->where("setting", "=", "enviar_qrcode")->where("value", "=", "on")->count();
$enviar_copia_cola = WHMCS\Database\Capsule::table("tbladdonmodules")->where("module", "=", "notificazap")->where("setting", "=", "enviar_copia_cola")->where("value", "=", "on")->count();
$enviar_link_cartao = WHMCS\Database\Capsule::table("tbladdonmodules")->where("module", "=", "notificazap")->where("setting", "=", "enviar_link_cartao")->where("value", "=", "on")->count();
$enviar_chave_pix = WHMCS\Database\Capsule::table("tbladdonmodules")->where("module", "=", "notificazap")->where("setting", "=", "enviar_chave_pix")->where("value", "=", "on")->count();
$method = $invoice_MPAY->paymentmethod;
$retorno = NULL;
if (in_array($method, ["asaasboletompay", "asaaspixmpay", "asaascartaompay", "cobrancaasaasmpay"])) {
    $retorno = $core->NOTIFICAZAP_EnviarMensagem($phone, $corpo_mensagem);
    if ($retorno["status"] === "success") {
        if (isset($vencendoHoje) && $vencendoHoje) {
            WHMCS\Database\Capsule::table("mod_cobrancaasaasmpay")->where("fatura_id", $invoice_MPAY->id)->where("user_id", "=", $invoice_MPAY->userid)->update(["hojeenviado" => "S", "notenviado" => "S"]);
        } else {
            WHMCS\Database\Capsule::table("mod_cobrancaasaasmpay")->where("fatura_id", $invoice_MPAY->id)->where("user_id", "=", $invoice_MPAY->userid)->update(["notenviado" => "S"]);
        }
        if ($method == "asaasboletompay") {
            if (0 < $enviar_boleto && $core->NOTIFICAZAP_php7php8($cobrancaasaas->url_boleto, "https://www.asaas.com/b/pdf")) {
                $core->EnviarBoleto_NOTIFICAZAP($phone, $cobrancaasaas->url_boleto, $invoice_MPAY->id);
            }
            if (0 < $enviar_codigo_barra && !empty($cobrancaasaas->codigo_barra) && trim($cobrancaasaas->codigo_barra) !== " ") {
                $titulo_codigo_barra = $core->NOTIFICAZAP_CampoAddon("enviar_codigodebarra_titulo");
                if (!empty($titulo_codigo_barra)) {
                    $core->NOTIFICAZAP_EnviarMensagem($phone, "*" . $titulo_codigo_barra . "*");
                }
                $core->NOTIFICAZAP_EnviarMensagem($phone, $cobrancaasaas->codigo_barra);
            }
        }
        if ($method == "asaaspixmpay") {
            $chavedopix = $core->NOTIFICAZAP_CampoAddon("enviar_chave_dopix");
            if (0 < $enviar_qrcode && !empty($cobrancaasaas->imagemqrcode) && trim($cobrancaasaas->imagemqrcode) !== " ") {
                $titulo_qrcode = $core->NOTIFICAZAP_CampoAddon("enviar_qrcode_titulo");
                if (!empty($titulo_qrcode)) {
                    $core->NOTIFICAZAP_EnviarMensagem($phone, "*" . $titulo_qrcode . "*");
                }
                $core->EnviarQRCode_NOTIFICAZAP($phone, $cobrancaasaas->imagemqrcode, $invoice_MPAY->id);
            }
            if (0 < $enviar_copia_cola && !empty($cobrancaasaas->qrcode) && trim($cobrancaasaas->qrcode) !== " ") {
                $titulo_copia_cola = $core->NOTIFICAZAP_CampoAddon("enviar_copiaecola_titulo");
                if (!empty($titulo_copia_cola)) {
                    $core->NOTIFICAZAP_EnviarMensagem($phone, "*" . $titulo_copia_cola . "*");
                }
                $core->NOTIFICAZAP_EnviarMensagem($phone, $cobrancaasaas->qrcode);
            }
            if (0 < $enviar_chave_pix && !empty($chavedopix) && trim($chavedopix) !== " ") {
                $titulo_chave_pix = $core->NOTIFICAZAP_CampoAddon("enviar_chavepix_titulo");
                if (!empty($titulo_chave_pix)) {
                    $core->NOTIFICAZAP_EnviarMensagem($phone, "*" . $titulo_chave_pix . "*");
                }
                $core->NOTIFICAZAP_EnviarMensagem($phone, $chavedopix);
            }
        }
        if ($method == "asaascartaompay" && 0 < $enviar_link_cartao) {
            $titulo_link_cartao = $core->NOTIFICAZAP_CampoAddon("enviar_linkcartao_titulo");
            $linkcartao = $cobrancaasaas->url_boleto;
            if (!empty($titulo_link_cartao) && !empty($linkcartao)) {
                $core->NOTIFICAZAP_EnviarMensagem($phone, "*" . $titulo_link_cartao . "*");
                $core->NOTIFICAZAP_EnviarMensagem($phone, $linkcartao);
            }
        }
        if ($method == "cobrancaasaasmpay") {
            $chavedopix = $core->NOTIFICAZAP_CampoAddon("enviar_chave_dopix");
            if (0 < $enviar_boleto && $core->NOTIFICAZAP_php7php8($cobrancaasaas->url_boleto, "https://www.asaas.com/b/pdf")) {
                $core->EnviarBoleto_NOTIFICAZAP($phone, $cobrancaasaas->url_boleto, $invoice_MPAY->id);
            }
            if (0 < $enviar_codigo_barra && !empty($cobrancaasaas->codigo_barra) && trim($cobrancaasaas->codigo_barra) !== " ") {
                $titulo_codigo_barra = $core->NOTIFICAZAP_CampoAddon("enviar_codigodebarra_titulo");
                if (!empty($titulo_codigo_barra)) {
                    $core->NOTIFICAZAP_EnviarMensagem($phone, "*" . $titulo_codigo_barra . "*");
                }
                $core->NOTIFICAZAP_EnviarMensagem($phone, $cobrancaasaas->codigo_barra);
            }
            if (0 < $enviar_qrcode && !empty($cobrancaasaas->imagemqrcode) && trim($cobrancaasaas->imagemqrcode) !== " ") {
                $titulo_qrcode = $core->NOTIFICAZAP_CampoAddon("enviar_qrcode_titulo");
                if (!empty($titulo_qrcode)) {
                    $core->NOTIFICAZAP_EnviarMensagem($phone, "*" . $titulo_qrcode . "*");
                }
                $core->EnviarQRCode_NOTIFICAZAP($phone, $cobrancaasaas->imagemqrcode, $invoice_MPAY->id);
            }
            if (0 < $enviar_copia_cola && !empty($cobrancaasaas->qrcode) && trim($cobrancaasaas->qrcode) !== " ") {
                $titulo_copia_cola = $core->NOTIFICAZAP_CampoAddon("enviar_copiaecola_titulo");
                if (!empty($titulo_copia_cola)) {
                    $core->NOTIFICAZAP_EnviarMensagem($phone, "*" . $titulo_copia_cola . "*");
                }
                $core->NOTIFICAZAP_EnviarMensagem($phone, $cobrancaasaas->qrcode);
            }
            if (0 < $enviar_chave_pix && !empty($chavedopix) && trim($chavedopix) !== "") {
                $titulo_chave_pix = $core->NOTIFICAZAP_CampoAddon("enviar_chavepix_titulo");
                if (!empty($titulo_chave_pix)) {
                    $core->NOTIFICAZAP_EnviarMensagem($phone, "*" . $titulo_chave_pix . "*");
                }
                $core->NOTIFICAZAP_EnviarMensagem($phone, $chavedopix);
            }
            if (0 < $enviar_link_cartao) {
                $titulo_link_cartao = $core->NOTIFICAZAP_CampoAddon("enviar_linkcartao_titulo");
                $linkcartao = $cobrancaasaas->url_boleto;
                if ($core->NOTIFICAZAP_php7php8($linkcartao, "https://www.asaas.com/b/pdf")) {
                    $linkcartao = str_replace("b/pdf", "i", $linkcartao);
                }
                if (!empty($titulo_link_cartao) && !empty($linkcartao) && trim($linkcartao) !== " ") {
                    $core->NOTIFICAZAP_EnviarMensagem($phone, "*" . $titulo_link_cartao . "*");
                    $core->NOTIFICAZAP_EnviarMensagem($phone, $linkcartao);
                }
            }
        }
    }
}

?>