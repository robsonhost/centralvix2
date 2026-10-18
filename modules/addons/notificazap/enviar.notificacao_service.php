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
if (!isset($_POST["template"]) || !isset($_POST["idservice"])) {
    echo json_encode(["status" => "error", "message" => "Dados inválidos."]);
    exit;
}
$template = filter_var($_POST["template"], FILTER_SANITIZE_STRING);
$idservice = filter_var($_POST["idservice"], FILTER_SANITIZE_NUMBER_INT);
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
$service = WHMCS\Database\Capsule::table("tblhosting")->find($idservice);
$product = WHMCS\Database\Capsule::table("tblproducts")->find($service->packageid);
$client = WHMCS\User\Client::find($service->userid);
$id_campo_receber_notificacao = $core->NOTIFICAZAP_Pega_Info_tbladdonmodules("addon_notificazap_receber_whatsapp");
$receber_notificacao = $core->NOTIFICAZAP_Pega_Info_tblcustomfieldsvalues($id_campo_receber_notificacao, $service->userid);
if ($receber_notificacao == "Não") {
    echo json_encode(["status" => "error", "message" => "O Cliente não deseja receber Notificação por WhatsApp."]);
    exit;
}
if ($receber_notificacao != "Sim") {
    echo json_encode(["status" => "error", "message" => "Não há uma definição registrada no banco de dados informando se o cliente deseja ou não receber notificações. Acesse o perfil do cliente e clique em “Atualizar” para que essa informação seja salva corretamente."]);
    exit;
}
$linkautologin = $DOMINIO_WHMCS;
if (WHMCS\Database\Capsule::table("tbladdonmodules")->where("module", "autologinmpay")->exists()) {
    $linkautologin = $core->NOTIFICAZAP_generateAutologinLink($client->id, $service->userid, "painel");
}
if ($template == "serviceready") {
    $corpo_mensagem = str_replace(["%name%", "%product%", "%email%", "%duedate%", "%value%", "%ip%", "%domain%", "%username%", "%password%", "%assinatura%", "%ns1%", "%ns2%", "%dominiowhmcs%"], [$client->firstname, $product->name, $client->email, $core->NOTIFICAZAP_AdjustDate($service->nextduedate), $core->NOTIFICAZAP_AdjustValue($service->amount), $service->dedicatedip, $service->domain, $service->username, decrypt($service->password), $core->NOTIFICAZAP_Assinatura_WhatsApp(), $service->ns1, $service->ns2, $linkautologin], $corpo_mensagem);
} else {
    $corpo_mensagem = str_replace(["%name%", "%product%", "%assinatura%", "%dominiowhmcs%"], [$client->firstname, $product->name, $core->NOTIFICAZAP_Assinatura_WhatsApp(), $linkautologin], $corpo_mensagem);
}
try {
    $phone = $core->NOTIFICAZAP_AdjustNumber($client);
    $phone_whatsapp = $whatsapp->Valida_Number_WhatsApp($phone);
    $existsPhone = $phone_whatsapp[0]["exists"] ?? NULL;
    if (!$existsPhone) {
        $core->NOTIFICAZAP_LogWarning("Número de telefone não é WhatsApp válido", ["phone" => $phone, "action" => "service_notification_send"]);
        $core->NOTIFICAZAP_CadastrarLogFalhas($client->id, "Telefone " . $phone . " do Cliente não é um número de WhatsApp");
        return NULL;
    }
    $retorno = $core->NOTIFICAZAP_EnviarMensagem($phone, $corpo_mensagem);
    $erroMensagem = $retorno["error"] ?? "Erro desconhecido ao enviar a notificação.";
    if (isset($retorno["status"]) && $retorno["status"] == "success") {
        $core->NOTIFICAZAP_CadastrarLogWhatsapp($client->id, $corpo_mensagem, "sendmessage", $template);
        $core->NOTIFICAZAP_LogInfo("Notificação enviada com sucesso", ["phone" => $phone, "client_id" => $client->id, "action" => "service_notification_send"]);
        echo json_encode(["status" => "success", "message" => "A notificação foi enviada com sucesso!"]);
        exit;
    }
    $core->NOTIFICAZAP_LogWarning("Mensagem de WhatsApp não foi enviada", ["phone" => $phone, "cliente" => json_encode($client), "hook" => "enviar.notificacao_service", "dados do retorno" => json_encode($retorno)]);
    echo json_encode(["status" => "error", "message" => "Mensagem de WhatsApp não foi enviada para o número do Telefone [" . $phone . "] - " . $erroMensagem]);
    exit;
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Erro ao processar a solicitação: " . $e->getMessage()]);
    exit;
}

?>