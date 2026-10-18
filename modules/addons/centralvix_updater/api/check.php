<?php

define('WHMCS', true);
require_once dirname(__DIR__, 4) . '/init.php';
require_once dirname(__DIR__) . '/lib/Security.php';
CentralVixUpdModule::autoload();

if (CentralVixUpdModule::setting('api_enabled', 'on') !== 'on') {
    CentralVixUpdUpdateService::respond(['success' => false, 'message' => 'API desativada.'], 503);
}

$ip = CentralVixUpdSecurity::clientIp();
CentralVixUpdUpdateService::rateLimit('check_' . $ip);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    CentralVixUpdUpdateService::respond(['success' => false, 'message' => 'Use POST.'], 405);
}

$input = CentralVixUpdUpdateService::parseInput();
CentralVixUpdUpdateService::check($input);
