<?php

define('CLIENTAREA', true);

$whmcsPath = dirname(__DIR__, 3);
if (!is_file($whmcsPath . '/init.php')) {
    $whmcsPath = dirname(__DIR__, 4);
}
require_once $whmcsPath . '/init.php';

require_once dirname(__DIR__) . '/lib/Security.php';
CentralVixPlayerModule::autoload();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$streamUrl = trim($_GET['stream_url'] ?? '');
$adapter = preg_replace('/[^a-z_]/', '', $_GET['adapter'] ?? 'auto');

if (!CentralVixPlayerSecurity::validateStreamUrl($streamUrl)) {
    echo json_encode(['success' => false, 'meta' => ['title' => '', 'artist' => '', 'listeners' => 0, 'cover' => '', 'online' => false]]);
    exit;
}

$meta = CentralVixStreamMetadata::fetch($streamUrl, $adapter ?: 'auto');
echo json_encode(['success' => true, 'meta' => $meta], JSON_UNESCAPED_UNICODE);
exit;
