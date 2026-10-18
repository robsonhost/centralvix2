<?php

define('CLIENTAREA', true);

$whmcsPath = dirname(__DIR__, 3);
if (!is_file($whmcsPath . '/init.php')) {
    $whmcsPath = dirname(__DIR__, 4);
}
require_once $whmcsPath . '/init.php';
require_once dirname(__DIR__) . '/lib/Security.php';
CentralVixLicModule::autoload();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    CentralVixLicApiService::respond(['success' => false, 'message' => 'Use POST.'], 405);
}

$input = CentralVixLicApiService::parseInput();
$key = trim($input['license_key'] ?? '');
$domain = strtolower(trim($input['domain'] ?? ''));

CentralVixLicApiService::rateLimit(CentralVixLicSecurity::clientIp() . '|' . substr(hash('sha256', $key), 0, 16));

if ($key === '' || strlen($key) < 10) {
    usleep(random_int(100000, 300000));
    CentralVixLicApiService::respond(['success' => false, 'data' => ['valid' => false, 'message' => 'Requisição inválida.']], 400);
}

$ip = CentralVixLicApiService::resolveServerIp($input);
$result = CentralVixLicValidationService::validate($key, $domain, $ip);
CentralVixLicApiService::logApi('validate', $key, $result['data']['message'] ?? 'validate', $result['success']);
CentralVixLicApiService::respondValidation($result);
