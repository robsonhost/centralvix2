<?php
/*
 * @ https://EasyToYou.eu - IonCube v14 Decoder Online
 * @ PHP 7.4
 * @ Decoder version: 1.0.2
 * @ Release: 10/08/2022
 */

// Decoded file for php version 74.
require __DIR__ . "../../../init.php";
$directory = __DIR__ . "/../../../modules/addons/notificazap/downloads";
$systemUrl = rtrim(App::getSystemUrl(), "/");
$baseURL = $systemUrl . "/modules/addons/notificazap/downloads/";
$images = array_filter(scandir($directory), function ($file) use ($directory) {
    return is_file($directory . "/" . $file) && preg_match("/\\.(jpg|jpeg|png|gif)\$/i", $file);
});
$response = "";
foreach ($images as $image) {
    $imageURL = $baseURL . $image;
    $response .= "<div class=\"thumbnail-wrapper\" style=\"display:inline-block; margin:5px; text-align:center;\">\r\n                    <img src=\"" . $imageURL . "\" class=\"img-thumbnail\" style=\"width:100px; height:100px; cursor:pointer;\" data-image-url=\"" . $imageURL . "\">\r\n                  </div>";
}
echo $response;

?>