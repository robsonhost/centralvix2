<?php
/*
 * @ https://EasyToYou.eu - IonCube v14 Decoder Online
 * @ PHP 7.4
 * @ Decoder version: 1.0.2
 * @ Release: 10/08/2022
 */

// Decoded file for php version 74.
$enviar_QRCODE = $core->NOTIFICAZAP_efipaygetAddonSetting("enviar_efipaypix");
$enviar_COPIAECOLA = $core->NOTIFICAZAP_efipaygetAddonSetting("enviar_efipaypix_copiaecola");
$retorno = $core->NOTIFICAZAP_EnviarMensagem($phone, $corpo_mensagem);
if ($retorno["status"] === "success") {
    if (isset($vencendoHoje) && $vencendoHoje) {
        WHMCS\Database\Capsule::table("mod_efimpay")->where("id", $efipaypix->id)->update(["hojeenviado" => "S", "notenviado" => "S"]);
    } else {
        WHMCS\Database\Capsule::table("mod_efimpay")->where("id", $efipaypix->id)->update(["notenviado" => "S"]);
    }
    if (0 < $enviar_QRCODE && !empty($efipaypix->qrcode_image) && trim($efipaypix->qrcode_image) !== "") {
        $envio = $core->EnviarQRCode_NOTIFICAZAP($phone, str_replace("data:image/png;base64,", "", $efipaypix->qrcode_image), $invoice_MPAY->id);
    }
    if (0 < $enviar_COPIAECOLA && !empty($efipaypix->qrcode) && trim($efipaypix->qrcode) !== "") {
        $envio = $core->NOTIFICAZAP_EnviarMensagem($phone, $efipaypix->qrcode);
    }
}

?>