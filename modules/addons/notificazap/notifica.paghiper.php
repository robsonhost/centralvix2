<?php
/*
 * @ https://EasyToYou.eu - IonCube v14 Decoder Online
 * @ PHP 7.4
 * @ Decoder version: 1.0.2
 * @ Release: 10/08/2022
 */

// Decoded file for php version 74.
$enviar_paghiper = WHMCS\Database\Capsule::table("tbladdonmodules")->where("module", "notificazap")->where("setting", "enviar_cobranca_paghiper")->where("value", "on")->exists();
$retorno = NULL;
if ($enviar_paghiper) {
    $retorno = $core->NOTIFICAZAP_EnviarMensagem($phone, $corpo_mensagem);
    if (isset($vencendoHoje) && $vencendoHoje) {
        WHMCS\Database\Capsule::table("mod_paghiper")->where("order_id", $invoice_MPAY->id)->update(["hojeenviado" => "S", "notenviado" => "S"]);
    } else {
        WHMCS\Database\Capsule::table("mod_paghiper")->where("order_id", $invoice_MPAY->id)->update(["notenviado" => "S"]);
    }
    $urldoboletopaghiper = WHMCS\Database\Capsule::table("mod_paghiper")->where("order_id", $invoice_MPAY->id)->where("transaction_type", "billet")->first();
    if ($urldoboletopaghiper) {
        $core->EnviarBoleto_NOTIFICAZAP($phone, $urldoboletopaghiper->url_slip_pdf, $invoice_MPAY->id);
    }
    $urldopixpaghiper = WHMCS\Database\Capsule::table("mod_paghiper")->where("order_id", $invoice_MPAY->id)->where("transaction_type", "pix")->first();
    if ($urldopixpaghiper) {
        $core->EnviarQRCode_NOTIFICAZAP($phone, $urldopixpaghiper->qrcode_image_url, $invoice_MPAY->id);
        $core->NOTIFICAZAP_EnviarMensagem($phone, $urldopixpaghiper->emv);
    }
}

?>