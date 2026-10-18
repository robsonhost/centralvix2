<?php
/*
 * @ https://EasyToYou.eu - IonCube v14 Decoder Online
 * @ PHP 7.4
 * @ Decoder version: 1.0.2
 * @ Release: 10/08/2022
 */

// Decoded file for php version 74.
class notificaWhatsAppMpay
{
    private $key;
    private $server;
    private $apikey;
    private $header = [];
    private $parth;
    private $method;
    private $body;
    private $numberfrom;
    public function __construct($server, $key, $apikey, $numberfrom)
    {
        $this->server = $server;
        if (isset($key)) {
            $this->key = $key;
        }
        if (isset($apikey)) {
            $this->apikey = $apikey;
        }
        if (isset($numberfrom)) {
            $this->numberfrom = $numberfrom;
        }
    }
    public function Valida_Number_WhatsApp($number)
    {
        $ch = curl_init();
        curl_setopt_array($ch, [CURLOPT_URL => $this->server . "/chat/whatsappNumbers/" . $this->key, CURLOPT_RETURNTRANSFER => true, CURLOPT_ENCODING => "", CURLOPT_MAXREDIRS => 10, CURLOPT_TIMEOUT => 0, CURLOPT_FOLLOWLOCATION => true, CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1, CURLOPT_CUSTOMREQUEST => "POST", CURLOPT_POSTFIELDS => "{\r\n              \"numbers\": [\r\n              \"" . $number . "\"\r\n              ]\r\n          }", CURLOPT_HTTPHEADER => ["Content-Type: application/json", "apikey: " . $this->apikey]]);
        $result = curl_exec($ch);
        curl_close($ch);
        if (curl_errno($ch)) {
            return curl_error($ch);
        }
        return json_decode($result, true);
    }
    public function getInformation()
    {
        $ch = curl_init();
        curl_setopt_array($ch, [CURLOPT_URL => $this->server, CURLOPT_RETURNTRANSFER => true, CURLOPT_ENCODING => "", CURLOPT_MAXREDIRS => 10, CURLOPT_TIMEOUT => 0, CURLOPT_FOLLOWLOCATION => true, CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1, CURLOPT_CUSTOMREQUEST => "GET", CURLOPT_HTTPHEADER => ["apikey: " . $this->apikey]]);
        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            return curl_error($ch);
        }
        curl_close($ch);
        return json_decode($result, true);
    }
    public function fetchInstances()
    {
        $ch = curl_init();
        curl_setopt_array($ch, [CURLOPT_URL => $this->server . "/instance/fetchInstances/?instanceName=" . $this->key, CURLOPT_RETURNTRANSFER => true, CURLOPT_ENCODING => "", CURLOPT_MAXREDIRS => 10, CURLOPT_TIMEOUT => 0, CURLOPT_FOLLOWLOCATION => true, CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1, CURLOPT_CUSTOMREQUEST => "GET", CURLOPT_HTTPHEADER => ["apikey: " . $this->apikey]]);
        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            return curl_error($ch);
        }
        curl_close($ch);
        return json_decode($result, true);
    }
    public function InstanceConnect()
    {
        $ch = curl_init();
        curl_setopt_array($ch, [CURLOPT_URL => $this->server . "/instance/connect/" . $this->key, CURLOPT_RETURNTRANSFER => true, CURLOPT_ENCODING => "", CURLOPT_MAXREDIRS => 10, CURLOPT_TIMEOUT => 0, CURLOPT_FOLLOWLOCATION => true, CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1, CURLOPT_CUSTOMREQUEST => "GET", CURLOPT_HTTPHEADER => ["apikey: " . $this->apikey]]);
        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            return curl_error($ch);
        }
        curl_close($ch);
        return json_decode($result, true);
    }
    public function connectionStatus()
    {
        $ch = curl_init();
        curl_setopt_array($ch, [CURLOPT_URL => $this->server . "/instance/connectionState/" . $this->key, CURLOPT_RETURNTRANSFER => true, CURLOPT_ENCODING => "", CURLOPT_MAXREDIRS => 10, CURLOPT_TIMEOUT => 0, CURLOPT_FOLLOWLOCATION => true, CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1, CURLOPT_CUSTOMREQUEST => "GET", CURLOPT_HTTPHEADER => ["apikey: " . $this->apikey]]);
        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            return curl_error($ch);
        }
        curl_close($ch);
        return json_decode($result, true);
    }
    public function getQrCodeBase64()
    {
        $ch = curl_init();
        curl_setopt_array($ch, [CURLOPT_URL => $this->server . "/instance/create", CURLOPT_RETURNTRANSFER => true, CURLOPT_ENCODING => "", CURLOPT_MAXREDIRS => 10, CURLOPT_TIMEOUT => 0, CURLOPT_FOLLOWLOCATION => true, CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1, CURLOPT_CUSTOMREQUEST => "POST", CURLOPT_POSTFIELDS => "\r\n            {\r\n                \"instanceName\": \"" . $this->key . "\",\r\n                \"qrcode\": true,\r\n                \"mobile\": false,\r\n                \"integration\": \"WHATSAPP-BAILEYS\"\r\n            }", CURLOPT_HTTPHEADER => ["Content-Type: application/json", "apikey: " . $this->apikey]]);
        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            return curl_error($ch);
        }
        curl_close($ch);
        return json_decode($result, true);
    }
    public function DeleteInstance()
    {
        $ch = curl_init();
        curl_setopt_array($ch, [CURLOPT_URL => $this->server . "/instance/delete/" . $this->key, CURLOPT_RETURNTRANSFER => true, CURLOPT_ENCODING => "", CURLOPT_MAXREDIRS => 10, CURLOPT_TIMEOUT => 0, CURLOPT_FOLLOWLOCATION => true, CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1, CURLOPT_CUSTOMREQUEST => "DELETE", CURLOPT_HTTPHEADER => ["apikey: " . $this->apikey]]);
        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            return curl_error($ch);
        }
        curl_close($ch);
        return json_decode($result, true);
    }
    public function RestartInstance()
    {
        $ch = curl_init();
        curl_setopt_array($ch, [CURLOPT_URL => $this->server . "/instance/restart/" . $this->key, CURLOPT_RETURNTRANSFER => true, CURLOPT_ENCODING => "", CURLOPT_MAXREDIRS => 10, CURLOPT_TIMEOUT => 0, CURLOPT_FOLLOWLOCATION => true, CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1, CURLOPT_CUSTOMREQUEST => "PUT", CURLOPT_HTTPHEADER => ["apikey: " . $this->apikey]]);
        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            return curl_error($ch);
        }
        curl_close($ch);
        return json_decode($result, true);
    }
    public function LogoutInstance()
    {
        $ch = curl_init();
        curl_setopt_array($ch, [CURLOPT_URL => $this->server . "/instance/logout/" . $this->key, CURLOPT_RETURNTRANSFER => true, CURLOPT_ENCODING => "", CURLOPT_MAXREDIRS => 10, CURLOPT_TIMEOUT => 0, CURLOPT_FOLLOWLOCATION => true, CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1, CURLOPT_CUSTOMREQUEST => "DELETE", CURLOPT_HTTPHEADER => ["apikey: " . $this->apikey]]);
        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            return curl_error($ch);
        }
        curl_close($ch);
        return json_decode($result, true);
    }
    public function sendTextButton($to, $texto, $links)
    {
        $texto = str_replace(["\r\n", "\r", "\n"], "\\n", $texto);
        $texto = preg_replace("/[\\x00-\\x1F\\x80-\\x9F]/u", "", $texto);
        $buttons = [];
        if (isset($links["linkboleto"])) {
            $buttons[] = ["type" => "url", "displayText" => $links["tituloboleto"], "url" => $links["linkboleto"]];
        }
        if (isset($links["linkqrcode"])) {
            $buttons[] = ["type" => "url", "displayText" => $links["linkqrcode"], "url" => $links["titulopix"]];
        }
        if (isset($links["chavepix"])) {
            $buttons[] = ["type" => "copy", "displayText" => $links["titulochavepix"], "copyCode" => $links["chavepix"]];
        }
        $data = ["number" => $to, "description" => $texto, "buttons" => $buttons];
        $jsonData = json_encode($data);
        $curl = curl_init();
        curl_setopt_array($curl, [CURLOPT_URL => $this->server . "/message/sendButtons/" . $this->key, CURLOPT_RETURNTRANSFER => true, CURLOPT_ENCODING => "", CURLOPT_MAXREDIRS => 10, CURLOPT_TIMEOUT => 0, CURLOPT_FOLLOWLOCATION => true, CURLOPT_CUSTOMREQUEST => "POST", CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1, CURLOPT_POSTFIELDS => $jsonData, CURLOPT_HTTPHEADER => ["Content-Type: application/json", "apikey: " . $this->apikey]]);
        $response = curl_exec($curl);
        curl_close($curl);
        return json_decode($response, true);
    }
    public function sendText($to, $texto)
    {
        $texto = str_replace(["\r\n", "\r", "\n"], "\\n", $texto);
        $texto = preg_replace("/[\\x00-\\x1F\\x80-\\x9F]/u", "", $texto);
        $curl = curl_init();
        curl_setopt_array($curl, [CURLOPT_URL => $this->server . "/message/sendText/" . $this->key, CURLOPT_RETURNTRANSFER => true, CURLOPT_ENCODING => "", CURLOPT_MAXREDIRS => 10, CURLOPT_TIMEOUT => 0, CURLOPT_FOLLOWLOCATION => true, CURLOPT_CUSTOMREQUEST => "POST", CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1, CURLOPT_POSTFIELDS => "{\r\n            \"number\": \"" . $to . "\",\r\n            \"text\": \"" . $texto . "\",\r\n            \"options\": {\r\n                \"delay\": 1200\r\n            },\r\n            \"linkPreview\": false,\r\n            \"mentionsEveryOne\": false\r\n            }", CURLOPT_HTTPHEADER => ["Content-Type: application/json", "apikey: " . $this->apikey]]);
        $response = curl_exec($curl);
        curl_close($curl);
        return json_decode($response, true);
    }
    public function sendPDFDocumentBase64($to, $invoice, $urlpdf, $texto)
    {
        $chx = curl_init($urlpdf);
        curl_setopt($chx, CURLOPT_RETURNTRANSFER, true);
        $data = curl_exec($chx);
        curl_close($chx);
        $pdfbase64 = base64_encode($data);
        $texto = str_replace(["\r\n", "\r", "\n"], "\\n", $texto);
        $texto = preg_replace("/[\\x00-\\x1F\\x80-\\x9F]/u", "", $texto);
        $ch = curl_init();
        $url = $this->server . "/message/sendMedia/" . $this->key;
        curl_setopt_array($ch, [CURLOPT_URL => $url, CURLOPT_RETURNTRANSFER => true, CURLOPT_ENCODING => "", CURLOPT_MAXREDIRS => 10, CURLOPT_TIMEOUT => 0, CURLOPT_FOLLOWLOCATION => true, CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1, CURLOPT_CUSTOMREQUEST => "POST", CURLOPT_POSTFIELDS => "{\r\n                \"number\": \"" . $to . "\",\r\n                \"mediatype\": \"document\", \r\n                \"caption\": \"" . $texto . "\",\r\n                \"media\": \"" . $pdfbase64 . "\",\r\n                \"fileName\": \"" . $invoice . ".pdf\",\r\n                \"options\": {\r\n                    \"delay\": 1200\r\n                }\r\n            }", CURLOPT_HTTPHEADER => ["Content-Type: application/json", "apikey: " . $this->apikey]]);
        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            return curl_error($ch);
        }
        curl_close($ch);
        return json_decode($result, true);
    }
    public function sendPDFBase64($to, $invoice, $urlpdf)
    {
        $chx = curl_init($urlpdf);
        curl_setopt($chx, CURLOPT_RETURNTRANSFER, true);
        $data = curl_exec($chx);
        curl_close($chx);
        $pdfbase64 = base64_encode($data);
        $ch = curl_init();
        curl_setopt_array($ch, [CURLOPT_URL => $this->server . "/message/sendMedia/" . $this->key, CURLOPT_RETURNTRANSFER => true, CURLOPT_ENCODING => "", CURLOPT_MAXREDIRS => 10, CURLOPT_TIMEOUT => 0, CURLOPT_FOLLOWLOCATION => true, CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1, CURLOPT_CUSTOMREQUEST => "POST", CURLOPT_POSTFIELDS => "{\r\n                \"number\": \"" . $to . "\",\r\n                \"mediatype\": \"document\", \r\n                \"media\": \"" . $pdfbase64 . "\", \r\n                \"fileName\": \"" . $invoice . ".pdf\",\r\n                \"options\": {\r\n                    \"delay\": 1200\r\n                }\r\n            }", CURLOPT_HTTPHEADER => ["Content-Type: application/json", "apikey: " . $this->apikey]]);
        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            return curl_error($ch);
        }
        curl_close($ch);
        return json_decode($result, true);
    }
    public function sendQRCode($to, $qrcode, $texto)
    {
        $texto = str_replace(["\r\n", "\r", "\n"], "\\n", $texto);
        $texto = preg_replace("/[\\x00-\\x1F\\x80-\\x9F]/u", "", $texto);
        $ch = curl_init();
        curl_setopt_array($ch, [CURLOPT_URL => $this->server . "/message/sendMedia/" . $this->key, CURLOPT_RETURNTRANSFER => true, CURLOPT_ENCODING => "", CURLOPT_MAXREDIRS => 10, CURLOPT_TIMEOUT => 0, CURLOPT_FOLLOWLOCATION => true, CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1, CURLOPT_CUSTOMREQUEST => "POST", CURLOPT_POSTFIELDS => "{\r\n                \"number\": \"" . $to . "\",\r\n                \"mediatype\": \"image\", \r\n                \"mimetype\": \"image/png\",\r\n                \"caption\": \"" . $texto . "\",\r\n                \"media\": \"" . $qrcode . "\"\r\n            }", CURLOPT_HTTPHEADER => ["Content-Type: application/json", "apikey: " . $this->apikey]]);
        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            return curl_error($ch);
        }
        curl_close($ch);
        return json_decode($result, true);
    }
    public function sendImagemUrl($to, $url, $texto)
    {
        $texto = str_replace(["\r\n", "\r", "\n"], "\\n", $texto);
        $texto = preg_replace("/[\\x00-\\x1F\\x80-\\x9F]/u", "", $texto);
        $ch = curl_init();
        curl_setopt_array($ch, [CURLOPT_URL => $this->server . "/message/sendMedia/" . $this->key, CURLOPT_RETURNTRANSFER => true, CURLOPT_ENCODING => "", CURLOPT_MAXREDIRS => 10, CURLOPT_TIMEOUT => 0, CURLOPT_FOLLOWLOCATION => true, CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1, CURLOPT_CUSTOMREQUEST => "POST", CURLOPT_POSTFIELDS => "{\r\n                \"number\": \"" . $to . "\",\r\n                \"mediatype\": \"image\", \r\n                \"mimetype\": \"image/png\",\r\n                \"caption\": \"" . $texto . "\",\r\n                \"media\": \"" . $url . "\",\r\n                \"fileName\": \"Imagem.png\"\r\n            }", CURLOPT_HTTPHEADER => ["Content-Type: application/json", "apikey: " . $this->apikey]]);
        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            return curl_error($ch);
        }
        curl_close($ch);
        return json_decode($result, true);
    }
}

?>