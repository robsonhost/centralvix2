<?php
/*
 * @ https://EasyToYou.eu - IonCube v14 Decoder Online
 * @ PHP 7.4
 * @ Decoder version: 1.0.2
 * @ Release: 10/08/2022
 */

// Decoded file for php version 74.
require __DIR__ . "/../../../init.php";
$resultado = [];
if (isset($_GET["grupo"])) {
    $grupo = filter_input(INPUT_GET, "grupo", FILTER_SANITIZE_STRING);
    $servicos = getservices($grupo);
    $resultado[] = "<option value=\"todos\">Todos</option>";
    foreach ($servicos as $option) {
        $resultado[] = "<option value=\"" . htmlspecialchars($option->id) . "\">" . htmlspecialchars($option->name) . "</option>";
    }
} else {
    $resultado[] = "Nenhum dado para ser exibido";
}
echo implode("", $resultado);
function getServices($grupo)
{
    $query = WHMCS\Database\Capsule::table("tblproductgroups")->join("tblproducts", "tblproductgroups.id", "=", "tblproducts.gid")->join("tblhosting", "tblhosting.packageid", "=", "tblproducts.id")->select("tblproducts.name", "tblproducts.id")->distinct("tblproducts.id")->orderBy("tblproducts.name", "asc");
    if ($grupo !== "todos") {
        $query->where("tblproducts.gid", "=", $grupo);
    }
    return $query->get();
}

?>