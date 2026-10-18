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
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["status" => "error", "message" => "Método não permitido."]);
    exit;
}
if (!isset($_POST["nomedacampanha"]) || !isset($_POST["grupoprodutos"]) || !isset($_POST["produtos"]) || !isset($_POST["status"]) || !isset($_POST["tipomensagem"]) || !isset($_POST["mensagem"])) {
    echo json_encode(["status" => "error"]);
    exit;
}
$nomedacampanha = $_POST["nomedacampanha"];
$grupoprodutos = $_POST["grupoprodutos"];
$produtos = $_POST["produtos"];
$status = $_POST["status"];
$tipomensagem = $_POST["tipomensagem"];
$imagem = $_POST["urldaimagem"];
$mensagem = $_POST["mensagem"];
$cron = $_POST["cron"];
if (!empty($imagem)) {
    $path = parse_url($imagem, PHP_URL_PATH);
    $fileName = basename($path);
} else {
    $fileName = "";
}
$userenvios = [];
$produtosQuery = WHMCS\Database\Capsule::table("tblproducts");
if ($produtos === "todos") {
    $produtosQuery = $grupoprodutos === "todos" ? $produtosQuery->orderBy("id", "asc")->get() : $produtosQuery->where("gid", $grupoprodutos)->orderBy("id", "asc")->get();
} else {
    $produtosQuery = $produtosQuery->where("id", $produtos)->orderBy("id", "asc")->get();
}
foreach ($produtosQuery as $product) {
    $usuarios = WHMCS\Database\Capsule::table("tblhosting")->join("tblclients", "tblhosting.userid", "=", "tblclients.id")->where("tblhosting.packageid", $product->id)->when($status !== "Todos", function ($query) use ($status) {
        return $query->where("tblclients.status", $status);
    })->orderBy("tblhosting.id", "asc")->pluck("tblhosting.userid");
    $userenvios = array_merge($userenvios, $usuarios->toArray());
}
$listadeusuarios = array_unique($userenvios);
$qtdeaenviar = count($listadeusuarios);
if ($qtdeaenviar === 0) {
    echo json_encode(["status" => "error", "message" => "Nenhuma Notificação para ser enviada!"]);
    exit;
}
$dados = ["nome" => $nomedacampanha . "-" . date("YmdHis"), "idclientes" => implode(",", $listadeusuarios), "grupo" => $grupoprodutos, "produto" => $produtos, "status" => $status, "cron" => $cron, "mensagem" => $mensagem, "type" => $tipomensagem, "urlimagem" => $fileName, "data" => date("Y-m-d H:i:s"), "enviado" => "no"];
try {
    WHMCS\Database\Capsule::table("mod_notificazap_cron")->insert($dados);
    echo json_encode(["status" => "success", "message" => "Foi agendado " . $qtdeaenviar . " mensagens para ser enviado no próximo CRON"]);
    exit;
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    exit;
}

?>