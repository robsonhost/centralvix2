<?php
/*
 * @ https://EasyToYou.eu - IonCube v14 Decoder Online
 * @ PHP 7.4
 * @ Decoder version: 1.0.2
 * @ Release: 10/08/2022
 */

// Decoded file for php version 74.
class efipixmpay
{
    private $licenseKey;
    private $baseDir;
    private $dias;
    private $whmcsUrl = "https://centralvix.com.br/painel/";
    private $UrlApiEfi = "https://pix.api.efipay.com.br";
    private $secret_key = "WHMCS@EFIPAYPIX#MPAY";
    private $license_file = "efipixlicense_cvx.dat";
    private $iv_secreto = "973sc41dcJtXuJJf04EG";
    private $verifyfilepath = "api/check.php";
    public function __construct($licenseKey = "")
    {
        $this->licenseKey = $licenseKey;
        $this->baseDir = __DIR__;
        $this->dias = 5;
    }
    public function checkLicense($forceRemote = false)
    {
        try {
            if (!class_exists('CvxModuleLicense', false)) {
                $sdk = dirname(__DIR__, 3) . '/painel/sdk/CvxModuleLicense.php';
                if (!is_file($sdk)) {
                    $sdk = dirname(__DIR__, 2) . '/painel/sdk/CvxModuleLicense.php';
                }
                if (defined('ROOTDIR') && is_file(ROOTDIR . '/painel/sdk/CvxModuleLicense.php')) {
                    $sdk = ROOTDIR . '/painel/sdk/CvxModuleLicense.php';
                }
                if (is_file($sdk)) {
                    require_once $sdk;
                }
            }
            if (class_exists('CvxModuleLicense', false)) {
                $result = CvxModuleLicense::validate((string) $this->licenseKey, [
                    'module_dir' => $this->baseDir,
                    'force' => (bool) $forceRemote,
                    'cache_file' => $this->baseDir . '/' . $this->license_file,
                    'cache_secret' => $this->secret_key,
                    'cache_days' => (int) $this->dias,
                ]);
                return [
                    'status' => (string) ($result['status'] ?? 'Invalid'),
                    'data' => date('Y-m-d'),
                    'erro' => (string) ($result['erro'] ?? ($result['description'] ?? '')),
                    'checkouturl' => (string) ($result['checkouturl'] ?? ($this->whmcsUrl . 'checkout.php')),
                    'source' => (string) ($result['source'] ?? ''),
                    'productname' => (string) ($result['productname'] ?? ''),
                ];
            }
            // Fallback legado se o SDK não estiver disponível
            return $this->checkLicenseLegacy((bool) $forceRemote);
        } catch (Throwable $e) {
            return [
                'status' => 'Invalid',
                'data' => date('Y-m-d'),
                'erro' => 'Erro interno na validação: ' . $e->getMessage(),
                'checkouturl' => $this->whmcsUrl . 'checkout.php',
                'source' => 'error',
                'productname' => '',
            ];
        }
    }
    private function checkLicenseLegacy($forceRemote = false)
    {
        return [
            'status' => 'Invalid',
            'data' => date('Y-m-d'),
            'erro' => 'SDK CentralVix ausente (painel/sdk/CvxModuleLicense.php).',
            'checkouturl' => $this->whmcsUrl . 'checkout.php' . (!empty($this->licenseKey) ? '?key=' . rawurlencode((string) $this->licenseKey) : ''),
            'source' => 'error',
            'productname' => '',
        ];
    }
    private function loadCvxSdk()
    {
        if (class_exists('CvxModuleLicense', false)) {
            return true;
        }
        $candidates = [
            dirname(__DIR__, 3) . '/painel/sdk/CvxModuleLicense.php',
            dirname(__DIR__, 2) . '/painel/sdk/CvxModuleLicense.php',
        ];
        if (defined('ROOTDIR')) {
            array_unshift($candidates, ROOTDIR . '/painel/sdk/CvxModuleLicense.php');
        }
        foreach ($candidates as $sdk) {
            if (is_file($sdk)) {
                require_once $sdk;
                break;
            }
        }
        return class_exists('CvxModuleLicense', false);
    }
    public function probePainelApi()
    {
        if ($this->loadCvxSdk()) {
            return CvxModuleLicense::probeApi();
        }
        try {
            $curl = curl_init();
            if ($curl === false) {
                return ["ok" => false, "message" => "cURL indisponível no servidor."];
            }
            curl_setopt($curl, CURLOPT_URL, $this->whmcsUrl . $this->verifyfilepath);
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query(["licensekey" => "PROBE"]));
            curl_setopt($curl, CURLOPT_TIMEOUT, 12);
            curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
            $response = curl_exec($curl);
            $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $err = curl_error($curl);
            curl_close($curl);
            if ($httpCode === 200 && is_string($response) && (stripos($response, "<status>") !== false || stripos($response, "status") !== false)) {
                return ["ok" => true, "message" => "API do painel respondendo (" . $this->whmcsUrl . "api/check.php)."];
            }
            return ["ok" => false, "message" => "API do painel sem resposta válida (HTTP {$httpCode}" . ($err ? " / {$err}" : "") . ")."];
        } catch (Throwable $e) {
            return ["ok" => false, "message" => "Falha ao consultar painel: " . $e->getMessage()];
        }
    }
    public function probeEfiApi($clientId, $clientSecret, $certFile)
    {
        try {
            if ($clientId === "" || $clientSecret === "" || $certFile === "" || $certFile === "nenhum_certificado_enviado") {
                return ["ok" => false, "message" => "Preencha Client_Id, Client_Secret e selecione um Certificado .pem válido em /certs."];
            }
            $certPath = $this->baseDir . "/certs/" . basename((string) $certFile);
            if (!is_file($certPath)) {
                return ["ok" => false, "message" => "Certificado não encontrado em /certs. Envie o arquivo .pem da EFI Pay."];
            }
            $info = $this->inspectCertificate($certPath);
            if (empty($info['ok'])) {
                return ["ok" => false, "message" => "API EFI: " . (string) ($info['message'] ?? 'certificado inválido')];
            }
            $autorizacao = base64_encode($clientId . ":" . $clientSecret);
            $token = $this->ApiGetAcessToken($autorizacao, $certPath);
            if (!is_array($token)) {
                return ["ok" => false, "message" => "API EFI não retornou resposta."];
            }
            if (!empty($token["access_token"])) {
                return ["ok" => true, "message" => "API EFI autenticada com sucesso (token OK, certificado {$info['type']})."];
            }
            $desc = (string) ($token["error_description"] ?? ($token["mensagem"] ?? ($token["error"] ?? "credenciais/certificado inválidos")));
            if (!empty($token['openssl_error'])) {
                $desc .= ' | OpenSSL: ' . $token['openssl_error'];
            }
            return ["ok" => false, "message" => "API EFI: " . $desc];
        } catch (Throwable $e) {
            return ["ok" => false, "message" => "Falha na API EFI: " . $e->getMessage()];
        }
    }
    public function getSystemHealthHtml(array $license, array $painel, array $efi)
    {
        if ($this->loadCvxSdk()) {
            return CvxModuleLicense::healthHtml($license, $painel, $efi, 'API EFI Pay');
        }
        $licOk = (($license["status"] ?? "") === "Active");
        $badge = static function ($ok, $label, $detail) {
            $color = $ok ? "#0a7a2f" : "#a94442";
            $bg = $ok ? "#e8f7ee" : "#fbeaea";
            $icon = $ok ? "OK" : "FALHA";
            return "<div style=\"margin:6px 0;padding:10px 12px;border-radius:6px;background:{$bg};border:1px solid {$color};\">"
                . "<strong style=\"color:{$color}\">[{$icon}] {$label}</strong>"
                . "<div style=\"margin-top:4px\">" . htmlspecialchars((string) $detail, ENT_QUOTES, "UTF-8") . "</div></div>";
        };
        $html = "<div style=\"margin-top:12px\"><strong>Diagnóstico CentralVix</strong>"
            . $badge($licOk, "Licença", (string) ($license["erro"] ?? ($licOk ? "Active" : "Inválida")))
            . $badge(!empty($painel["ok"]), "API / Painel", (string) ($painel["message"] ?? ""))
            . $badge(!empty($efi["ok"]), "API EFI Pay", (string) ($efi["message"] ?? ""))
            . "</div>";
        return $html;
    }
    public function getLicenseMessage($status, $error, $checkout = "")
    {
        if ($this->loadCvxSdk()) {
            return CvxModuleLicense::statusHtml([
                'status' => $status,
                'erro' => $error,
                'checkouturl' => $checkout,
            ], (string) $this->licenseKey);
        }
        $painel = "https://centralvix.com.br/painel";
        $status = trim((string) $status);
        $checkout = $checkout ?: ($painel . "/checkout.php" . (!empty($this->licenseKey) ? "?key=" . rawurlencode($this->licenseKey) : ""));
        $cta = "<p style=\"margin-top:12px\"><a class=\"btn btn-primary\" href=\"" . htmlspecialchars($checkout, ENT_QUOTES, "UTF-8") . "\" target=\"_blank\" rel=\"noopener\">Contratar / gerar licença</a></p>";
        $errorHtml = $error !== "" && $error !== null ? "<p>" . htmlspecialchars((string) $error, ENT_QUOTES, "UTF-8") . "</p>" : "";
        if ($status === "Active") {
            return "<div class=\"alert alert-success\"><h4 class=\"alert-heading\">Licença ativa</h4></div>";
        }
        return "<div class=\"alert alert-warning\"><h4 class=\"alert-heading\">Licença: " . htmlspecialchars($status ?: "desconhecido", ENT_QUOTES, "UTF-8") . "</h4>" . $errorHtml . $cta . "</div>";
    }
    private function adicionarDias($data, $dias)
    {
        $date = new DateTime($data);
        $date->modify("+" . $dias . " days");
        return $date->format("Y-m-d");
    }
    private function is_server_online($url)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 2);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $result !== false && 200 <= $httpCode && $httpCode < 400;
    }
    public function ArquivoExiste($filePath)
    {
        return file_exists($filePath);
    }
    public function redirect($link, $timeout = 1000)
    {
        echo "<script type=\"text/javascript\">\r\n        setTimeout(function () {\r\n            window.location.href = \"" . $link . "\";\r\n            }, " . $timeout . ");\r\n            </script>";
    }
    public function validateExpirationDate($date)
    {
        $currentDate = date("Ymd");
        $invoiceDate = trim(str_replace("-", "", $date));
        return $currentDate < $invoiceDate ? $date : date("Y-m-d");
    }
    public function pegaVersaoModulo($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        curl_close($ch);
        return json_decode($response, false);
    }
    public function PegaCPFCNPJ($doc, $userId)
    {
        $cpfCnpjData = WHMCS\Database\Capsule::table("tblcustomfieldsvalues")->where("fieldid", $doc)->where("relid", $userId)->value("value");
        return $cpfCnpjData ?? NULL;
    }
    public function cpf_ou_cnpj($cpfCnpj)
    {
        $taxId = preg_replace("/\\D/", "", $cpfCnpj);
        if (strlen($taxId) === 11) {
            return "cpf";
        }
        if (strlen($taxId) === 14) {
            return "cnpj";
        }
        return "invalid";
    }
    public function validador_cpf_cnpj($cpfCnpj)
    {
        $taxId = preg_replace("/\\D/", "", $cpfCnpj);
        if (11 < strlen($taxId)) {
            return $this->valida_cnpj($taxId);
        }
        return $this->valida_cpf($taxId);
    }
    private function valida_cpf($cpf)
    {
        $cpf = str_pad(preg_replace("/\\D/", "", $cpf), 11, "0", STR_PAD_LEFT);
        if (strlen($cpf) != 11 || preg_match("/(\\d)\\1{10}/", $cpf)) {
            return false;
        }
        for ($t = 9; $t < 11; $t++) {
            $sum = 0;
            for ($i = 0; $i < $t; $i++) {
                $sum += $cpf[$i] * ($t + 1 - $i);
            }
            $digit = $sum * 10 % 11;
            if ($cpf[$t] != ($digit == 10 ? 0 : $digit)) {
                return false;
            }
        }
        return true;
    }
    private function valida_cnpj($cnpj)
    {
        $cnpj = str_pad(preg_replace("/\\D/", "", $cnpj), 14, "0", STR_PAD_LEFT);
        if (strlen($cnpj) != 14 || preg_match("/(\\d)\\1{13}/", $cnpj)) {
            return false;
        }
        for ($t = 12; $t < 14; $t++) {
            $sum = 0;
            $pos = $t - 7;
            for ($i = 0; $i < $t; $i++) {
                $sum += $cnpj[$i] * $pos--;
                if ($pos < 2) {
                    $pos = 9;
                }
            }
            $digit = $sum % 11 < 2 ? 0 : 11 - $sum % 11;
            if ($cnpj[$t] != $digit) {
                return false;
            }
        }
        return true;
    }
    public function exibe_botao($imagem, $imagempadrao)
    {
        return !empty($imagem) ? $imagem : $imagempadrao;
    }
    public function verify_Table()
    {
        try {
            if (!WHMCS\Database\Capsule::schema()->hasTable("mod_efimpay")) {
                WHMCS\Database\Capsule::schema()->create("mod_efimpay", function ($table) {
                    $table->increments("id");
                    $table->integer("cliente_id");
                    $table->integer("fatura_id");
                    $table->string("txid");
                    $table->string("transacao");
                    $table->string("barcode");
                    $table->string("qrcode");
                    $table->text("qrcode_image");
                    $table->string("link");
                    $table->string("billet_link");
                    $table->string("pdf");
                    $table->decimal("valor", 11, 2);
                    $table->enum("tipo", ["boleto", "pix", "cartao"]);
                    $table->date("duedate");
                    $table->date("paiddate");
                    $table->string("status");
                    $table->enum("notenviado", ["N", "S"])->default("N");
                });
            }
        } catch (Exception $e) {
            logModuleCall("verify_Table", "create_table_error", $e->getMessage(), "N/A", "N/A");
        }
    }
    public function criptografar($key)
    {
        $encryption_key = base64_decode("modulespay@efipixmpay#cripto");
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length("aes-256-cbc"));
        $encrypted = openssl_encrypt($key, "aes-256-cbc", $encryption_key, 0, $iv);
        return base64_encode($encrypted . "::" . $iv);
    }
    public function descriptografar($key)
    {
        $encryption_key = base64_decode("modulespay@efipixmpay#cripto");
        list($encrypted_data, $iv) = explode("::", base64_decode($key), 2);
        return openssl_decrypt($encrypted_data, "aes-256-cbc", $encryption_key, 0, $iv);
    }
    public function Converter_DataBrasil($data)
    {
        return date("d/m/Y", strtotime($data));
    }
    public function Converter_DataBrComHoras($data)
    {
        return date("d/m/Y H:i:s", strtotime($data));
    }
    public function Converter_DataEUA($data)
    {
        return date("Y/m/d", strtotime($data));
    }
    public function _obfuscated_43616C63756C61725F4469666572656EC3A7615F456E7472655F447561735F4461746173_($vencimento, $data_atual)
    {
        $primeiraData = new DateTime($vencimento);
        $segundaData = new DateTime($data_atual);
        $intervalo = $primeiraData->diff($segundaData);
        return (int) $intervalo->format("%R%a");
    }
    public function deletar_arquivo_Remoto($file)
    {
        if (!empty($file) && file_exists($file) && unlink($file)) {
            return true;
        }
        return false;
    }
    public function inspectCertificate(string $certPath): array
    {
        if (!is_file($certPath) || !is_readable($certPath)) {
            return ['ok' => false, 'type' => 'missing', 'message' => 'Arquivo de certificado não encontrado ou sem permissão de leitura.'];
        }
        $size = (int) filesize($certPath);
        if ($size < 64) {
            return ['ok' => false, 'type' => 'empty', 'message' => 'Arquivo de certificado vazio ou inválido.'];
        }
        $ext = strtolower(pathinfo($certPath, PATHINFO_EXTENSION));
        $raw = (string) @file_get_contents($certPath, false, null, 0, 2048);
        $isPem = (stripos($raw, 'BEGIN CERTIFICATE') !== false)
            || (stripos($raw, 'BEGIN PRIVATE KEY') !== false)
            || (stripos($raw, 'BEGIN RSA PRIVATE KEY') !== false)
            || (stripos($raw, 'BEGIN ENCRYPTED PRIVATE KEY') !== false);
        $isP12 = in_array($ext, ['p12', 'pfx'], true) || (!$isPem && in_array($ext, ['p12', 'pfx', ''], true) && !preg_match('/^[\x09\x0a\x0d\x20-\x7e]*$/', substr($raw, 0, 80)));

        if ($isPem || in_array($ext, ['pem', 'crt', 'cer', 'key'], true)) {
            if (!$isPem) {
                return [
                    'ok' => false,
                    'type' => 'pem_invalid',
                    'message' => 'O arquivo .pem não está no formato PEM (falta -----BEGIN CERTIFICATE----- / PRIVATE KEY). Baixe novamente o certificado na EFI e envie o .pem correto.',
                ];
            }
            return ['ok' => true, 'type' => 'PEM', 'message' => 'Certificado PEM detectado.'];
        }
        if ($isP12 || in_array($ext, ['p12', 'pfx'], true)) {
            return ['ok' => true, 'type' => 'P12', 'message' => 'Certificado P12/PFX detectado.'];
        }
        return [
            'ok' => false,
            'type' => 'unknown',
            'message' => 'Formato de certificado não reconhecido. Use o .pem gerado pela EFI Pay (ou .p12/.pfx).',
        ];
    }
    public function ApiGetAcessToken($autorizacao, $certificado)
    {
        try {
            if (!is_string($certificado) || $certificado === "" || !is_file($certificado)) {
                return ["error" => "certificate_missing", "error_description" => "Arquivo de certificado inválido ou ausente."];
            }
            $info = $this->inspectCertificate($certificado);
            if (empty($info['ok'])) {
                return ["error" => "certificate_invalid", "error_description" => (string) ($info['message'] ?? 'Certificado inválido.')];
            }

            $opts = [
                CURLOPT_URL => $this->UrlApiEfi . "/oauth/token",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 25,
                CURLOPT_CONNECTTIMEOUT => 8,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => "{\"grant_type\": \"client_credentials\"}",
                CURLOPT_SSLCERT => $certificado,
                CURLOPT_HTTPHEADER => ["Authorization: Basic " . $autorizacao, "Content-Type: application/json"],
            ];

            if (($info['type'] ?? '') === 'P12') {
                $opts[CURLOPT_SSLCERTTYPE] = 'P12';
                $opts[CURLOPT_SSLCERTPASSWD] = '';
            } else {
                $opts[CURLOPT_SSLCERTTYPE] = 'PEM';
                $opts[CURLOPT_SSLCERTPASSWD] = '';
            }

            $curl = curl_init();
            if ($curl === false) {
                return ["error" => "curl", "error_description" => "cURL indisponível."];
            }
            curl_setopt_array($curl, $opts);
            $response = curl_exec($curl);
            $err = curl_error($curl);
            $errno = curl_errno($curl);
            curl_close($curl);

            if ($err !== '' && (stripos($err, 'PEM') !== false || stripos($err, 'certificate') !== false || stripos($err, 'SSL') !== false)) {
                $hint = ($info['type'] === 'P12')
                    ? 'Verifique a senha do .p12/.pfx (deixe em branco se a EFI gerou sem senha) ou converta para .pem.'
                    : 'O .pem parece corrompido ou incompleto. Na EFI, baixe o certificado de produção novamente e envie o arquivo .pem para modules/gateways/efipixmpay/certs.';
                return [
                    "error" => "certificate_openssl",
                    "error_description" => "Falha ao carregar certificado ({$info['type']}): " . $hint,
                    "openssl_error" => $err,
                ];
            }
            if (!is_string($response) || $response === "") {
                return ["error" => "empty_response", "error_description" => $err !== "" ? $err : "Sem resposta da API EFI.", "curl_errno" => $errno];
            }
            $returnAuth = json_decode($response, true);
            return is_array($returnAuth) ? $returnAuth : ["error" => "invalid_json", "error_description" => "Resposta inválida da API EFI."];
        } catch (Throwable $e) {
            return ["error" => "exception", "error_description" => $e->getMessage()];
        }
    }
    public function CadastrarWebhook($url, $chave, $access_token, $certificado)
    {
        $curl = curl_init();
        curl_setopt_array($curl, [CURLOPT_URL => $this->UrlApiEfi . "/v2/webhook/" . $chave, CURLOPT_RETURNTRANSFER => true, CURLOPT_ENCODING => "", CURLOPT_MAXREDIRS => 10, CURLOPT_TIMEOUT => 0, CURLOPT_FOLLOWLOCATION => true, CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1, CURLOPT_CUSTOMREQUEST => "PUT", CURLOPT_SSLCERT => $certificado, CURLOPT_SSLCERTPASSWD => "", CURLOPT_POSTFIELDS => "{\r\n                \"webhookUrl\": \"" . $url . "\"\r\n            }", CURLOPT_HTTPHEADER => ["Authorization: Bearer " . $access_token, "Content-Type: application/json", "x-skip-mtls-checking: true"]]);
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }
    public function ListarWebhook($autorizacao, $datainicio, $datafim, $certificado, $access_token)
    {
        $curl = curl_init();
        curl_setopt_array($curl, [CURLOPT_URL => $this->UrlApiEfi . "/v2/webhook/?inicio=" . $datainicio . "&fim=" . $datafim, CURLOPT_RETURNTRANSFER => true, CURLOPT_ENCODING => "", CURLOPT_MAXREDIRS => 10, CURLOPT_TIMEOUT => 0, CURLOPT_FOLLOWLOCATION => true, CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1, CURLOPT_CUSTOMREQUEST => "GET", CURLOPT_SSLCERT => $certificado, CURLOPT_SSLCERTPASSWD => "", CURLOPT_HTTPHEADER => ["Authorization: Bearer " . $access_token, "Content-Type: application/json"]]);
        $response = curl_exec($curl);
        curl_close($curl);
        $returnAuth = json_decode($response, true);
        return $returnAuth;
    }
    public function DeleteWebhook($chave, $autorizacao, $certificado, $access_token)
    {
        $curl = curl_init();
        curl_setopt_array($curl, [CURLOPT_URL => $this->UrlApiEfi . "/v2/webhook/" . $chave, CURLOPT_RETURNTRANSFER => true, CURLOPT_ENCODING => "", CURLOPT_MAXREDIRS => 10, CURLOPT_TIMEOUT => 0, CURLOPT_FOLLOWLOCATION => true, CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1, CURLOPT_CUSTOMREQUEST => "DELETE", CURLOPT_SSLCERT => $certificado, CURLOPT_SSLCERTPASSWD => "", CURLOPT_HTTPHEADER => ["Authorization: Bearer " . $access_token, "Content-Type: application/json"]]);
        $response = curl_exec($curl);
        curl_close($curl);
        $returnAuth = json_decode($response, true);
        return $returnAuth;
    }
    public function WebhookExiste($chave, $access_token, $certificado)
    {
        $curl = curl_init();
        curl_setopt_array($curl, [CURLOPT_URL => $this->UrlApiEfi . "/v2/webhook/" . $chave, CURLOPT_RETURNTRANSFER => true, CURLOPT_ENCODING => "", CURLOPT_MAXREDIRS => 10, CURLOPT_TIMEOUT => 0, CURLOPT_FOLLOWLOCATION => true, CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1, CURLOPT_CUSTOMREQUEST => "GET", CURLOPT_SSLCERT => $certificado, CURLOPT_SSLCERTPASSWD => "", CURLOPT_HTTPHEADER => ["Authorization: Bearer " . $access_token, "Content-Type: application/json"]]);
        $response = json_decode(curl_exec($curl), true);
        curl_close($curl);
        return $response;
    }
    public function GerarPixComVencimento($txid, $body, $access_token, $certificado)
    {
        $curl = curl_init();
        curl_setopt_array($curl, [CURLOPT_URL => $this->UrlApiEfi . "/v2/cobv/" . $txid, CURLOPT_RETURNTRANSFER => true, CURLOPT_ENCODING => "", CURLOPT_MAXREDIRS => 10, CURLOPT_TIMEOUT => 0, CURLOPT_FOLLOWLOCATION => true, CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1, CURLOPT_CUSTOMREQUEST => "PUT", CURLOPT_POSTFIELDS => json_encode($body), CURLOPT_SSLCERT => $certificado, CURLOPT_SSLCERTPASSWD => "", CURLOPT_HTTPHEADER => ["Authorization: Bearer " . $access_token, "Content-Type: application/json"]]);
        $responsePix = json_decode(curl_exec($curl), true);
        curl_close($curl);
        return $responsePix;
    }
    public function ModificarDadosdoPix($txid, $body, $access_token, $certificado)
    {
        $curl = curl_init();
        curl_setopt_array($curl, [CURLOPT_URL => $this->UrlApiEfi . "/v2/cobv/" . $txid, CURLOPT_RETURNTRANSFER => true, CURLOPT_ENCODING => "", CURLOPT_MAXREDIRS => 10, CURLOPT_TIMEOUT => 0, CURLOPT_FOLLOWLOCATION => true, CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1, CURLOPT_CUSTOMREQUEST => "PATCH", CURLOPT_POSTFIELDS => json_encode($body), CURLOPT_SSLCERT => $certificado, CURLOPT_SSLCERTPASSWD => "", CURLOPT_HTTPHEADER => ["Authorization: Bearer " . $access_token, "Content-Type: application/json"]]);
        $responsePix = json_decode(curl_exec($curl), true);
        curl_close($curl);
        return $responsePix;
    }
    public function GerarPixLocation($idlocationPix, $certificado, $access_token)
    {
        $curl = curl_init();
        curl_setopt_array($curl, [CURLOPT_URL => $this->UrlApiEfi . "/v2/loc/" . $idlocationPix . "/qrcode", CURLOPT_RETURNTRANSFER => true, CURLOPT_ENCODING => "", CURLOPT_MAXREDIRS => 10, CURLOPT_TIMEOUT => 0, CURLOPT_FOLLOWLOCATION => true, CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1, CURLOPT_CUSTOMREQUEST => "GET", CURLOPT_SSLCERT => $certificado, CURLOPT_SSLCERTPASSWD => "", CURLOPT_HTTPHEADER => ["Authorization: Bearer " . $access_token]]);
        $response = json_decode(curl_exec($curl), true);
        curl_close($curl);
        return $response;
    }
    public function PegarStatusDoPix($txid, $certificado, $access_token)
    {
        $curl = curl_init();
        curl_setopt_array($curl, [CURLOPT_URL => $this->UrlApiEfi . "/v2/cobv/" . $txid, CURLOPT_RETURNTRANSFER => true, CURLOPT_ENCODING => "", CURLOPT_MAXREDIRS => 10, CURLOPT_TIMEOUT => 0, CURLOPT_FOLLOWLOCATION => true, CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1, CURLOPT_CUSTOMREQUEST => "GET", CURLOPT_SSLCERT => $certificado, CURLOPT_SSLCERTPASSWD => "", CURLOPT_HTTPHEADER => ["Authorization: Bearer " . $access_token, "Content-Type: application/json"]]);
        $response = json_decode(curl_exec($curl), true);
        curl_close($curl);
        return $response;
    }
    public function formatValue($value)
    {
        return number_format((float) $value, 2, ".", "");
    }
    public function obterCamposPersonalizados()
    {
        $fields = [];
        try {
            $sqlFields = WHMCS\Database\Capsule::table("tblcustomfields")->select("id", "fieldname")->get();
            foreach ($sqlFields as $field) {
                $fields[$field->id] = $field->fieldname;
            }
        } catch (Exception $e) {
            logActivity("Erro ao obter campos personalizados: " . $e->getMessage());
        }
        return $fields;
    }
    public function listar_arquivos_diretorio($diretorio)
    {
        $fields = [];
        if (!is_dir($diretorio)) {
            @mkdir($diretorio, 0755, true);
        }
        if (!is_dir($diretorio) || !is_readable($diretorio)) {
            return $fields;
        }
        $arquivos = @scandir($diretorio);
        if (!is_array($arquivos)) {
            return $fields;
        }
        foreach ($arquivos as $file) {
            if ($file !== "." && $file !== ".." && is_file($diretorio . DIRECTORY_SEPARATOR . $file)) {
                $fields[] = $file;
            }
        }
        return $fields;
    }
    public function salvarQrCodeComoPng($qrcode, $nomeArquivo)
    {
        $diretorioDestino = $this->baseDir . "/pix";
        $outputFilePath = $diretorioDestino . "/" . $nomeArquivo;
        $decodedData = base64_decode(str_replace("data:image/png;base64,", "", $qrcode));
        if (!is_dir($diretorioDestino) && !mkdir($diretorioDestino, 493, true)) {
            logActivity("Não foi possível criar o diretório: " . $diretorioDestino);
            return false;
        }
        $result = file_put_contents($outputFilePath, $decodedData);
        if ($result !== false) {
            return true;
        }
        return false;
    }
    public function alert_msg($msg)
    {
        echo "<script type=\"text/javascript\">alert(\"" . $msg . "\");</script>";
    }
    public function generateRandomStringWithPrefix($prefix)
    {
        $characters = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
        $charactersLength = strlen($characters);
        $randomLength = rand(26, 35) - strlen($prefix);
        if ($randomLength <= 0) {
            throw new Exception("Prefixo muito longo! Deve ser menor que 26 caracteres.");
        }
        $randomString = "";
        for ($i = 0; $i < $randomLength; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $prefix . $randomString;
    }
    public function somenteNumeros($input): string
    {
        return preg_replace("/\\D/", "", $input);
    }
    public function GetProductNameByInvoiceId($invoiceId)
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
    public function Proximo_Dia_Util($data)
    {
        $feriadosFixos = ["01-01", "04-21", "05-01", "09-07", "10-12", "11-02", "11-15", "12-25"];
        $dia = date("d", strtotime($data));
        $mes = date("m", strtotime($data));
        $ano = date("Y", strtotime($data));
        $final = $data;
        while (in_array(date("N", strtotime($final)), [6, 7]) || in_array($mes . "-" . $dia, $feriadosFixos)) {
            $final = date("Y-m-d", strtotime($final . " +1 day"));
            $dia = date("d", strtotime($final));
            $mes = date("m", strtotime($final));
        }
        return $final;
    }
    public function formatarValorCentavoReais($value)
    {
        return number_format($value / 100, 2, ".", "");
    }
    public function formatarValorReaisCentavos($value)
    {
        $value = preg_replace("/[^\\d,.-]/", "", str_replace(",", ".", $value));
        return (int) round($value * 100);
    }
    public function gerarTxidPix($invoice)
    {
        $tamanhoString = strlen($invoice . "EFIMPAY");
        $x = 26 - $tamanhoString;
        $y = 35 - $tamanhoString;
        $tamanho = rand($x, $y);
        $caracteres = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $txid = substr(str_shuffle(str_repeat($caracteres, ceil($tamanho / strlen($caracteres)))), 0, $tamanho);
        return $invoice . "EFIMPAY" . $txid;
    }
    public function ValidarDatas($dataVencimento, $dataAtual)
    {
        $vencimento = new DateTime($dataVencimento);
        $data = new DateTime($dataAtual);
        $validade = $vencimento < $data;
        return $validade;
    }
    public function CompararDatas($data1, $data2)
    {
        $data1 = new DateTime($data1);
        $data2 = new DateTime($data2);
        $comparar = $data1 != $data2;
        return $comparar;
    }
    public function calcularMultaJurosDescontos($valorPago, $valorReal, $valordastaxas, $dataVencimento, $dataPagamento)
    {
        $valorEsperado = (float) $valorReal + (float) $valordastaxas;
        $valorPago = (float) $valorPago;
        $vencimento = new DateTime($dataVencimento);
        $pagamento = new DateTime($dataPagamento);
        if ($pagamento <= $vencimento) {
            if ($valorPago < $valorEsperado) {
                return number_format($valorPago - $valorEsperado, 2, ".", "");
            }
        } else if ($valorEsperado < $valorPago) {
            return number_format($valorPago - $valorEsperado, 2, ".", "");
        }
        return 0;
    }
    public function Get_DadosClientWHMCS($client)
    {
        $addressParts = explode(",", $client->address1);
        $endereco = trim($addressParts[0] ?? $client->address1);
        $endnumero = trim($addressParts[1] ?? "S/N");
        return ["clie_enderecocompleto" => $endereco . ", " . $endnumero . ", " . $client->address2, "clie_endereco" => trim($addressParts[0] ?? $client->address1), "clie_enderecoNum" => trim($addressParts[1] ?? "S/N"), "clie_nomecompleto" => $client->firstname . " " . $client->lastname];
    }
    public function converterParaNegativo($valor)
    {
        $valor = floatval($valor);
        return 0 < $valor ? -1 * $valor : $valor;
    }
    public function preparePixBody($pixefi, $invoice, $client, $cpfCnpj, $gatewaySettings, $valordaInvoice, $dataVencimento)
    {
        $dadosdoclient = $this->Get_DadosClientWHMCS($client);
        $body = ["calendario" => ["dataDeVencimento" => $dataVencimento, "validadeAposVencimento" => (int) $gatewaySettings["efipixmpay_tempoexpiracao"]], "devedor" => ["logradouro" => $dadosdoclient["clie_enderecocompleto"], "cidade" => $client->city, "uf" => $client->state, "nome" => $dadosdoclient["clie_nomecompleto"]], "valor" => ["original" => (string) $valordaInvoice], "chave" => $gatewaySettings["efipixmpay_chavepix"], "infoAdicionais" => [["nome" => "Serviço", "valor" => $this->getInvoiceItemsShortDescriptions($invoice->id)]]];
        if ($this->cpf_ou_cnpj($cpfCnpj) == "cnpj") {
            $body["devedor"]["cnpj"] = preg_replace("/\\D/", "", $cpfCnpj);
        } else {
            $body["devedor"]["cpf"] = preg_replace("/\\D/", "", $cpfCnpj);
        }
        if (!empty($gatewaySettings["efipixmpay_multaporatraso"])) {
            $multas = ["modalidade" => 2, "valorPerc" => $this->formatValue($gatewaySettings["efipixmpay_multaporatraso"])];
            $body["valor"]["multa"] = $multas;
        }
        if (!empty($gatewaySettings["efipixmpay_jurosporatraso"])) {
            $juroscobrado = ["modalidade" => 3, "valorPerc" => $gatewaySettings["efipixmpay_jurosporatraso"] == "on" ? $this->formatValue("1.00") : "0.00"];
            $body["valor"]["juros"] = $juroscobrado;
        }
        if (!empty($gatewaySettings["efipixmpay_descontoantecipado"])) {
            $dias = (int) $gatewaySettings["efipixmpay_descontoantecipado"];
            $percentualdesc = (float) $gatewaySettings["efipixmpay_percentualdescontoantecipado"];
            $datalimite = date("Y-m-d", strtotime("-" . $dias . " days", strtotime($dataVencimento)));
            $dataLimiteDesconto = $pixefi->ValidarDatas($datalimite, date("Y-m-d"));
            $descontoaplicado = ["modalidade" => 2, "descontoDataFixa" => [["data" => $datalimite, "valorPerc" => $this->formatValue($gatewaySettings["efipixmpay_percentualdescontoantecipado"])]]];
            $body["valor"]["desconto"] = $descontoaplicado;
        }
        return $body;
    }
    public function createNewPix($pixefi, $invoice, $client, $gatewaySettings, $valordaInvoice, $dataVencimento, $accessToken)
    {
        $cpfCnpj = $this->PegaCPFCNPJ($gatewaySettings["efipixmpay_campocpfcnpj"], $client->id);
        if (empty($cpfCnpj)) {
            logActivity("[EFI PIX MPAY] CPF/CNPJ vazio para o cliente: " . $client->firstname . " " . $client->lastname);
        } else {
            $certPath = ROOTDIR . "/modules/gateways/efipixmpay/certs/" . $gatewaySettings["efipixmpay_certificado"];
            $body = $this->preparePixBody($pixefi, $invoice, $client, $cpfCnpj, $gatewaySettings, $valordaInvoice, $dataVencimento);
            $txid = $this->gerarTxidPix($invoice->id);
            $responsePix = $this->GerarPixComVencimento($txid, $body, $accessToken, $certPath);
            return $responsePix;
        }
    }
    public function updateExistingPix($pixefi, $dadosdopix, $invoice, $client, $valordaInvoice, $accessToken, $gatewaySettings, $dataVencimento)
    {
        $cpfCnpj = $this->PegaCPFCNPJ($gatewaySettings["efipixmpay_campocpfcnpj"], $client->id);
        $cpfCnpj = preg_replace("/\\D/", "", $cpfCnpj);
        $certPath = ROOTDIR . "/modules/gateways/efipixmpay/certs/" . $gatewaySettings["efipixmpay_certificado"];
        $responseLoc = NULL;
        $body = $this->preparePixBody($pixefi, $invoice, $client, $cpfCnpj, $gatewaySettings, $valordaInvoice, $dataVencimento);
        $responsePix = $this->ModificarDadosdoPix($txid, $body, $accessToken, $certPath);
        if (!empty($responsePix["loc"]["id"])) {
            $idLocationPix = $responsePix["loc"]["id"];
            $responseLoc = $this->GerarPixLocation($idLocationPix, $certPath, $accessToken);
            $PixCopiaCola = $responseLoc["qrcode"];
            $imagemQrcode = $responseLoc["imagemQrcode"];
            $linkvisual = $responseLoc["linkVisualizacao"];
            $this->UpdateInsertTbl_Efi(["iddainvoice" => $invoice->id, "clientId" => $invoice->userid, "txid" => $responsePix["txid"], "qrcode" => $PixCopiaCola, "qrcode_image" => $imagemQrcode, "link" => $linkvisual, "valor" => $pixefi->formatValue($responsePix["valor"]["original"]), "tipo" => "pix", "duedate" => $responsePix["calendario"]["dataDeVencimento"], "status" => $responsePix["status"]]);
        }
        if (isset($responsePix["status"]) && $responsePix["status"] != "ATIVA") {
            return $responsePix["status"];
        }
        return $responseLoc;
    }
    public function UpdateTbl_Efi_Status($id, $status)
    {
        WHMCS\Database\Capsule::table("mod_efimpay")->where("fatura_id", $id)->update(["status" => $status]);
    }
    public function UpdateInsertTbl_Efi($dados)
    {
        $update = [];
        $update["fatura_id"] = $dados["iddainvoice"];
        $update["cliente_id"] = $dados["clientId"];
        if (isset($dados["txid"]) && !empty($dados["txid"])) {
            $update["txid"] = $dados["txid"];
        }
        if (isset($dados["qrcode"]) && !empty($dados["qrcode"])) {
            $update["qrcode"] = $dados["qrcode"];
        }
        if (isset($dados["qrcode_image"]) && !empty($dados["qrcode_image"])) {
            $update["qrcode_image"] = $dados["qrcode_image"];
        }
        if (isset($dados["link"]) && !empty($dados["link"])) {
            $update["link"] = $dados["link"];
        }
        if (isset($dados["valor"]) && !empty($dados["valor"])) {
            $update["valor"] = $dados["valor"];
        }
        if (isset($dados["duedate"]) && !empty($dados["duedate"])) {
            $update["duedate"] = $dados["duedate"];
        }
        if (isset($dados["transacao"]) && !empty($dados["transacao"])) {
            $update["transacao"] = $dados["transacao"];
        }
        if (isset($dados["barcode"]) && !empty($dados["barcode"])) {
            $update["barcode"] = $dados["barcode"];
        }
        if (isset($dados["billet_link"]) && !empty($dados["billet_link"])) {
            $update["billet_link"] = $dados["billet_link"];
        }
        if (isset($dados["pdf"]) && !empty($dados["pdf"])) {
            $update["pdf"] = $dados["pdf"];
        }
        if (isset($dados["barcode"]) && !empty($dados["barcode"])) {
            $update["barcode"] = $dados["barcode"];
        }
        if (isset($dados["tipo"]) && !empty($dados["tipo"])) {
            $update["tipo"] = $dados["tipo"];
        }
        if (isset($dados["barcode"]) && !empty($dados["barcode"])) {
            $update["barcode"] = $dados["barcode"];
        }
        if (isset($dados["paiddate"]) && !empty($dados["paiddate"])) {
            $update["paiddate"] = $dados["paiddate"];
        }
        if (isset($dados["status"]) && !empty($dados["status"])) {
            $update["status"] = $dados["status"];
        }
        if (isset($dados["notenviado"]) && !empty($dados["notenviado"])) {
            $update["notenviado"] = $dados["notenviado"];
        }
        if (0 < WHMCS\Database\Capsule::table("mod_efimpay")->where("fatura_id", "=", $dados["iddainvoice"])->count()) {
            unset($update["fatura_id"]);
            unset($update["client_id"]);
            WHMCS\Database\Capsule::table("mod_efimpay")->where("fatura_id", "=", $dados["iddainvoice"])->update($update);
        } else {
            WHMCS\Database\Capsule::table("mod_efimpay")->insert($update);
        }
    }
    public function getInvoice_Unpaid($invoiceId)
    {
        return WHMCS\Database\Capsule::table("tblinvoices")->where("id", $invoiceId)->where("status", "Unpaid")->first();
    }
    public function configureWebhook($webhookUrl, $accessToken, $certPath, $chavepix)
    {
        $cert_existe = $this->WebhookExiste($chavepix, $accessToken, $certPath);
        if (!isset($cert_existe["criacao"])) {
            $this->CadastrarWebhook($webhookUrl, $chavepix, $accessToken, $certPath);
        }
    }
    public function calculateInvoiceValues($pixefi, $valor, $data, $dataVencida, $gatewaySettings)
    {
        $taxaFixa = !empty($gatewaySettings["efipixmpay_taxafixa"]) ? $this->formatValue($gatewaySettings["efipixmpay_taxafixa"]) : 0;
        $taxaPerc = !empty($gatewaySettings["efipixmpay_taxapercentual"]) ? $gatewaySettings["efipixmpay_taxapercentual"] : 0;
        $valorTaxa = $taxaPerc != 0 ? $this->formatValue($valor * $taxaPerc / 100) : 0;
        $totalTaxas = $this->formatValue($taxaFixa + $valorTaxa);
        $juros = 0;
        $valordaInvoice = $this->formatValue($valor);
        if ($dataVencida) {
            $diasAtraso = (int) $this->_obfuscated_43616C63756C61725F4469666572656EC3A7615F456E7472655F447561735F4461746173_($data, date("Y-m-d"));
            $taxaMulta = !empty($gatewaySettings["efipixmpay_multaporatraso"]) ? $gatewaySettings["efipixmpay_multaporatraso"] : 0;
            $multa = $taxaMulta != 0 ? $valor * $taxaMulta / 100 : 0;
            $valorjuros = $gatewaySettings["efipixmpay_jurosporatraso"] === "on" ? (float) ($valor * 0 * $diasAtraso / 100) : 0;
            $jurosmulta = $this->formatValue($juros + $multa);
            $dataVencimento = $this->Proximo_Dia_Util(date("Y-m-d"));
        } else {
            $dataVencimento = $this->Proximo_Dia_Util($data);
        }
        return [$valordaInvoice, $dataVencimento, $totalTaxas, $jurosmulta];
    }
    public function processInvoice($pixefi, $invoice, $client, $gatewaySettings, $valordaInvoice, $dataVencimento, $accessToken)
    {
        $tblEfi = WHMCS\Database\Capsule::table("mod_efimpay")->where("fatura_id", $invoice->id)->first();
        if ($tblEfi) {
            $resultado = $this->updateExistingPix($pixefi, $tblEfi, $valordaInvoice, $dataVencimento, $accessToken, $gatewaySettings, $invoice, $client);
        } else {
            $resultado = $this->createNewPix($pixefi, $invoice, $client, $gatewaySettings, $valordaInvoice, $dataVencimento, $accessToken);
        }
        return $resultado;
    }
    public function getInvoiceItemsShortDescriptions($invoiceId)
    {
        $items = WHMCS\Database\Capsule::table("tblinvoiceitems")->where("invoiceid", $invoiceId)->get();
        if ($items->isEmpty()) {
            return false;
        }
        $shortDescriptions = "Fatura #" . $invoiceId;
        foreach ($items as $item) {
            $shortDescriptions .= " - " . $item->description . " (R\$ " . $item->amount . ")";
        }
        return substr($shortDescriptions, 0, 200);
    }
    public function getWebhookUrlByKey($data, $keyToCheck)
    {
        if (isset($data["webhooks"]) && is_array($data["webhooks"])) {
            foreach ($data["webhooks"] as $webhook) {
                if (isset($webhook["chave"]) && $webhook["chave"] === $keyToCheck) {
                    return isset($webhook["webhookUrl"]) ? $webhook["webhookUrl"] : false;
                }
            }
        }
        return false;
    }
    public function logGatewayAction($action, $data = [])
    {
        $logDirBase = __DIR__ . "/logs";
        $currentDate = date("Y-m-d");
        $logDir = $logDirBase . "/" . $currentDate;
        $logFile = $logDir . "/logs.log";
        if (!file_exists($logDirBase)) {
            mkdir($logDirBase, 493, true);
        }
        if (!file_exists($logDir)) {
            mkdir($logDir, 493, true);
        }
        $timestamp = date("Y-m-d H:i:s");
        $logMessage = "[" . $timestamp . "] Ação: " . $action;
        if (!empty($data)) {
            $logMessage .= " | Dados: " . json_encode($data, JSON_PRETTY_PRINT);
        }
        $logMessage .= PHP_EOL;
        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }
    public function verificarArquivoExiste($arquivo)
    {
        if (!file_exists($arquivo)) {
            return false;
        }
        return true;
    }
    public function CriarArquivo($arquivo)
    {
        if (!file_exists($arquivo)) {
            $handle = @fopen($arquivo, "w");
            if ($handle === false) {
                throw new Exception("Erro ao tentar criar o arquivo de licença.");
            }
            fclose($handle);
            chmod($arquivo, 384);
            return true;
        }
        return false;
    }
    public function clienteTemCredito($clientId)
    {
        $cliente = WHMCS\Database\Capsule::table("tblclients")->where("id", $clientId)->where("credit", "<>", "0.00")->count();
        return $cliente;
    }
    public function PegarCreditoCliente($clientId)
    {
        $cliente = WHMCS\Database\Capsule::table("tblclients")->where("id", $clientId)->value("credit");
        return (float) $cliente;
    }
    public function aplicarCreditoNaInvoice($valorCredito, $valorFatura)
    {
        $credito = floatval($valorCredito);
        $valorFatura = floatval($valorFatura);
        if (0 < $credito || 0 < $valorFatura) {
            $valorDescontado = min($credito, $valorFatura);
            $novoValor = $valorFatura - $valorDescontado;
            return $novoValor;
        }
        return $valorFatura;
    }
    public function naoArredondar($valor, $casasDecimais = 2)
    {
        $fator = pow(10, $casasDecimais);
        return floor($valor * $fator) / $fator;
    }
    public function obterDominioSemSubdominio()
    {
        $isSecure = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off")
            || (isset($_SERVER["SERVER_PORT"]) && (string) $_SERVER["SERVER_PORT"] === "443")
            || (!empty($_SERVER["HTTP_X_FORWARDED_PROTO"]) && strtolower((string) $_SERVER["HTTP_X_FORWARDED_PROTO"]) === "https");
        $protocol = $isSecure ? "https://" : "http://";
        $host = (string) ($_SERVER["HTTP_HOST"] ?? ($_SERVER["SERVER_NAME"] ?? "localhost"));
        $host = preg_replace("/^www\\./i", "", $host);
        $host = preg_replace("/:\\d+$/", "", $host);
        return strtolower(rtrim($protocol . $host, "/"));
    }
    public function cleanDomain($url)
    {
        $url = (string) $url;
        $url = preg_replace("#^https?://#i", "", $url);
        $url = preg_replace("/^www\\./i", "", $url);
        $url = preg_replace("#/.*$#", "", $url);
        $url = preg_replace("/:\\d+$/", "", $url);
        $url = rtrim($url, "/");
        return strtolower(trim($url));
    }
}

?>