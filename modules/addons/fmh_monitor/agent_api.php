<?php
/**
 * FMH Monitor - Server Agent API
 * This file receives the payload from the remote server bash agent.
 */

define('WHMCS_EXTERNAL', true);
define('CLIENTAREA', false);
require_once __DIR__ . '/../../../init.php';

// Prevent session bloat from agent POST requests only
// (do NOT destroy session for GET requests like ping/validate/download - that logs admin out)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && session_id()) {
    session_destroy();
}

use Illuminate\Database\Capsule\Manager as Capsule;

require_once __DIR__ . '/fmh_monitor.php';

header('Content-Type: application/json');

// Auto-migrate columns needed for agent metrics (runs BEFORE admin opens panel)
try {
    if (!Capsule::schema()->hasColumn('mod_fmh_monitor_server_metrics', 'health_score')) {
        Capsule::schema()->table('mod_fmh_monitor_server_metrics', function($table) {
            $table->text('ssl_certs')->nullable();
            $table->text('docker_containers')->nullable();
            $table->text('db_health')->nullable();
            $table->text('kernel_vulns')->nullable();
            $table->text('backup_status')->nullable();
            $table->text('file_integrity')->nullable();
            $table->text('root_audit')->nullable();
            $table->bigInteger('net_bandwidth_rx')->nullable();
            $table->bigInteger('net_bandwidth_tx')->nullable();
            $table->bigInteger('net_speed_rx')->nullable();
            $table->bigInteger('net_speed_tx')->nullable();
            $table->integer('health_score')->nullable();
        });
    } else {
        // Health score exists but newer columns might be missing
        if (!Capsule::schema()->hasColumn('mod_fmh_monitor_server_metrics', 'root_audit')) {
            Capsule::schema()->table('mod_fmh_monitor_server_metrics', function($table) {
                $table->text('root_audit')->nullable();
            });
        }
    }
} catch (\Exception $e) {
    // Silent - if migration fails, agent will still work with existing columns
}

