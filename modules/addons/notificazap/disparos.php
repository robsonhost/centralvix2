<?php
/*
 * @ https://EasyToYou.eu - IonCube v14 Decoder Online
 * @ PHP 7.4
 * @ Decoder version: 1.0.2
 * @ Release: 10/08/2022
 */

// Decoded file for php version 74.
require __DIR__ . "/../../../init.php";
header("Content-Type: application/json");
$core = new notificazapCore();
$onlineServer = $core->NOTIFICAZAP_Servidor_WhatsApp_Online(NZURLSERVIDORWHATSAPP);
if (!$onlineServer) {
    echo json_encode(["status" => "error", "message" => "Servidor do WhatsApp está offline."]);
    exit;
}
$onlineModulesPay = $core->NOTIFICAZAP_ModulesPay_Online(NZSITEMODULESPAY);
if ($onlineModulesPay) {
    $license = NOTIFICAZAP_check_license($core->NOTIFICAZAP_Licenca());
    $statusdalicenca = $license["status"];
} else {
    $license = NOTIFICAZAP_check_license($core->NOTIFICAZAP_Licenca());
    $statusdalicenca = $license["status"] ?? "Invalid";
}
if ($statusdalicenca !== "Active") {
    echo json_encode(["status" => "error", "message" => "Licença inválida ou expirada."]);
    exit;
}
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["status" => "error", "message" => "Método não permitido."]);
    exit;
}
$nome_campanha = trim($_POST["nome_campanha"] ?? "");
$tipo_mensagem = $_POST["tipo_mensagem"] ?? "texto";
$mensagem = trim($_POST["mensagem"] ?? "");
$imagem_selecionada = trim($_POST["imagem_selecionada"] ?? "");
if (empty($nome_campanha) || empty($mensagem)) {
    echo json_encode(["status" => "error", "message" => "Nome da campanha e mensagem são obrigatórios."]);
    exit;
}
if ($tipo_mensagem === "imagem" && empty($imagem_selecionada)) {
    echo json_encode(["status" => "error", "message" => "Selecione uma imagem para mensagens com imagem."]);
    exit;
}
if (!isset($_FILES["arquivo"]) || $_FILES["arquivo"]["error"] !== UPLOAD_ERR_OK) {
    echo json_encode(["status" => "error", "message" => "Arquivo não enviado ou erro no upload."]);
    exit;
}
$file = $_FILES["arquivo"];
$allowedExtensions = ["txt"];
$extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
if (!in_array($extension, $allowedExtensions)) {
    echo json_encode(["status" => "error", "message" => "Apenas arquivos .txt são permitidos."]);
    exit;
}
$uploadDir = __DIR__ . "/uploads/disparos/";
if (!is_dir($uploadDir) && !mkdir($uploadDir, 493, true)) {
    echo json_encode(["status" => "error", "message" => "Erro ao criar diretório de upload."]);
    exit;
}
if (!is_writable($uploadDir)) {
    echo json_encode(["status" => "error", "message" => "Diretório de upload não tem permissão de escrita."]);
    exit;
}
$filename = uniqid() . "_" . basename($file["name"]);
$filepath = $uploadDir . $filename;
if (!move_uploaded_file($file["tmp_name"], $filepath)) {
    echo json_encode(["status" => "error", "message" => "Erro ao salvar o arquivo."]);
    exit;
}
$lines = file($filepath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$valid = true;
foreach ($lines as $line) {
    $parts = explode(",", $line);
    if (count($parts) !== 2) {
        $valid = false;
    } else {
        $name = trim($parts[0]);
        $phone = trim($parts[1]);
        if (empty($name) || empty($phone)) {
            $valid = false;
        }
    }
    if (!$valid) {
        unlink($filepath);
        echo json_encode(["status" => "error", "message" => "Arquivo inválido. Deve conter 2 colunas separadas por vírgula: nome, telefone."]);
        exit;
    }
    try {
        WHMCS\Database\Capsule::table("mod_notificazap_disparos")->insert(["nome_campanha" => $nome_campanha, "arquivo" => $filepath, "tipo" => $tipo_mensagem, "imagem" => $imagem_selecionada, "mensagem" => $mensagem, "status" => "pending"]);
        echo json_encode(["status" => "success", "message" => "Campanha salva com sucesso. O envio será processado no próximo cron diário."]);
    } catch (Exception $e) {
        unlink($filepath);
        echo json_encode(["status" => "error", "message" => "Erro ao salvar a campanha: " . $e->getMessage() . " | Dados: " . json_encode(["nome_campanha" => $nome_campanha, "tipo" => $tipo_mensagem, "imagem" => $imagem_selecionada, "arquivo" => $filepath])]);
    }
}

?>