<?php
/*
 * @ https://EasyToYou.eu - IonCube v14 Decoder Online
 * @ PHP 7.4
 * @ Decoder version: 1.0.2
 * @ Release: 10/08/2022
 */

// Decoded file for php version 74.
include_once __DIR__ . "/../../../../modules/addons/notificazap/config.php";
class notificazapCore
{
    public $templates = [];
    public function __construct()
    {
    }
    public function NOTIFICAZAP_Servidor_WhatsApp_Online($URL)
    {
        return $this->NOTIFICAZAP_EstaOnline($URL);
    }
    public function NOTIFICAZAP_ModulesPay_Online($URL)
    {
        return $this->NOTIFICAZAP_EstaOnline($URL);
    }
    public function NOTIFICAZAP_AdjustValue($value)
    {
        return number_format((float) $value, 2, ".", "");
    }
    public function NOTIFICAZAP_AdjustDate($date)
    {
        return date("d/m/Y", strtotime($date));
    }
    public function NOTIFICAZAP_AdjustHour($hour)
    {
        return date("H:i", strtotime($hour));
    }
    public function NOTIFICAZAP_converterDataHoraParaBrasileira($datetime)
    {
        return date("d/m/Y", strtotime($datetime));
    }
    public function NOTIFICAZAP_Chave_Secreta()
    {
        foreach (WHMCS\Database\Capsule::table("tbladdonmodules")->WHERE([["module", "notificazap"], ["setting", "addon_notificazap_chave_secreta"]])->get() as $CAMPOnotificazap) {
            $CAMPO = $CAMPOnotificazap->value;
        }
        return $CAMPO;
    }
    public function NOTIFICAZAP_Licenca()
    {
        foreach (WHMCS\Database\Capsule::table("tbladdonmodules")->WHERE([["module", "notificazap"], ["setting", "addon_notificazap_licenca"]])->get() as $CAMPOnotificazap) {
            $CAMPO2 = $CAMPOnotificazap->value;
        }
        return $CAMPO2;
    }
    public function NOTIFICAZAP_Valida_Numero()
    {
        foreach (WHMCS\Database\Capsule::table("tbladdonmodules")->WHERE([["module", "notificazap"], ["setting", "addon_notificazap_valida_numero"]])->get() as $CAMPOnotificazap) {
            $CAMPO3 = $CAMPOnotificazap->value;
        }
        return $CAMPO3;
    }
    public function NOTIFICAZAP_Origem_WhatsApp()
    {
        foreach (WHMCS\Database\Capsule::table("tbladdonmodules")->WHERE([["module", "notificazap"], ["setting", "addon_notificazap_origem_whatsapp"]])->get() as $CAMPOnotificazap) {
            $CAMPO4 = $CAMPOnotificazap->value;
        }
        return $CAMPO4;
    }
    public function NOTIFICAZAP_Assinatura_WhatsApp()
    {
        foreach (WHMCS\Database\Capsule::table("tbladdonmodules")->WHERE([["module", "notificazap"], ["setting", "addon_notificazap_assinatura_whatsapp"]])->get() as $CAMPOnotificazap) {
            $CAMPO5 = $CAMPOnotificazap->value;
        }
        return $CAMPO5;
    }
    public function NOTIFICAZAP_Telefone_Origem()
    {
        foreach (WHMCS\Database\Capsule::table("tbladdonmodules")->WHERE([["module", "notificazap"], ["setting", "addon_notificazap_telefone"]])->get() as $CAMPOnotificazap) {
            $CAMPO6 = $CAMPOnotificazap->value;
        }
        return $CAMPO6;
    }
    public function NOTIFICAZAP_Token_Geral()
    {
        return NZTOKENGERAL;
    }
    public function NOTIFICAZAP_Server()
    {
        return NZURLSERVIDORWHATSAPP;
    }
    public function NOTIFICAZAP_KEY()
    {
        foreach (WHMCS\Database\Capsule::table("tbladdonmodules")->WHERE([["module", "notificazap"], ["setting", "addon_notificazap_key"]])->get() as $CAMPOnotificazap) {
            $CAMPO7 = $CAMPOnotificazap->value;
        }
        return $CAMPO7;
    }
    public function NOTIFICAZAP_EstaOnline($url)
    {
        if (empty($url)) {
            return false;
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return 200 <= $httpCode && $httpCode < 400;
    }
    public function NOTIFICAZAP_Descriptografar($data)
    {
        $chave = "modulespay@whatsapp#cripto";
        $encryption_key = base64_decode($chave);
        list($encrypted_data, $iva) = explode("::", base64_decode($data), 2);
        return openssl_decrypt($encrypted_data, "aes-256-cbc", $encryption_key, 0, $iva);
    }
    public function NOTIFICAZAP_AdjustNumberUnico($phonenumber)
    {
        $number = str_replace(["(", ")", " ", "-", ".", "+"], "", $phonenumber);
        $numberNOTIFICAZAP = trim($number);
        return $numberNOTIFICAZAP;
    }
    public function NOTIFICAZAP_AdjustNumber($client, $useddi = true)
    {
        $ORIGEMWHATSAPPCAMPO = $this->NOTIFICAZAP_Origem_WhatsApp();
        if ($ORIGEMWHATSAPPCAMPO != "Campo padrão WHMCS") {
            $field = WHMCS\Database\Capsule::table("tblcustomfieldsvalues")->where("fieldid", $ORIGEMWHATSAPPCAMPO)->where("relid", $client->id)->first();
            $number = str_replace(["(", ")", " ", "-", ".", "+"], "", $field->value);
        } else {
            $number = str_replace(["(", ")", " ", "-", ".", "+"], "", $client->phonenumber);
        }
        $numberNOTIFICAZAP = trim($number);
        return $numberNOTIFICAZAP;
    }
    public function SaveContact($client)
    {
        $phone = explode(".", $client->phonenumber);
        $phone[1] = str_replace(["-", " "], "", $phone[1]);
        $this->method = "addcontact";
        $this->phone = $this->NOTIFICAZAP_AdjustNumber($client, false);
        $this->name = $client->firstname;
        $return = $this->SalvarContato();
        if (isset($return)) {
            if ($return === "success") {
                WHMCS\Database\Capsule::table("mod_notificazap_logs")->insert(["message" => "Novo contato adicionado na agenda virtual do Zap Mpay: " . $client->firstname . " " . $client->lastname . " (Id: " . $client->id . ")", "clientid" => $client->id, "type" => "addcontact", "created_at" => date("Y-m-d H:i:s"), "updated_at" => date("Y-m-d H:i:s")]);
            } else {
                $this->NOTIFICAZAP_LogError("Erro no processo de exportação do cliente para a Zap NOTIFICAZAP", ["client_name" => $client->firstname . " " . $client->lastname, "action" => "add_contact"], $client->id);
            }
        }
    }
    public function NOTIFICAZAP_EnviarMensagem($phone, $mensagem)
    {
        include_once "notificaWhatsApp.php";
        $whatsapp = new notificaWhatsAppMpay($this->NOTIFICAZAP_Server(), $this->NOTIFICAZAP_Descriptografar($this->NOTIFICAZAP_KEY()), $this->NOTIFICAZAP_Token_Geral(), $this->NOTIFICAZAP_Telefone_Origem());
        $envios = $whatsapp->sendText($phone, $mensagem);
        if (isset($envios["status"]) && $envios["status"] == "PENDING") {
            $resultado["status"] = "success";
            $resultado["error"] = "";
            $this->NOTIFICAZAP_LogInfo("Mensagem WhatsApp enviada com sucesso", ["phone" => $phone, "status" => $envios["status"], "action" => "send_text"]);
        } else {
            $resultado["status"] = "erro";
            $resultado["error"] = $envios["error"];
            $this->NOTIFICAZAP_LogError("Erro ao enviar notificação WhatsApp", ["phone" => $phone, "error" => $envios["error"], "action" => "send_text"]);
        }
        return $resultado;
    }
    public function NOTIFICAZAP_Envio_Geral_Mensagem($phone, $mensagem)
    {
        require_once "notificaWhatsApp.php";
        $whatsapp = new notificaWhatsAppMpay($this->NOTIFICAZAP_Server(), $this->NOTIFICAZAP_Descriptografar($this->NOTIFICAZAP_KEY()), $this->NOTIFICAZAP_Token_Geral(), $this->NOTIFICAZAP_Telefone_Origem());
        $envios = $whatsapp->sendText($phone, $mensagem);
        if (isset($envios["status"]) && $envios["status"] == "PENDING") {
            $resultado["status"] = "success";
            $resultado["error"] = "";
            $this->NOTIFICAZAP_LogInfo("Mensagem geral WhatsApp enviada com sucesso", ["phone" => $phone, "status" => $envios["status"], "action" => "send_general_message"]);
        } else {
            $resultado["status"] = "erro";
            $resultado["error"] = $envios["error"];
            $this->NOTIFICAZAP_LogError("Erro ao enviar mensagem geral WhatsApp", ["phone" => $phone, "error" => $envios["error"], "action" => "send_general_message"]);
        }
        return $resultado;
    }
    public function NOTIFICAZAP_Envio_Imagem_Mensagem($phone, $url, $mensagem)
    {
        require_once "notificaWhatsApp.php";
        $whatsapp = new notificaWhatsAppMpay($this->NOTIFICAZAP_Server(), $this->NOTIFICAZAP_Descriptografar($this->NOTIFICAZAP_KEY()), $this->NOTIFICAZAP_Token_Geral(), $this->NOTIFICAZAP_Telefone_Origem());
        $envios = $whatsapp->sendImagemUrl($phone, $url, $mensagem);
        if (isset($envios["status"]) && $envios["status"] == "PENDING") {
            $resultado["status"] = "success";
            $resultado["error"] = "";
            $this->NOTIFICAZAP_LogInfo("Imagem WhatsApp enviada com sucesso", ["phone" => $phone, "url" => $url, "status" => $envios["status"], "action" => "send_image_message"]);
        } else {
            $resultado["status"] = "erro";
            $resultado["error"] = $envios["error"];
            $this->NOTIFICAZAP_LogError("Erro ao enviar imagem por WhatsApp", ["phone" => $phone, "url" => $url, "error" => $envios["error"], "action" => "send_image_message"]);
        }
        return $resultado;
    }
    public function EnviarMensagemComBoleto_NOTIFICAZAP($phone, $urldoboleto, $invoice, $mensagem)
    {
        require_once "notificaWhatsApp.php";
        $whatsapp = new notificaWhatsAppMpay($this->NOTIFICAZAP_Server(), $this->NOTIFICAZAP_Descriptografar($this->NOTIFICAZAP_KEY()), $this->NOTIFICAZAP_Token_Geral(), $this->NOTIFICAZAP_Telefone_Origem());
        $envios = $whatsapp->sendPDFDocumentBase64($phone, $invoice, $urldoboleto, $mensagem);
        if (isset($envios["status"]) && $envios["status"] == "PENDING") {
            $resultado["status"] = "success";
            $resultado["error"] = "";
        } else {
            $resultado["status"] = "erro";
            $resultado["error"] = $envios["error"];
            $this->NOTIFICAZAP_LogError("Erro ao enviar boleto por WhatsApp", ["phone" => $phone, "invoice" => $invoice, "error" => $envios["error"], "action" => "send_boleto"]);
        }
        return $resultado;
    }
    public function EnviarBoleto_NOTIFICAZAP($phone, $urldoboleto, $invoice)
    {
        require_once "notificaWhatsApp.php";
        $whatsapp = new notificaWhatsAppMpay($this->NOTIFICAZAP_Server(), $this->NOTIFICAZAP_Descriptografar($this->NOTIFICAZAP_KEY()), $this->NOTIFICAZAP_Token_Geral(), $this->NOTIFICAZAP_Telefone_Origem());
        $envios = $whatsapp->sendPDFBase64($phone, $invoice, $urldoboleto);
        if (isset($envios["status"]) && $envios["status"] == "PENDING") {
            $resultado["status"] = "success";
            $resultado["error"] = "";
        } else {
            $resultado["status"] = "erro";
            $resultado["error"] = $envios["error"];
            $this->NOTIFICAZAP_LogError("Erro ao enviar notificação com boleto", ["phone" => $phone, "invoice" => $invoice, "error" => $envios["error"], "action" => "send_invoice"]);
        }
        return $resultado;
    }
    public function EnviarQRCode_NOTIFICAZAP($phone, $qrcode, $invoice)
    {
        require_once "notificaWhatsApp.php";
        $whatsapp = new notificaWhatsAppMpay($this->NOTIFICAZAP_Server(), $this->NOTIFICAZAP_Descriptografar($this->NOTIFICAZAP_KEY()), $this->NOTIFICAZAP_Token_Geral(), $this->NOTIFICAZAP_Telefone_Origem());
        $envios = $whatsapp->sendQRCode($phone, $qrcode, "Fatura Nº " . $invoice);
        if (isset($envios["status"]) && $envios["status"] == "PENDING") {
            $resultado["status"] = "success";
            $resultado["error"] = "";
        } else {
            $resultado["status"] = "erro";
            $resultado["error"] = $envios["error"];
            $this->NOTIFICAZAP_LogError("Erro ao enviar notificação WhatsApp", ["phone" => $phone, "error" => $envios["error"], "action" => "send_text"]);
        }
        return $resultado;
    }
    public function NOTIFICAZAP_CadastrarLogWhatsapp($clientid, $msg, $type, $tipomsg = " ", $invoiceid = " ")
    {
        WHMCS\Database\Capsule::table("mod_notificazap_logs")->insert(["message" => $msg, "created_at" => date("Y-m-d H:i:s"), "updated_at" => date("Y-m-d H:i:s"), "type" => $type, "invoiceid" => $invoiceid, "tipo_mensagem" => $tipomsg, "clientid" => $clientid]);
    }
    public function NOTIFICAZAP_CadastrarLogFalhas($clientID, $msg, $invoiceid = 0)
    {
        WHMCS\Database\Capsule::table("mod_notificazap_logs_falhas")->insert(["invoiceid" => $invoiceid, "clientid" => $clientID, "message" => $msg, "created_at" => date("Y-m-d H:i:s"), "updated_at" => date("Y-m-d H:i:s")]);
    }
    public function NOTIFICAZAP_CadastrarLogEnvios($client, $msg)
    {
        $Log = WHMCS\Database\Capsule::table("mod_notificazap_envios")->insert(["message" => $msg, "created_at" => date("Y-m-d H:i:s"), "clientid" => $client->id]);
    }
    public function NOTIFICAZAP_Titulo_Template($type)
    {
        if ($type == "createdinvoice") {
            $nomedotemplate = "Fatura Criada";
        }
        if ($type == "invoicereminder") {
            $nomedotemplate = "Lembrete de Vencimento";
        }
        if ($type == "invoicepaid") {
            $nomedotemplate = "Confirmação de Pagamento";
        }
        if ($type == "ticketcreated") {
            $nomedotemplate = "Abertura de Ticket";
        }
        if ($type == "ticketreplied") {
            $nomedotemplate = "Resposta do Ticket";
        }
        if ($type == "servicecreated") {
            $nomedotemplate = "Ativação de Serviço";
        }
        if ($type == "servicesuspended") {
            $nomedotemplate = "Suspensão de Serviço";
        }
        if ($type == "servicereactived") {
            $nomedotemplate = "Reativação do Serviço";
        }
        if ($type == "servicecancelled") {
            $nomedotemplate = "Cancelamento de Serviço";
        }
        if ($type == "welcome") {
            $nomedotemplate = "Cadastro Novo Cliente";
        }
        if ($type == "clientlogin") {
            $nomedotemplate = "Alerta Login";
        }
        if ($type == "failedaccess") {
            $nomedotemplate = "Falha de Login";
        }
        if ($type == "invoicecancelled") {
            $nomedotemplate = "Cancelamento de Fatura";
        }
        if ($type == "clientchangepwd") {
            $nomedotemplate = "Troca de Senha do Cliente";
        }
        if ($type == "serviceready") {
            $nomedotemplate = "Serviço Pronto";
        }
        if ($type == "servicealerta") {
            $nomedotemplate = "Lembrete Fatura Vencida";
        }
        if ($type == "alteracaosenha") {
            $nomedotemplate = "Alteração de Senha";
        }
        if ($type == "solicitacancelamento") {
            $nomedotemplate = "Solicitação Cancelamento";
        }
        if ($type == "alterarstatuscotacao") {
            $nomedotemplate = "Alteração Status Cotação";
        }
        if ($type == "cotacaoaceita") {
            $nomedotemplate = "Cotação Aceita";
        }
        if ($type == "enviarcotacao") {
            $nomedotemplate = "Enviar Cotação";
        }
        if ($type == "ticketclose") {
            $nomedotemplate = "Fechar Ticket";
        }
        if ($type == "happybirthday") {
            $nomedotemplate = "Feliz Aniversário";
        }
        if ($type == "notafiscalasaas") {
            $nomedotemplate = "Nota Fiscal Asaas";
        }
        if ($type == "faturavencendohoje") {
            $nomedotemplate = "Fatura Vencendo Hoje";
        }
        if ($type == "servicealertsuspended") {
            $nomedotemplate = "Alerta de Suspensão de Serviço";
        }
        if ($type == "servicealertcancelled") {
            $nomedotemplate = "Alerta de Cancelamento de Serviço";
        }
        return $nomedotemplate;
    }
    public function NOTIFICAZAP_encurta_link($url = NULL)
    {
        if ($url) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_URL, $url);
            $result = curl_exec($ch);
            curl_close($ch);
            $json = json_decode($result, true);
            return $json;
        }
    }
    public function NOTIFICAZAP_deletar_arquivo_da_licenca($caminhoDiretorio)
    {
        $deletado = "nao";
        if (!empty($caminhoDiretorio)) {
            $file = $caminhoDiretorio;
            if (file_exists($caminhoDiretorio) && unlink($file)) {
                $deletado = "sim";
            }
        }
        return $deletado;
    }
    public function NOTIFICAZAP_getProductNameByInvoiceId($invoiceId)
    {
        $invoiceItem = WHMCS\Database\Capsule::table("tblinvoiceitems")->where("invoiceid", $invoiceId)->whereIn("type", ["Hosting", "Domain"])->first();
        if (!$invoiceItem) {
            $invoiceItens = WHMCS\Database\Capsule::table("tblinvoiceitems")->where("invoiceid", $invoiceId)->first();
            if ($invoiceItens) {
                return $invoiceItens->description;
            }
            return "naoexiste";
        }
        return $invoiceItem->description;
    }
    public function NOTIFICAZAP_php7php8($haystack, $needle)
    {
        if (0 <= version_compare(PHP_VERSION, "8.0.0")) {
            return str_contains($haystack, $needle);
        }
        return strpos($haystack, $needle) !== false;
    }
    public function NOTIFICAZAP_generateAutologinLink($userId, $identificador, $tipo)
    {
        global $core;
        $systemUrl = rtrim(App::getSystemUrl(), "/");
        $autologinExistente = WHMCS\Database\Capsule::table("mod_autologinmpay")->where("user_id", $userId)->where("identificador", $identificador)->where("tipo", $tipo)->first();
        if ($autologinExistente) {
            return $systemUrl . "/autologin.php?short=" . $autologinExistente->short_code;
        }
        $shortUrl = $this->NOTIFICAZAP_generate_short_link($systemUrl . $userId . time() . $tipo . "autologin");
        WHMCS\Database\Capsule::table("mod_autologinmpay")->insert(["user_id" => $userId, "identificador" => $identificador, "tipo" => $tipo, "short_code" => $shortUrl, "data" => date("Y-m-d H:i:s")]);
        return $systemUrl . "/autologin.php?short=" . $shortUrl;
    }
    public function NOTIFICAZAP_generate_short_link($long_url)
    {
        $short_code = substr(md5($long_url), 0, 13);
        return $short_code;
    }
    public function NOTIFICAZAP_Pega_Versao($versionUrl)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $versionUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $version = curl_exec($ch);
        if (curl_errno($ch)) {
            $resultado = "error";
        } else {
            $resultado = $version;
        }
        curl_close($ch);
        return $resultado;
    }
    public function NOTIFICAZAP_Pega_Info_tbladdonmodules($setting)
    {
        $dados = WHMCS\Database\Capsule::table("tbladdonmodules")->where("module", "notificazap")->where("setting", $setting)->value("value");
        if ($dados) {
            $dadoencontrado = $dados;
        } else {
            $dadoencontrado = NULL;
        }
        return $dadoencontrado;
    }
    public function NOTIFICAZAP_Pega_Info_tblconfiguration($setting)
    {
        $dados = WHMCS\Database\Capsule::table("tblconfiguration")->where("setting", $setting)->value("value");
        if ($dados) {
            $dadoencontrado = $dados;
        } else {
            $dadoencontrado = NULL;
        }
        return $dadoencontrado;
    }
    public function NOTIFICAZAP_Pega_Info_tblcustomfieldsvalues($fieldid, $relid)
    {
        return WHMCS\Database\Capsule::table("tblcustomfieldsvalues")->where("fieldid", $fieldid)->where("relid", $relid)->value("value");
    }
    public function NOTIFICAZAP_Inserir_Campo_Personalizado_Receber_WhatsApp($numberCampo, $clientsId, $condicao = "Sim")
    {
        WHMCS\Database\Capsule::table("tblcustomfieldsvalues")->insert(["fieldid" => (int) $numberCampo, "relid" => (int) $clientsId, "value" => (string) $condicao, "created_at" => date("Y-m-d H:i:s"), "updated_at" => date("Y-m-d H:i:s")]);
    }
    public function NOTIFICAZAP_Pegar_Status_Mensagem_Template($type)
    {
        $template = WHMCS\Database\Capsule::table("mod_notificazap_templates")->where("type", $type)->first(["message", "status"]);
        if ($template) {
            $corpo_mensagem = $template->message;
            $status_mensagem = $template->status;
        } else {
            $corpo_mensagem = NULL;
            $status_mensagem = NULL;
        }
        return ["corpo_mensagem" => $corpo_mensagem, "status_mensagem" => $status_mensagem];
    }
    public function NOTIFICAZAP_CampoAddon($setting)
    {
        $campo = WHMCS\Database\Capsule::table("tbladdonmodules")->where("module", "notificazap")->where("setting", $setting)->value("value");
        return $campo;
    }
    public function NOTIFICAZAP_efipaygetAddonSetting($setting)
    {
        return WHMCS\Database\Capsule::table("tbladdonmodules")->where("module", "=", "notificazap")->where("setting", "=", $setting)->where("value", "=", "on")->count();
    }
    private function NOTIFICAZAP_DetectCurrentContext()
    {
        $context = ["client_id" => NULL, "user_id" => NULL];
        try {
            if (isset($_SESSION["uid"]) && 0 < $_SESSION["uid"]) {
                $context["user_id"] = (int) $_SESSION["uid"];
            }
            if (isset($_GET["userid"]) && 0 < $_GET["userid"]) {
                $context["client_id"] = (int) $_GET["userid"];
            } else if (isset($_POST["userid"]) && 0 < $_POST["userid"]) {
                $context["client_id"] = (int) $_POST["userid"];
            } else if (isset($_REQUEST["clientid"]) && 0 < $_REQUEST["clientid"]) {
                $context["client_id"] = (int) $_REQUEST["clientid"];
            } else if (isset($_SESSION["cid"]) && 0 < $_SESSION["cid"]) {
                $context["client_id"] = (int) $_SESSION["cid"];
            }
            if (empty($context["client_id"]) && !empty($context["user_id"])) {
                $client = WHMCS\Database\Capsule::table("tblusers")->where("id", $context["user_id"])->first();
                if ($client && !empty($client->client_id)) {
                    $context["client_id"] = (int) $client->client_id;
                }
            }
        } catch (Exception $e) {
            $context = ["client_id" => NULL, "user_id" => NULL];
        }
        return $context;
    }
    public function NOTIFICAZAP_Log($level = "INFO", $message = "", $context = NULL, $clientId = NULL, $userId = NULL)
    {
        try {
            $validLevels = ["DEBUG", "INFO", "WARNING", "ERROR", "CRITICAL"];
            if (!in_array(strtoupper($level), $validLevels)) {
                $level = "INFO";
            }
            if ($clientId === NULL && $userId === NULL) {
                $detectedContext = $this->NOTIFICAZAP_DetectCurrentContext();
                $clientId = $detectedContext["client_id"];
                $userId = $detectedContext["user_id"];
            }
            $data = ["level" => strtoupper($level), "message" => substr($message, 0, 5000), "context" => !empty($context) ? is_string($context) ? $context : json_encode($context) : NULL, "client_id" => !empty($clientId) ? $clientId : NULL, "user_id" => !empty($userId) ? $userId : NULL, "ip_address" => $this->NOTIFICAZAP_GetClientIP(), "user_agent" => !empty($_SERVER["HTTP_USER_AGENT"]) ? substr($_SERVER["HTTP_USER_AGENT"], 0, 500) : NULL, "created_at" => date("Y-m-d H:i:s"), "updated_at" => date("Y-m-d H:i:s")];
            WHMCS\Database\Capsule::table("mod_notificazap_registry")->insert($data);
            if (strtoupper($level) === "ERROR" || strtoupper($level) === "CRITICAL") {
                logActivity("[NOTIFICAZAP " . $level . "] " . $message);
            }
            return true;
        } catch (Exception $e) {
            if (function_exists("logActivity")) {
                logActivity("[NOTIFICAZAP LOG ERROR] " . $message . " | Log Error: " . $e->getMessage());
            }
            return false;
        }
    }
    private function NOTIFICAZAP_GetClientIP()
    {
        $ip = "";
        if (!empty($_SERVER["HTTP_CLIENT_IP"])) {
            $ip = $_SERVER["HTTP_CLIENT_IP"];
        } else if (!empty($_SERVER["HTTP_X_FORWARDED_FOR"])) {
            $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
        } else if (!empty($_SERVER["HTTP_X_FORWARDED"])) {
            $ip = $_SERVER["HTTP_X_FORWARDED"];
        } else if (!empty($_SERVER["HTTP_FORWARDED_FOR"])) {
            $ip = $_SERVER["HTTP_FORWARDED_FOR"];
        } else if (!empty($_SERVER["HTTP_FORWARDED"])) {
            $ip = $_SERVER["HTTP_FORWARDED"];
        } else if (!empty($_SERVER["REMOTE_ADDR"])) {
            $ip = $_SERVER["REMOTE_ADDR"];
        }
        return $ip;
    }
    public function NOTIFICAZAP_LogDebug($message, $context = NULL, $clientId = NULL, $userId = NULL)
    {
        return $this->NOTIFICAZAP_Log("DEBUG", $message, $context, $clientId, $userId);
    }
    public function NOTIFICAZAP_LogInfo($message, $context = NULL, $clientId = NULL, $userId = NULL)
    {
        return $this->NOTIFICAZAP_Log("INFO", $message, $context, $clientId, $userId);
    }
    public function NOTIFICAZAP_LogWarning($message, $context = NULL, $clientId = NULL, $userId = NULL)
    {
        return $this->NOTIFICAZAP_Log("WARNING", $message, $context, $clientId, $userId);
    }
    public function NOTIFICAZAP_LogError($message, $context = NULL, $clientId = NULL, $userId = NULL)
    {
        return $this->NOTIFICAZAP_Log("ERROR", $message, $context, $clientId, $userId);
    }
    public function NOTIFICAZAP_LogCritical($message, $context = NULL, $clientId = NULL, $userId = NULL)
    {
        return $this->NOTIFICAZAP_Log("CRITICAL", $message, $context, $clientId, $userId);
    }
    public function NOTIFICAZAP_GetLogs($filters = [], $limit = 50, $offset = 0)
    {
        try {
            $query = WHMCS\Database\Capsule::table("mod_notificazap_registry");
            if (!empty($filters["level"])) {
                $query->where("level", $filters["level"]);
            }
            if (!empty($filters["client_id"])) {
                $query->where("client_id", $filters["client_id"]);
            }
            if (!empty($filters["user_id"])) {
                $query->where("user_id", $filters["user_id"]);
            }
            if (!empty($filters["date_from"])) {
                $query->where("created_at", ">=", $filters["date_from"]);
            }
            if (!empty($filters["date_to"])) {
                $query->where("created_at", "<=", $filters["date_to"]);
            }
            if (!empty($filters["search"])) {
                $query->where("message", "LIKE", "%" . $filters["search"] . "%");
            }
            $query->orderBy("created_at", "DESC");
            if (0 < $limit) {
                $query->limit($limit)->offset($offset);
            }
            $logs = $query->get();
            $countQuery = WHMCS\Database\Capsule::table("mod_notificazap_registry");
            if (!empty($filters["level"])) {
                $countQuery->where("level", $filters["level"]);
            }
            if (!empty($filters["client_id"])) {
                $countQuery->where("client_id", $filters["client_id"]);
            }
            if (!empty($filters["user_id"])) {
                $countQuery->where("user_id", $filters["user_id"]);
            }
            if (!empty($filters["date_from"])) {
                $countQuery->where("created_at", ">=", $filters["date_from"]);
            }
            if (!empty($filters["date_to"])) {
                $countQuery->where("created_at", "<=", $filters["date_to"]);
            }
            if (!empty($filters["search"])) {
                $countQuery->where("message", "LIKE", "%" . $filters["search"] . "%");
            }
            $total = $countQuery->count();
            return ["logs" => $logs, "total" => $total, "limit" => $limit, "offset" => $offset];
        } catch (Exception $e) {
            $this->NOTIFICAZAP_LogError("Erro ao obter logs: " . $e->getMessage());
            return ["logs" => [], "total" => 0, "limit" => $limit, "offset" => $offset, "error" => $e->getMessage()];
        }
    }
    public function NOTIFICAZAP_GetLogsFalhas($filters = [], $limit = 50, $offset = 0)
    {
        try {
            $query = WHMCS\Database\Capsule::table("mod_notificazap_logs_falhas");
            if (!empty($filters["client_id"])) {
                $query->where("clientid", $filters["client_id"]);
            }
            if (!empty($filters["invoice_id"])) {
                $query->where("invoiceid", $filters["invoice_id"]);
            }
            if (!empty($filters["date_to"])) {
                $query->where("created_at", "<=", $filters["date_to"] . " 23:59:59");
            }
            $query->orderBy("created_at", "DESC");
            if (0 < $limit) {
                $query->limit($limit)->offset($offset);
            }
            $logs = $query->get();
            $countQuery = WHMCS\Database\Capsule::table("mod_notificazap_logs_falhas");
            if (!empty($filters["client_id"])) {
                $countQuery->where("clientid", $filters["client_id"]);
            }
            if (!empty($filters["invoice_id"])) {
                $countQuery->where("invoiceid", $filters["invoice_id"]);
            }
            if (!empty($filters["date_to"])) {
                $countQuery->where("created_at", "<=", $filters["date_to"] . " 23:59:59");
            }
            $total = $countQuery->count();
            return ["logs" => $logs, "total" => $total, "limit" => $limit, "offset" => $offset];
        } catch (Exception $e) {
            $this->NOTIFICAZAP_LogError("Erro ao obter logs de falhas: " . $e->getMessage());
            return ["logs" => [], "total" => 0, "limit" => $limit, "offset" => $offset, "error" => $e->getMessage()];
        }
    }
    public function NOTIFICAZAP_diasEntreDatas($data)
    {
        $dataInformada = new DateTime($data);
        $hoje = new DateTime();
        $diferenca = $dataInformada->diff($hoje);
        return $diferenca->days;
    }
}

?>