// =============================================
// PING ENDPOINT - Lightweight health check
// =============================================
if (isset($_GET['action']) && $_GET['action'] === 'ping') {
    header('Content-Type: application/json');
    header('Cache-Control: no-store');
    try {
        // Verify basic connectivity: DB + tables exist
        $dbOk = Capsule::schema()->hasTable('mod_fmh_monitor_servers');
        $colOk = Capsule::schema()->hasColumn('mod_fmh_monitor_server_metrics', 'health_score');
        $rootAuditOk = Capsule::schema()->hasColumn('mod_fmh_monitor_server_metrics', 'root_audit');
        echo json_encode([
            'status' => 'ok',
            'agent_current' => AGENT_CURRENT_VERSION,
            'db' => $dbOk,
            'columns_ok' => $colOk,
            'root_audit_ok' => $rootAuditOk,
            'time' => date('Y-m-d H:i:s')
        ]);
    } catch (\Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// =============================================
// VALIDATE AGENT SCRIPT - Pre-flight syntax check
// Checks if agent_bash.sh has valid bash syntax before deployment
// =============================================
if (isset($_GET['action']) && $_GET['action'] === 'validate_agent') {
    header('Content-Type: application/json');
    header('Cache-Control: no-store');
    $agentFile = __DIR__ . '/agent_bash.sh';
    $result = ['valid' => false, 'version' => '', 'lines' => 0, 'errors' => []];
    
    if (file_exists($agentFile)) {
        $content = file_get_contents($agentFile);
        $result['lines'] = substr_count($content, "\n") + 1;
        // Extract version
        if (preg_match('/AGENT_VERSION="([^"]+)"/', $content, $m)) {
            $result['version'] = $m[1];
        }
        
        // Validate bash syntax
        $tmpFile = sys_get_temp_dir() . '/fmh_validate_' . md5(time() . rand()) . '.sh';
        $cleanContent = str_replace("\r\n", "\n", str_replace("\r", "\n", $content));
        file_put_contents($tmpFile, $cleanContent);
        chmod($tmpFile, 0700);
        
        if (function_exists('exec') && !in_array('exec', explode(',', ini_get('disable_functions') ?: ''))) {
            exec('bash -n ' . escapeshellarg($tmpFile) . ' 2>&1', $output, $exitCode);
            if ($exitCode === 0) {
                $result['valid'] = true;
            } else {
                $result['errors'] = $output;
            }
        } else {
            // exec() disabled - validation done by cron.php (CLI PHP)
            $validFile = __DIR__ . '/agent_bash.valid';
            $result['valid'] = file_exists($validFile) && (time() - filemtime($validFile) < 86400);
            if (!$result['valid']) {
                $result['errors'] = ['exec() disabled in web PHP. Run cron.php (CLI) to validate.'];
            }
        }
        @unlink($tmpFile);
    } else {
        $result['errors'] = ['agent_bash.sh not found'];
    }
    
    // Cache validation result: creates/removes flag file
    $validFile = __DIR__ . '/agent_bash.valid';
    if ($result['valid']) {
        touch($validFile);
        @chmod($validFile, 0644);
    } else {
        @unlink($validFile);
    }
    
    echo json_encode($result);
    exit;
}

// =============================================
// DOWNLOAD AGENT ACTION (no token required, bypasses Cloudflare cache)
// Used by: auto-update, self-repair, and install
// =============================================
if (isset($_GET['action']) && $_GET['action'] === 'download_agent') {
    header('Content-Type: text/plain; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
    header('X-Content-Type-Options: nosniff');
    // Rate limit: max 30 downloads per minute per IP
    $dlIp = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
    $dlRateFile = sys_get_temp_dir() . '/fmh_dl_' . md5($dlIp) . '.rl';
    $dlData = @json_decode(@file_get_contents($dlRateFile), true) ?: ['count' => 0, 'window' => time()];
    if (time() - $dlData['window'] > 60) { $dlData = ['count' => 0, 'window' => time()]; }
    $dlData['count']++;
    @file_put_contents($dlRateFile, json_encode($dlData));
    if ($dlData['count'] > 30) {
        http_response_code(429);
        echo "# Rate limit exceeded. Try again later.\n";
        exit;
    }
    $agentFile = __DIR__ . '/agent_bash.sh';
    if (file_exists($agentFile)) {
        $content = file_get_contents($agentFile);
        // Inject version from single source of truth
        $content = str_replace('AGENT_VERSION="0.0"', 'AGENT_VERSION="' . AGENT_CURRENT_VERSION . '"', $content);
        $content = str_replace("\r\n", "\n", $content);
        $content = str_replace("\r", "\n", $content);
        echo $content;
    } else {
        http_response_code(404);
        echo "# Agent file not found.\n";
    }
    exit;
}

// Get headers
$headers = getallheaders();
$agentToken = null;

// Find token (case insensitive)
foreach ($headers as $key => $val) {
    if (strtolower($key) === 'agent-token') {
        $agentToken = trim($val);
        break;
    }
}

if (!$agentToken) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Missing Agent-Token header']);
    exit;
}

try {
    $server = Capsule::table('mod_fmh_monitor_servers')
        ->where('token', $agentToken)
        ->first();

    if (!$server) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Invalid Agent-Token. Server deleted or not found.', 'uninstall' => false]);
        exit;
    }

    // =============================================
    // HMAC SIGNATURE VERIFICATION (only when enforced)
    // =============================================
    $hmacSecret = !empty($server->hmac_secret) ? $server->hmac_secret : null;
    $signatureValid = true;
    
    // Only verify HMAC if admin explicitly enabled enforcement
    if ($hmacSecret && $server->hmac_enforced) {
        $clientSignature = null;
        foreach ($headers as $key => $val) {
            if (strtolower($key) === 'x-signature') { $clientSignature = trim($val); break; }
        }
        if ($clientSignature) {
            $rawPayload = file_get_contents('php://input');
            $expectedSignature = hash_hmac('sha256', $rawPayload, $hmacSecret);
            if (!hash_equals($expectedSignature, $clientSignature)) {
                http_response_code(403);
                echo json_encode(['status' => 'error', 'message' => 'Invalid HMAC signature.']);
                exit;
            }
        } else {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'HMAC signature required.', 'update_agent' => true]);
            exit;
        }
    }
    
    // =============================================
    // ANTI-REPLAY: Validate nonce (one-time use)
    // =============================================
    $nonceValid = true;
    $clientNonce = null;
    foreach ($headers as $key => $val) {
        if (strtolower($key) === 'x-nonce') {
            $clientNonce = trim($val);
            break;
        }
    }
    
    if ($hmacSecret && $clientNonce) {
        // Create nonce tracking table if needed
        if (!Capsule::schema()->hasTable('mod_fmh_monitor_api_nonces')) {
            Capsule::schema()->create('mod_fmh_monitor_api_nonces', function ($table) {
                $table->string('nonce', 128)->unique();
                $table->integer('server_id');
                $table->dateTime('created_at');
            });
        }
        
        // Check if nonce already used (replay attack)
        $existingNonce = Capsule::table('mod_fmh_monitor_api_nonces')
            ->where('nonce', $clientNonce)
            ->first();
        
        if ($existingNonce) {
            $nonceValid = false;
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Replay attack detected. Nonce already used.']);
            exit;
        }
        
        // Store nonce (valid for 5 minutes)
        Capsule::table('mod_fmh_monitor_api_nonces')->insert([
            'nonce' => $clientNonce,
            'server_id' => $server->id,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    // =============================================
    // IP BINDING: Verify server IP matches expected
    // =============================================
    if (!empty($server->expected_ip)) {
        $clientIp = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
        if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            $clientIp = $_SERVER['HTTP_CF_CONNECTING_IP'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $clientIp = trim($ips[0]);
        }
        
        // Allow if IP is in the bound list (supports multiple IPs and IPv4/IPv6)
        $boundIps = array_map('trim', explode(',', $server->expected_ip));
        if (!in_array($clientIp, $boundIps)) {
            // IP mismatch - log and alert but don't block (IPs can change)
            if (Capsule::schema()->hasTable('mod_fmh_monitor_logs')) {
                Capsule::table('mod_fmh_monitor_logs')->insert([
                    'service_id' => 0,
                    'server_id' => $server->id,
                    'event_type' => 'SECURITY',
                    'details' => "IP do agente mudou. Esperado: {$server->expected_ip}, Recebido: {$clientIp}",
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            }
            // Update IP automatically (DHCP-friendly)
            Capsule::table('mod_fmh_monitor_servers')->where('id', $server->id)->update(['last_ip' => $clientIp]);
        }
    } else {
        // First connection - bind the IP automatically
        $clientIp = isset($_SERVER['HTTP_CF_CONNECTING_IP']) ? $_SERVER['HTTP_CF_CONNECTING_IP'] : 
                   (isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]) : 
                   (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null));
        if ($clientIp && filter_var($clientIp, FILTER_VALIDATE_IP)) {
            Capsule::table('mod_fmh_monitor_servers')->where('id', $server->id)->update(['expected_ip' => $clientIp]);
        }
    }

    // Rate Limiting: Max 60 requests per minute per token
    $rateLimitKey = 'fmh_ratelimit_' . $server->id;
    $rateLimitFile = sys_get_temp_dir() . '/' . md5($rateLimitKey) . '.rl';
    $rateWindow = 60;
    $rateMax = 60;
    $rateData = @json_decode(@file_get_contents($rateLimitFile), true) ?: ['count' => 0, 'window_start' => time()];
    if (time() - $rateData['window_start'] > $rateWindow) {
        $rateData = ['count' => 0, 'window_start' => time()];
    }
    $rateData['count']++;
    @file_put_contents($rateLimitFile, json_encode($rateData));
    if ($rateData['count'] > $rateMax) {
        http_response_code(429);
        echo json_encode(['status' => 'error', 'message' => 'Rate limit exceeded. Max 60 req/min.']);
        exit;
    }

    $payloadRaw = isset($rawPayload) ? $rawPayload : file_get_contents('php://input');
    $data = json_decode($payloadRaw, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode([
            'status' => 'error', 
            'message' => 'Invalid JSON payload',
            'update_agent' => true // TEMPORARY RESCUE FLAG: forces broken v1.15 agents to update to v1.16
        ]);
        exit;
    }

    $machine_id = isset($data['machine_id']) ? substr($data['machine_id'], 0, 100) : 'unknown';

    // Anti-Clone / Fingerprint Security Check
    // Auto-accepts new machine_id if server was recently reinstalled or reset
    if (empty($server->machine_id) || $server->machine_id === 'unknown') {
        if ($machine_id !== 'unknown') {
            Capsule::table('mod_fmh_monitor_servers')->where('id', $server->id)->update(['machine_id' => $machine_id]);
        }
    } elseif ($server->machine_id !== $machine_id && $machine_id !== 'unknown') {
        // Machine ID changed - auto-accept if server was offline >1h (likely reinstalled) or was reset
        $lastCheck = $server->last_check ? strtotime($server->last_check) : 0;
        $offlineSeconds = time() - $lastCheck;
        if ($offlineSeconds > 3600 || $lastCheck == 0) {
            // Auto-update machine_id for reinstalled/reset servers
            Capsule::table('mod_fmh_monitor_servers')->where('id', $server->id)->update(['machine_id' => $machine_id]);
        } else {
            // Recent change - possible clone attack, reject
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'SECURITY: Token bound to different server. Use Resetar Machine ID in panel.', 'uninstall' => false]);
            exit;
        }
    }

    // Se for apenas uma checagem de instalação inicial, podemos abortar aqui e retornar sucesso
    if (isset($data['action']) && $data['action'] == 'verify_install') {
        echo json_encode(['status' => 'success', 'message' => 'Token and Identity verified successfully.']);
        exit;
    }

    // Extract agent version early (before any processing that might fail)
    $clientVersion = isset($data['agent_version']) ? $data['agent_version'] : '1.0';

    $now = date('Y-m-d H:i:s');

    // Extract metrics safely
    $load_1 = isset($data['load_1']) ? (float)$data['load_1'] : null;
    $cpu_usage = isset($data['cpu_usage']) ? (float)$data['cpu_usage'] : null;
    if ($cpu_usage === null) $cpu_usage = $load_1; // Fallback para agentes antigos
    $load_5 = isset($data['load_5']) ? (float)$data['load_5'] : null;
    $load_15 = isset($data['load_15']) ? (float)$data['load_15'] : null;
    $mem_total = isset($data['mem_total']) ? (int)$data['mem_total'] : null;
    $mem_used = isset($data['mem_used']) ? (int)$data['mem_used'] : null;
    $disk_total = isset($data['disk_total']) ? substr($data['disk_total'], 0, 50) : null;
    $disk_used_pct = isset($data['disk_used_pct']) ? (int)$data['disk_used_pct'] : null;
    $inodes_used_pct = isset($data['inodes_used_pct']) ? (int)$data['inodes_used_pct'] : null;
    $net_connections = isset($data['net_connections']) ? (int)$data['net_connections'] : null;
    $mail_queue = isset($data['mail_queue']) ? (int)$data['mail_queue'] : null;
    $mail_top_senders = isset($data['mail_top_senders']) ? substr($data['mail_top_senders'], 0, 500) : null;
    $os_uptime = isset($data['os_uptime']) ? substr($data['os_uptime'], 0, 255) : null;
    $top_processes = isset($data['top_processes']) ? $data['top_processes'] : null;
    $services_status = isset($data['services']) ? json_encode($data['services']) : null;
    
    // Optional: detect OS if sent in payload
    $os = isset($data['os']) ? substr($data['os'], 0, 100) : $server->os;

    // Store new metrics from agent v1.30+
    $ssl_certs = isset($data['ssl_certs']) ? json_encode($data['ssl_certs']) : null;
    $docker_containers = isset($data['docker_containers']) ? json_encode($data['docker_containers']) : null;
    $db_health = isset($data['db_health']) ? json_encode($data['db_health']) : null;
    $kernel_vulns = isset($data['kernel_vulns']) ? json_encode($data['kernel_vulns']) : null;
    $backup_status = isset($data['backup_status']) ? json_encode($data['backup_status']) : null;
    $file_integrity = isset($data['file_integrity']) ? json_encode($data['file_integrity']) : null;
    $root_audit = isset($data['root_audit']) ? json_encode($data['root_audit']) : null;
    $net_bandwidth_rx = isset($data['net_bandwidth_rx']) ? (int)$data['net_bandwidth_rx'] : null;
    $net_bandwidth_tx = isset($data['net_bandwidth_tx']) ? (int)$data['net_bandwidth_tx'] : null;
    $net_speed_rx = isset($data['net_speed_rx']) ? (int)$data['net_speed_rx'] : null;
    $net_speed_tx = isset($data['net_speed_tx']) ? (int)$data['net_speed_tx'] : null;
    
    // Calculate Health Score (0-100)
    $healthScore = 100;
    if ($disk_used_pct && $disk_used_pct > 90) $healthScore -= 20;
    elseif ($disk_used_pct && $disk_used_pct > 80) $healthScore -= 10;
    if ($mem_total > 0 && $mem_used > 0) {
        $memPct = ($mem_used / $mem_total) * 100;
        if ($memPct > 95) $healthScore -= 25;
        elseif ($memPct > 85) $healthScore -= 15;
        elseif ($memPct > 75) $healthScore -= 5;
    }
    if ($load_1 && $load_1 > 10) $healthScore -= 15;
    elseif ($load_1 && $load_1 > 5) $healthScore -= 8;
    if ($services_status) {
        $svcs = json_decode($services_status, true);
        if (is_array($svcs)) {
            $downCount = count(array_filter($svcs, function($s) { return $s === 'DOWN'; }));
            $healthScore -= ($downCount * 5);
        }
    }
    $healthScore = max(0, min(100, $healthScore));

    // Insert Metrics (with fallback for old agents if new columns don't exist yet)
    $metricsData = [
        'server_id' => $server->id,
        'created_at' => $now,
        'load_1' => $load_1,
        'cpu_usage' => $cpu_usage,
        'load_5' => $load_5,
        'load_15' => $load_15,
        'mem_total' => $mem_total,
        'mem_used' => $mem_used,
        'disk_total' => $disk_total,
        'disk_used_pct' => $disk_used_pct,
        'inodes_used_pct' => $inodes_used_pct,
        'net_connections' => $net_connections,
        'mail_queue' => $mail_queue,
        'mail_top_senders' => $mail_top_senders,
        'os_uptime' => $os_uptime,
        'top_processes' => $top_processes,
        'services_status' => $services_status,
    ];
    
    // Add new columns only if they exist (graceful fallback)
    $hasNewColumns = Capsule::schema()->hasColumn('mod_fmh_monitor_server_metrics', 'health_score');
    if ($hasNewColumns) {
        if (Capsule::schema()->hasColumn('mod_fmh_monitor_server_metrics', 'ssl_certs'))
            $metricsData['ssl_certs'] = $ssl_certs;
        if (Capsule::schema()->hasColumn('mod_fmh_monitor_server_metrics', 'docker_containers'))
            $metricsData['docker_containers'] = $docker_containers;
        if (Capsule::schema()->hasColumn('mod_fmh_monitor_server_metrics', 'db_health'))
            $metricsData['db_health'] = $db_health;
        if (Capsule::schema()->hasColumn('mod_fmh_monitor_server_metrics', 'kernel_vulns'))
            $metricsData['kernel_vulns'] = $kernel_vulns;
        if (Capsule::schema()->hasColumn('mod_fmh_monitor_server_metrics', 'backup_status'))
            $metricsData['backup_status'] = $backup_status;
        if (Capsule::schema()->hasColumn('mod_fmh_monitor_server_metrics', 'file_integrity'))
            $metricsData['file_integrity'] = $file_integrity;
        if (Capsule::schema()->hasColumn('mod_fmh_monitor_server_metrics', 'root_audit'))
            $metricsData['root_audit'] = $root_audit;
        if (Capsule::schema()->hasColumn('mod_fmh_monitor_server_metrics', 'net_bandwidth_rx'))
            $metricsData['net_bandwidth_rx'] = $net_bandwidth_rx;
        if (Capsule::schema()->hasColumn('mod_fmh_monitor_server_metrics', 'net_bandwidth_tx'))
            $metricsData['net_bandwidth_tx'] = $net_bandwidth_tx;
        if (Capsule::schema()->hasColumn('mod_fmh_monitor_server_metrics', 'net_speed_rx'))
            $metricsData['net_speed_rx'] = $net_speed_rx;
        if (Capsule::schema()->hasColumn('mod_fmh_monitor_server_metrics', 'net_speed_tx'))
            $metricsData['net_speed_tx'] = $net_speed_tx;
        if (Capsule::schema()->hasColumn('mod_fmh_monitor_server_metrics', 'health_score'))
            $metricsData['health_score'] = $healthScore;
    }
    
    Capsule::table('mod_fmh_monitor_server_metrics')->insert($metricsData);

    // Store SSL certificates separately
    if (isset($data['ssl_certs']) && is_array($data['ssl_certs']) && count($data['ssl_certs']) > 0) {
        if (!Capsule::schema()->hasTable('mod_fmh_monitor_ssl_certs')) {
            Capsule::schema()->create('mod_fmh_monitor_ssl_certs', function ($table) {
                $table->increments('id');
                $table->integer('server_id');
                $table->string('domain', 255);
                $table->string('issuer', 255)->nullable();
                $table->dateTime('expiry_date');
                $table->integer('days_remaining');
                $table->dateTime('created_at');
            });
        }
        // Delete old certs for this server
        Capsule::table('mod_fmh_monitor_ssl_certs')->where('server_id', $server->id)->delete();
        foreach ($data['ssl_certs'] as $cert) {
            if (!empty($cert['domain']) && !empty($cert['expiry_date'])) {
                Capsule::table('mod_fmh_monitor_ssl_certs')->insert([
                    'server_id' => $server->id,
                    'domain' => substr($cert['domain'], 0, 255),
                    'issuer' => isset($cert['issuer']) ? substr($cert['issuer'], 0, 255) : null,
                    'expiry_date' => date('Y-m-d H:i:s', strtotime($cert['expiry_date'])),
                    'days_remaining' => (int)($cert['days_remaining'] ?? 0),
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            }
        }
    }

    $agent_hostname = isset($data['hostname']) ? substr($data['hostname'], 0, 255) : null;
    $agent_ssh_port = isset($data['ssh_port']) ? (int)$data['ssh_port'] : 22;
    
    // IP Detection - always prefer IPv4 for main IP, store IPv6 separately
    $agent_ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
    if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        $agent_ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
    } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $agent_ip = trim($ips[0]);
    }
    
    $ipv4 = isset($data['ipv4']) && !empty($data['ipv4']) ? substr($data['ipv4'], 0, 100) : null;
    $ipv6 = isset($data['ipv6']) && !empty($data['ipv6']) ? substr($data['ipv6'], 0, 100) : null;
    
    // ALWAYS prefer IPv4 for the main ip field
    if ($ipv4 && filter_var($ipv4, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        $agent_ip = $ipv4;
    } elseif (!$ipv4 && $agent_ip && filter_var($agent_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        // Only use IPv6 if no IPv4 available
        $agent_ip = $agent_ip;
    }
    
    $ssh_keys_json = null;
    if (isset($data['ssh_keys']) && is_array($data['ssh_keys'])) {
        $keys = [];
        foreach ($data['ssh_keys'] as $b64) {
            $keys[] = base64_decode($b64);
        }
        $ssh_keys_json = json_encode($keys);
        
        // Detect new keys
        if (!empty($server->ssh_keys)) {
            $oldKeys = json_decode($server->ssh_keys, true);
            if (is_array($oldKeys)) {
                $newKeys = array_diff($keys, $oldKeys);
                if (count($newKeys) > 0) {
                    $suspectIp = "Desconhecido (Painel/Sistema)";
                    $lastSshLogin = Capsule::table('mod_fmh_monitor_ssh_logins')->where('server_id', $server->id)->where('created_at', '>=', date('Y-m-d H:i:s', strtotime('-15 minutes')))->orderBy('created_at', 'desc')->first();
                    if ($lastSshLogin) $suspectIp = $lastSshLogin->ip;

                    if (!class_exists('\FmhMonitor\Notifications')) {
                        require_once __DIR__ . '/lib/Notifications.php';
                    }
                    $notifications = new \FmhMonitor\Notifications();
                    $dummyService = (object)[
                        'name' => $server->name,
                        'ip' => $server->ip,
                        'port' => 'SYSTEM',
                        'client_id' => $server->client_id ?? 0,
                        'notify_client' => $server->notify_client ?? 0,
                        'channels' => $server->channels
                    ];
                    
                    $template = Capsule::table('mod_fmh_monitor_settings')->where('setting_key', 'template_alert_ssh_key')->value('setting_value');
                    if (!$template) $template = "🔑 *NOVA CHAVE SSH ADICIONADA* 🔑\nServidor: {nome_servico}\nDetalhes: Uma nova chave pública foi inserida no authorized_keys!\nIdentificação: {chave}\nIP Suspeito (Último Login): {ip}\nData/Hora: {hora}";

                    foreach ($newKeys as $nk) {
                        $parts = explode(' ', trim($nk));
                        $comment = count($parts) > 2 ? htmlspecialchars(end($parts)) : 'Sem comentário';
                        
                        $replaces = [
                            '{nome_servico}' => $server->name,
                            '{ip}' => $suspectIp,
                            '{chave}' => $comment,
                            '{hora}' => date('d/m/Y H:i:s')
                        ];
                        $notifications->sendAlert($dummyService, 'UP', $now, 'template_alert_ssh_key', $replaces, $template);
                        usleep(300000); // 300ms pause to prevent spam
                    }
                }
            }
        }
    }

    $updateData = [
        'last_check' => $now,
        'os' => $os,
        'agent_version' => $clientVersion,
        'hostname' => $agent_hostname,
        'ssh_port' => $agent_ssh_port,
        'ip' => $agent_ip,
        'ipv6' => $ipv6
    ];

    if ($ssh_keys_json !== null) {
        $updateData['ssh_keys'] = $ssh_keys_json;
    }

    if (isset($data['root_pwd_changed']) && $data['root_pwd_changed'] === "true") {
        $last_alert = $server->last_pwd_alert ? strtotime($server->last_pwd_alert) : 0;
        if (time() - $last_alert > 3600) {
            $updateData['last_pwd_alert'] = $now;
            
            $suspectIp = "Desconhecido (Painel/Sistema)";
            $lastSshLogin = Capsule::table('mod_fmh_monitor_ssh_logins')->where('server_id', $server->id)->where('created_at', '>=', date('Y-m-d H:i:s', strtotime('-15 minutes')))->orderBy('created_at', 'desc')->first();
            if ($lastSshLogin) $suspectIp = $lastSshLogin->ip;

            if (!class_exists('\FmhMonitor\Notifications')) {
                require_once __DIR__ . '/lib/Notifications.php';
            }
            $notifications = new \FmhMonitor\Notifications();
            $dummyService = (object)[
                'name' => $server->name,
                'ip' => $server->ip,
                'port' => 'SYSTEM',
                'client_id' => $server->client_id ?? 0,
                'notify_client' => $server->notify_client ?? 0,
                'channels' => $server->channels
            ];
            
            $template = Capsule::table('mod_fmh_monitor_settings')->where('setting_key', 'template_alert_pwd')->value('setting_value');
            if (!$template) $template = "⚠️ *ALERTA CRÍTICO DE SEGURANÇA* ⚠️\nServidor: {nome_servico}\nDetalhes: A senha do usuário root foi alterada diretamente no sistema Linux!\nIP Suspeito (Último Login): {ip}\nData/Hora: {hora}";
            
            $replaces = [
                '{nome_servico}' => $server->name,
                '{ip}' => $suspectIp,
                '{hora}' => date('d/m/Y H:i:s')
            ];
            $notifications->sendAlert($dummyService, 'UP', $now, 'template_alert_pwd', $replaces, $template);
        }
    }

    // Store auto-repair SSH key sent by agent (always overwrite if regenerated)
    if (!empty($data['repair_key']) && !empty($data['repair_pubkey'])) {
        $keysDir = __DIR__ . '/keys';
        if (!is_dir($keysDir)) { @mkdir($keysDir, 0700, true); }
        if (!file_exists("$keysDir/.htaccess")) {
            file_put_contents("$keysDir/.htaccess", "Deny from all\n");
        }
        @chmod($keysDir, 0700);
        $keyPath = "{$keysDir}/server_{$server->id}";
        $pubkeyPath = "{$keyPath}.pub";
        
        $privateKey = base64_decode($data['repair_key']);
        $publicKey = base64_decode($data['repair_pubkey']);
        
        // Always save the key (agent sends every cycle - always keep in sync)
        if ($privateKey && $publicKey) {
            file_put_contents($keyPath, $privateKey);
            file_put_contents($pubkeyPath, $publicKey);
            chmod($keyPath, 0600);
            chmod($pubkeyPath, 0600);
            $updateData['ssh_key'] = $keyPath;
            $updateData['ssh_pubkey'] = trim($publicKey);
        }
        
        // Update SSH user if detected by agent
        if (!empty($data['ssh_user_detected'])) {
            $sshUser = substr($data['ssh_user_detected'], 0, 50);
            if (empty($server->ssh_user) || $server->ssh_user === 'root') {
                $updateData['ssh_user'] = $sshUser;
            }
        }
    }

    // Update Server Status (always mark UP when agent successfully connects)
    $updateData['status'] = 'UP';
    Capsule::table('mod_fmh_monitor_servers')
        ->where('id', $server->id)
        ->update($updateData);

    $ipCache = [];

    // 9. Process Audit Commands
    if (isset($data['audit_commands']) && is_array($data['audit_commands'])) {
        foreach ($data['audit_commands'] as $cmd) {
            $geoStr = 'Desconhecido';
            $ip = $cmd['ip'];
            if (filter_var($ip, FILTER_VALIDATE_IP) && $ip !== '127.0.0.1' && $ip !== '::1') {
                if (isset($ipCache[$ip])) {
                    $geoStr = $ipCache[$ip];
                } else {
                    $ch = curl_init("https://ip-api.com/json/{$ip}?fields=country,city");
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
                    $geoResp = curl_exec($ch);
                    curl_close($ch);
                    if ($geoResp) {
                        $g = json_decode($geoResp, true);
                        if ($g && isset($g['city']) && isset($g['country'])) {
                            $geoStr = $g['city'] . ', ' . $g['country'];
                            $ipCache[$ip] = $geoStr;
                        }
                    }
                }
            }

            Capsule::table('mod_fmh_monitor_audit_commands')->insert([
                'server_id' => $server->id,
                'user' => substr($cmd['user'], 0, 100),
                'ip' => substr($cmd['ip'], 0, 100),
                'geolocation' => $geoStr,
                'command' => $cmd['command'],
                'created_at' => $cmd['timestamp']
            ]);
        }
    }

    // 10. Process SSH Logins
    if (isset($data['ssh_logins']) && is_array($data['ssh_logins'])) {
        $whitelisted_ips = [];
        if (!empty($server->ssh_whitelist_ips)) {
            $whitelisted_ips = array_map('trim', explode(',', $server->ssh_whitelist_ips));
        }
        
        foreach ($data['ssh_logins'] as $login) {
            if (in_array(trim($login['ip']), $whitelisted_ips)) {
                continue; // Ignore this IP as it is whitelisted
            }
            
            $exists = Capsule::table('mod_fmh_monitor_ssh_logins')
                ->where('server_id', $server->id)
                ->where('user', $login['user'])
                ->where('ip', $login['ip'])
                ->where('created_at', '>=', date('Y-m-d H:i:s', strtotime('-3 minutes')))
                ->first();
                
            if (!$exists) {
                $geoStr = 'Desconhecido';
                $ip = $login['ip'];
                if (filter_var($ip, FILTER_VALIDATE_IP) && $ip !== '127.0.0.1' && $ip !== '::1') {
                    if (isset($ipCache[$ip])) {
                        $geoStr = $ipCache[$ip];
                    } else {
                        $ch = curl_init("https://ip-api.com/json/{$ip}?fields=country,city");
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
                        $geoResp = curl_exec($ch);
                        curl_close($ch);
                        if ($geoResp) {
                            $g = json_decode($geoResp, true);
                            if ($g && isset($g['city']) && isset($g['country'])) {
                                $geoStr = $g['city'] . ', ' . $g['country'];
                                $ipCache[$ip] = $geoStr;
                            }
                        }
                    }
                }

                Capsule::table('mod_fmh_monitor_ssh_logins')->insert([
                    'server_id' => $server->id,
                    'user' => substr($login['user'], 0, 50),
                    'ip' => substr($login['ip'], 0, 100),
                    'geolocation' => $geoStr,
                    'status' => substr($login['status'], 0, 20),
                    'created_at' => $login['timestamp']
                ]);
                
                // Trigger Alert for successful (non-whitelisted) login
                if (strtoupper(substr($login['status'], 0, 20)) == 'SUCCESS') {
                    $template = Capsule::table('mod_fmh_monitor_settings')->where('setting_key', 'template_alert_ssh')->value('setting_value');
                    if (!$template) {
                        $template = "🚨 *NOVO LOGIN SSH DETECTADO* 🚨\nServidor: {nome_servico}\nUsuário: {usuario}\nIP: {ip}\nLocalização: {localidade}\nData/Hora: {hora}";
                    }
                    if (!class_exists('\FmhMonitor\Notifications')) {
                        require_once __DIR__ . '/lib/Notifications.php';
                    }
                    $notifications = new \FmhMonitor\Notifications();
                    $dummyService = (object)[
                        'name' => $server->name,
                        'ip' => $login['ip'],
                        'port' => '22',
                        'client_id' => $server->client_id ?? 0,
                        'notify_client' => $server->notify_client ?? 0,
                        'channels' => $server->channels
                    ];
                    $replaces = [
                        '{nome_servico}' => $server->name,
                        '{user}' => $login['user'],
                        '{usuario}' => $login['user'],
                        '{ip}' => $login['ip'],
                        '{geolocation}' => $geoStr,
                        '{localidade}' => $geoStr,
                        '{hora}' => date('d/m/Y H:i:s', strtotime($login['timestamp']))
                    ];
                    $notifications->sendAlert($dummyService, 'SSH', $login['timestamp'], 'template_alert_ssh', $replaces);
                }
            }
        }
    }

    // BRUTE-FORCE DETECTION: Track failed SSH attempts and auto-blacklist
    if (isset($data['ssh_logins']) && is_array($data['ssh_logins'])) {
        $failedAttempts = [];
        foreach ($data['ssh_logins'] as $login) {
            if (strtoupper(substr($login['status'], 0, 6)) == 'FAILED') {
                $ip = trim($login['ip']);
                if (!isset($failedAttempts[$ip])) $failedAttempts[$ip] = 0;
                $failedAttempts[$ip]++;
            }
        }
        
        // Check if blacklist table exists, create if not
        if (!Capsule::schema()->hasTable('mod_fmh_monitor_ip_blacklist')) {
            Capsule::schema()->create('mod_fmh_monitor_ip_blacklist', function ($table) {
                $table->increments('id');
                $table->integer('server_id');
                $table->string('ip', 100);
                $table->string('reason', 255)->default('Brute-force SSH detectado');
                $table->dateTime('blocked_until');
                $table->dateTime('created_at');
            });
        }
        
        foreach ($failedAttempts as $ipAddr => $count) {
            if ($count >= 5 && filter_var($ipAddr, FILTER_VALIDATE_IP)) {
                // Check recent failures in last 5 minutes
                $recentFailures = Capsule::table('mod_fmh_monitor_ssh_logins')
                    ->where('server_id', $server->id)
                    ->where('ip', $ipAddr)
                    ->where('status', 'FAILED')
                    ->where('created_at', '>=', date('Y-m-d H:i:s', strtotime('-5 minutes')))
                    ->count();
                
                if ($recentFailures >= 5) {
                    // Check if already blacklisted
                    $existing = Capsule::table('mod_fmh_monitor_ip_blacklist')
                        ->where('server_id', $server->id)
                        ->where('ip', $ipAddr)
                        ->where('blocked_until', '>', date('Y-m-d H:i:s'))
                        ->first();
                    
                    if (!$existing) {
                        // Determine block duration based on repeat offenses
                        $totalOffenses = Capsule::table('mod_fmh_monitor_ip_blacklist')
                            ->where('server_id', $server->id)
                            ->where('ip', $ipAddr)
                            ->count();
                        
                        if ($totalOffenses == 0) $blockHours = 1;
                        elseif ($totalOffenses == 1) $blockHours = 24;
                        else $blockHours = 720; // 30 days
                        
                        Capsule::table('mod_fmh_monitor_ip_blacklist')->insert([
                            'server_id' => $server->id,
                            'ip' => $ipAddr,
                            'reason' => "Brute-force SSH: $recentFailures falhas em 5 minutos (ofensa #" . ($totalOffenses + 1) . ")",
                            'blocked_until' => date('Y-m-d H:i:s', strtotime("+$blockHours hours")),
                            'created_at' => date('Y-m-d H:i:s')
                        ]);
                        
                        // Send critical alert
                        if (!class_exists('\FmhMonitor\Notifications')) {
                            require_once __DIR__ . '/lib/Notifications.php';
                        }
                        $notifications = new \FmhMonitor\Notifications();
                        $dummyService = (object)[
                            'name' => $server->name,
                            'ip' => $ipAddr,
                            'port' => 'SSH',
                            'client_id' => $server->client_id ?? 0,
                            'notify_client' => $server->notify_client ?? 0,
                            'channels' => $server->channels
                        ];
                        $replaces = [
                            '{nome_servico}' => $server->name,
                            '{ip}' => $ipAddr,
                            '{detalhe}' => "IP BLOQUEADO por $blockHours hora(s) - $recentFailures tentativas de brute-force detectadas em 5 minutos"
                        ];
                        $bruteForceTpl = Capsule::table('mod_fmh_monitor_settings')->where('setting_key', 'template_alert_bruteforce')->value('setting_value');
                        if (!$bruteForceTpl) $bruteForceTpl = "🚨 *ALERTA DE BRUTE-FORCE SSH* 🚨\nServidor: {nome_servico}\nIP Bloqueado: {ip}\nMotivo: {detalhe}\nData/Hora: {hora}";
                        $bruteReplaces = array_merge($replaces, ['{hora}' => date('d/m/Y H:i:s')]);
                        $notifications->sendAlert($dummyService, 'SECURITY', date('Y-m-d H:i:s'), 'template_alert_bruteforce', $bruteReplaces);
                    }
                }
            }
        }
    }

    $response = ['status' => 'success', 'message' => 'Metrics logged successfully'];
    
    // Safety: only send update_agent if the agent script is valid (prevents broken updates)
    $agentValidFile = __DIR__ . '/agent_bash.valid';
    $agentValid = file_exists($agentValidFile) && (time() - filemtime($agentValidFile) < 86400);
    
    // Version hints for intelligent self-repair
    $response['agent_current'] = AGENT_CURRENT_VERSION;
    if (version_compare($clientVersion, AGENT_CURRENT_VERSION, '<')) {
        if ($agentValid) {
            $response['update_agent'] = true;
            $response['latest_version'] = AGENT_CURRENT_VERSION;
            $clientMajor = explode('.', $clientVersion)[0] ?? '1';
            $clientMinor = explode('.', $clientVersion)[1] ?? '0';
            $currentMinor = explode('.', AGENT_CURRENT_VERSION)[1] ?? '0';
            if ((int)$currentMinor - (int)$clientMinor >= 2) {
                $response['force_update'] = true;
            }
        } else {
            // Agent script has syntax errors - DON'T push update
            $response['update_blocked'] = true;
            $response['update_blocked_reason'] = 'Agent script has syntax errors. Run validate_agent to check.';
        }
    }
    
    // Check if agent was recently crashing (no data for >10 min)
    if ($server->last_check && (time() - strtotime($server->last_check) > 600)) {
        $response['was_offline'] = true;
        $response['offline_minutes'] = round((time() - strtotime($server->last_check)) / 60);
    }

    // Security info for agent
    $response['hmac_enabled'] = !empty($hmacSecret);
    $response['ip_bound'] = !empty($server->expected_ip);
    
    // Send HMAC secret to agent if configured on server but agent doesn't have it yet
    // Also send when signature was invalid (agent has outdated secret after rotate)
    if ($hmacSecret && (empty($clientSignature) || !$signatureValid)) {
        $response['hmac_secret'] = $hmacSecret;
    }
    
    // Auto-generate HMAC secret for existing servers that don't have one
    if (empty($server->hmac_secret)) {
        $newAutoSecret = bin2hex(random_bytes(32));
        Capsule::table('mod_fmh_monitor_servers')->where('id', $server->id)->update(['hmac_secret' => $newAutoSecret]);
        $response['hmac_secret'] = $newAutoSecret;
        $response['hmac_enabled'] = true;
        $hmacSecret = $newAutoSecret; // Use it for response fields above
    }

    // Cleanup old nonces (>5 min old)
    try {
        Capsule::table('mod_fmh_monitor_api_nonces')
            ->where('created_at', '<', date('Y-m-d H:i:s', strtotime('-5 minutes')))
            ->delete();
    } catch (\Exception $e) {}

    echo json_encode($response);

} catch (\Exception $e) {
    http_response_code(500);
    $errorResponse = ['status' => 'error', 'message' => 'Server Error: ' . $e->getMessage(), 'agent_current' => AGENT_CURRENT_VERSION];
    // Critical: even on error, tell outdated agents to update so they can self-repair
    if (isset($clientVersion) && version_compare($clientVersion, AGENT_CURRENT_VERSION, '<')) {
        $errorResponse['update_agent'] = true;
        $errorResponse['latest_version'] = AGENT_CURRENT_VERSION;
        $errorResponse['force_update'] = true;
    }
    $errorResponse['hmac_enabled'] = !empty($hmacSecret);
    $errorResponse['ip_bound'] = !empty($server->expected_ip);
    echo json_encode($errorResponse);
}
