<?php
/*
 * @ https://EasyToYou.eu - IonCube v14 Decoder Online
 * @ PHP 7.4
 * @ Decoder version: 1.0.2
 * @ Release: 10/08/2022
 */

// Decoded file for php version 74.
header("Content-Type: application/json");
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES["imagem"])) {
    $uploadDir = __DIR__ . "/../../../modules/addons/notificazap/downloads/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 493, true);
    }
    $file = $_FILES["imagem"];
    $fileExtension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $allowedExtensions = ["gif", "jpg", "jpeg", "png"];
    if (!in_array($fileExtension, $allowedExtensions)) {
        echo json_encode(["status" => "error", "message" => "Apenas arquivos GIF, JPG ou PNG são permitidos."]);
        exit;
    }
    $randomName = bin2hex(random_bytes(3)) . "." . $fileExtension;
    $filePath = $uploadDir . $randomName;
    if (move_uploaded_file($file["tmp_name"], $filePath)) {
        echo json_encode(["status" => "success", "fileUrl" => (string) $randomName]);
    } else {
        echo json_encode(["status" => "error", "message" => "Falha ao mover o arquivo para o diretório de destino."]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Nenhum arquivo enviado."]);
}

?>