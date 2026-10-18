<?php

define('CLIENTAREA', true);
define('FORCESSL', true);

$whmcsPath = dirname(__DIR__, 3);
if (!is_file($whmcsPath . '/init.php')) {
    $whmcsPath = dirname(__DIR__, 4);
}
require_once $whmcsPath . '/init.php';

require_once dirname(__DIR__) . '/lib/Security.php';
CentralVixPlayerModule::autoload();

$file = basename($_GET['f'] ?? '');
if (!preg_match('/^img_[a-f0-9]+\.(jpg|png|webp|gif)$/', $file)) {
    http_response_code(404);
    exit;
}

$path = CentralVixPlayerModule::storagePath();
$found = null;
foreach (glob($path . '/c*/' . $file) as $candidate) {
    if (is_file($candidate)) {
        $found = $candidate;
        break;
    }
}
if (!$found) {
    http_response_code(404);
    exit;
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $found);
finfo_close($finfo);
$allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
if (!in_array($mime, $allowed, true)) {
    http_response_code(403);
    exit;
}

header('Content-Type: ' . $mime);
header('Cache-Control: public, max-age=86400');
header('X-Content-Type-Options: nosniff');
readfile($found);
exit;
