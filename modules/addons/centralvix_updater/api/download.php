<?php

define('WHMCS', true);
require_once dirname(__DIR__, 4) . '/init.php';
require_once dirname(__DIR__) . '/lib/Security.php';
CentralVixUpdModule::autoload();

if (CentralVixUpdModule::setting('api_enabled', 'on') !== 'on') {
    http_response_code(503);
    exit('API desativada.');
}

$ip = CentralVixUpdSecurity::clientIp();
CentralVixUpdUpdateService::rateLimit('download_' . $ip);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Use POST.']);
    exit;
}

$input = CentralVixUpdUpdateService::parseInput();
$token = trim((string) ($input['token'] ?? $_POST['token'] ?? ''));

$result = CentralVixUpdDownloadService::consumeToken($token);
if (!$result['success']) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $result['message']]);
    exit;
}

$path = $result['path'];
$version = $result['version'];
$filename = $result['filename'];

header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($path));
header('X-CVIX-Version: ' . $version->version);
header('X-CVIX-SHA256: ' . $version->sha256_hash);
header('Cache-Control: no-store');

$fp = fopen($path, 'rb');
if ($fp) {
    while (!feof($fp)) {
        echo fread($fp, 8192);
        flush();
    }
    fclose($fp);
}
exit;
