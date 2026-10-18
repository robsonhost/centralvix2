<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

$streamUrl = trim($_GET['stream_url'] ?? $_POST['stream_url'] ?? '');
$adapter = preg_replace('/[^a-z_]/', '', $_GET['adapter'] ?? $_POST['adapter'] ?? 'auto');

if (!CentralVixPlayerSecurity::validateStreamUrl($streamUrl)) {
    CentralVixPlayerModule::json(['success' => false, 'meta' => CentralVixStreamMetadata::fetch('', 'auto')]);
}

$meta = CentralVixStreamMetadata::fetch($streamUrl, $adapter ?: 'auto');
CentralVixPlayerModule::json(['success' => true, 'meta' => $meta]);
