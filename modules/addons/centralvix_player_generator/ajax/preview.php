<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

CentralVixPlayerModule::requireClient();

$data = $_POST;
$data['socials'] = $_POST['socials'] ?? [];

$logoUrl = trim($_POST['existing_logo'] ?? '');
$bgUrl = trim($_POST['existing_bg'] ?? '');

if (!empty($_FILES['logo']['tmp_name']) && is_uploaded_file($_FILES['logo']['tmp_name'])) {
    $mime = mime_content_type($_FILES['logo']['tmp_name']);
    $logoUrl = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($_FILES['logo']['tmp_name']));
}
if (!empty($_FILES['background']['tmp_name']) && is_uploaded_file($_FILES['background']['tmp_name'])) {
    $mime = mime_content_type($_FILES['background']['tmp_name']);
    $bgUrl = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($_FILES['background']['tmp_name']));
}

$data['logo_url'] = $logoUrl;
$data['bg_url'] = $bgUrl;

$html = CentralVixPlayerService::renderPreviewHtml($data, true);
CentralVixPlayerModule::json(['success' => true, 'html' => $html]);
