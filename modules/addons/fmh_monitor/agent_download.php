<?php
/**
 * FMH Monitor - Agent Download Endpoint
 * Serves bash agent with anti-cache headers.
 * Extracts version from fmh_monitor.php (single source of truth).
 */
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
header('X-Content-Type-Options: nosniff');

$agentFile = __DIR__ . '/agent_bash.sh';
$monitorFile = __DIR__ . '/fmh_monitor.php';
$version = '1.0';
if (file_exists($monitorFile)) {
    $mc = file_get_contents($monitorFile);
    if (preg_match("/AGENT_CURRENT_VERSION',\s*'([^']+)'/", $mc, $m)) {
        $version = $m[1];
    }
}

if (file_exists($agentFile)) {
    $content = file_get_contents($agentFile);
    $content = str_replace('AGENT_VERSION="0.0"', 'AGENT_VERSION="' . $version . '"', $content);
    $content = str_replace("\r\n", "\n", $content);
    $content = str_replace("\r", "\n", $content);
    echo $content;
} else {
    http_response_code(404);
    echo "# Agent file not found. Please reinstall the module.\n";
}
