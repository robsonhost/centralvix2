<?php
/*
 * @ https://EasyToYou.eu - IonCube v14 Decoder Online
 * @ PHP 7.4
 * @ Decoder version: 1.0.2
 * @ Release: 10/08/2022
 */

// Decoded file for php version 74.
$enviar_mercadopix = WHMCS\Database\Capsule::table("tbladdonmodules")->where("module", "notificazap")->where("setting", "enviar_mercadopagopix")->where("value", "on")->exists();
$retorno = NULL;
if ($enviar_mercadopix) {
    $retorno = $core->NOTIFICAZAP_EnviarMensagem($phone, $corpo_mensagem);
    if ($retorno["status"] === "success") {
        if (isset($vencendoHoje) && $vencendoHoje) {
            WHMCS\Database\Capsule::table("mod_mpagopixmpay")->where("fatura_id", $invoice_MPAY->id)->update(["hojeenviado" => "S", "notenviado" => "S"]);
        } else {
            WHMCS\Database\Capsule::table("mod_mpagopixmpay")->where("fatura_id", $invoice_MPAY->id)->update(["notenviado" => "S"]);
        }
        $core->EnviarQRCode_NOTIFICAZAP($phone, $mercadoPix->qrcode, $invoice_MPAY->id);
        $core->NOTIFICAZAP_EnviarMensagem($phone, $mercadoPix->copiaecola);
    }
}

?>