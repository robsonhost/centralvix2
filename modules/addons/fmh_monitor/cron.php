<?php
// Carrega o ambiente do WHMCS para podermos usar o banco de dados (Capsule)
// [v1.18] - Sessão do Cron revertida para padrão CLI para evitar crashes no WHMCS
require_once dirname(__DIR__, 3) . '/init.php';
require_once __DIR__ . '/lib/Notifications.php';

use Illuminate\Database\Capsule\Manager as Capsule;

// Removida a verificação de CLI para maior compatibilidade com crons de cPanel que rodam como cgi-fcgi
// Porem, bloqueamos explicitamente o acesso via navegador/web para evitar abusos e falhas de seguranca
if (isset($_SERVER['HTTP_HOST']) || isset($_SERVER['REMOTE_ADDR'])) {
    http_response_code(403);
    die("Acesso bloqueado por seguranca. Este arquivo so pode ser executado via CLI (Linha de Comando) ou Tarefas Cron.");
}

require_once __DIR__ . '/fmh_monitor.php';

// Auto-Upgrade / Table check para caso o cron rode antes do usuário abrir o painel
if (!Capsule::schema()->hasTable('mod_fmh_monitor_settings')) {
    fmh_monitor_activate();
}
if (!Capsule::schema()->hasColumn('mod_fmh_monitor_services', 'channels')) {
    Capsule::schema()->table('mod_fmh_monitor_services', function($table) {
        $table->string('channels', 255)->default('whatsapp,telegram,email,webhooks');
    });
}
if (!Capsule::schema()->hasColumn('mod_fmh_monitor_services', 'strike_count')) {
    Capsule::schema()->table('mod_fmh_monitor_services', function($table) {
        $table->integer('strike_count')->default(0);
    });
}
if (!Capsule::schema()->hasColumn('mod_fmh_monitor_services', 'client_id')) {
    Capsule::schema()->table('mod_fmh_monitor_services', function($table) {
        $table->integer('client_id')->default(0);
        $table->boolean('notify_client')->default(0);
    });
}

// Obter as configurações do módulo (nova tabela V2)
$settingsRaw = Capsule::table('mod_fmh_monitor_settings')->get();
$moduleConfig = [];
foreach ($settingsRaw as $s) {
    $moduleConfig[$s->setting_key] = $s->setting_value;
}
    
// Verifica se a tabela existe / retornou algo
if (empty($moduleConfig)) {
    die("Configuracoes nao encontradas ou modulo inativo.");
}

$notifications = new \FmhMonitor\Notifications($moduleConfig);

$timeout = isset($moduleConfig['timeout']) ? (int)$moduleConfig['timeout'] : 5;
if ($timeout <= 0) $timeout = 5;

$services = Capsule::table('mod_fmh_monitor_services')
    ->where('status', '!=', 'PAUSED')
    ->get();

$now = date('Y-m-d H:i:s');

$activeServices = [];
foreach ($services as $service) {
    $intervalMinutes = isset($service->interval_minutes) ? (int)$service->interval_minutes : 1;
    if ($service->last_check) {
        $secondsSinceLastCheck = time() - strtotime($service->last_check);
        if ($secondsSinceLastCheck < (($intervalMinutes * 60) - 10)) {
            continue; // Pular se não for a hora
        }
    }
    $activeServices[] = $service;
}

$results = [];
$servicesToTest = [];
foreach ($activeServices as $svc) {
    $servicesToTest[$svc->id] = $svc;
}
$maxRetries = 4;

for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
    if (empty($servicesToTest)) break;
    
    // HTTP Concurrency
    $mh = curl_multi_init();
    $curlHandles = [];
    foreach ($servicesToTest as $svc) {
        $type = isset($svc->type) ? $svc->type : 'PORT';
        if ($type == 'HTTP') {
            $url = (strpos($svc->ip, 'http') === 0) ? $svc->ip : 'http://' . $svc->ip;
            if ($svc->port && $svc->port != 80 && $svc->port != 443) $url .= ':' . $svc->port;
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_multi_add_handle($mh, $ch);
            $curlHandles[$svc->id] = $ch;
        }
    }
    if (!empty($curlHandles)) {
        $active = null;
        do { $mrc = curl_multi_exec($mh, $active); } while ($mrc == CURLM_CALL_MULTI_PERFORM);
        while ($active && $mrc == CURLM_OK) {
            if (curl_multi_select($mh) == -1) { usleep(100); }
            do { $mrc = curl_multi_exec($mh, $active); } while ($mrc == CURLM_CALL_MULTI_PERFORM);
        }
        foreach ($curlHandles as $id => $ch) {
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($httpCode >= 200 && $httpCode < 400) {
                $results[$id] = 'UP';
                unset($servicesToTest[$id]); // Passou!
            }
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }
    }
    curl_multi_close($mh);

    // PORT e PING Concurrency (Lotes de 20)
    $chunks = array_chunk($servicesToTest, 20);
    foreach ($chunks as $chunk) {
        $sockets = [];
        $pings = [];
        foreach ($chunk as $svc) {
            $type = isset($svc->type) ? $svc->type : 'PORT';
            if ($type == 'PORT') {
                $sock = @stream_socket_client("tcp://{$svc->ip}:{$svc->port}", $errno, $errstr, $timeout, STREAM_CLIENT_ASYNC_CONNECT | STREAM_CLIENT_CONNECT);
                if ($sock) {
                    stream_set_blocking($sock, 0);
                    $sockets[$svc->id] = $sock;
                }
            } elseif ($type == 'PING') {
                $pingCmd = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') 
                    ? "ping -n 1 -w " . ($timeout * 1000) . " " . escapeshellarg($svc->ip) 
                    : "ping -c 1 -W " . $timeout . " " . escapeshellarg($svc->ip);
                
                $descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
                $proc = @proc_open($pingCmd, $descriptors, $pipes);
                if (is_resource($proc)) {
                    stream_set_blocking($pipes[1], 0);
                    $pings[$svc->id] = ['proc' => $proc, 'pipes' => $pipes];
                }
            }
        }
        
        $start = microtime(true);
        while ((count($sockets) > 0 || count($pings) > 0) && (microtime(true) - $start) < $timeout + 1) {
            if (count($sockets) > 0) {
                $read = $sockets; $write = $sockets; $except = null;
                if (@stream_select($read, $write, $except, 0, 100000) > 0) {
                    foreach ($write as $w) {
                        $id = array_search($w, $sockets);
                        if ($id !== false) {
                            if (@stream_socket_get_name($w, true) !== false) {
                                $results[$id] = 'UP';
                                unset($servicesToTest[$id]);
                            }
                            fclose($sockets[$id]);
                            unset($sockets[$id]);
                        }
                    }
                }
            }
            foreach ($pings as $id => $p) {
                $status = proc_get_status($p['proc']);
                if (!$status['running']) {
                    if ($status['exitcode'] === 0) {
                        $results[$id] = 'UP';
                        unset($servicesToTest[$id]);
                    }
                    fclose($p['pipes'][1]);
                    fclose($p['pipes'][2]);
                    proc_close($p['proc']);
                    unset($pings[$id]);
                }
            }
            usleep(50000);
        }
        
        foreach ($sockets as $id => $s) { fclose($s); }
        foreach ($pings as $id => $p) {
            @proc_terminate($p['proc']);
            fclose($p['pipes'][1]);
            fclose($p['pipes'][2]);
            proc_close($p['proc']);
        }
    }
    
    if ($attempt == $maxRetries) {
        foreach ($servicesToTest as $id => $svc) {
            $results[$id] = 'DOWN';
        }
    }
}

// Avaliação de Resultados
foreach ($activeServices as $service) {
    $currentStatus = isset($results[$service->id]) ? $results[$service->id] : 'DOWN';
    $previousStatus = $service->status;
    $strikeCount = isset($service->strike_count) ? (int)$service->strike_count : 0;
    
    $updateData = ['last_check' => $now];
    $alertToTrigger = null;

    if ($currentStatus == 'UP') {
        $updateData['strike_count'] = 0; // Reset Strikes
        $updateData['last_up'] = $now;
        
        if ($previousStatus == 'DOWN' || $previousStatus == 'OFFLINE') {
            $updateData['status'] = 'UP';
            $alertToTrigger = 'UP';
        } elseif ($previousStatus != 'UP') {
            $updateData['status'] = 'UP';
        }
    } else { // DOWN
        $updateData['strike_count'] = $strikeCount + 1;
        $updateData['last_down'] = $now;
        
        // A ideia do usuário foi brilhante: como já testamos 4x no MESMO segundo, 
        // 1 único strike de cron já é prova absoluta de que o servidor caiu.
        if ($updateData['strike_count'] >= 1 && $previousStatus != 'DOWN' && $previousStatus != 'OFFLINE') {
            $updateData['status'] = 'DOWN';
            $alertToTrigger = 'DOWN';
        }
    }

    Capsule::table('mod_fmh_monitor_services')
        ->where('id', $service->id)
        ->update($updateData);

    if ($alertToTrigger) {
        $msgStatus = ($alertToTrigger == 'UP') ? 'RECUPERADO' : 'OFFLINE';
        Capsule::table('mod_fmh_monitor_logs')->insert([
            'service_id' => $service->id,
            'event_type' => $alertToTrigger,
            'details' => "O status mudou para {$msgStatus}" . ($alertToTrigger == 'DOWN' ? " após 4 falhas seguidas no teste rápido." : "."),
            'created_at' => $now
        ]);
        $notifications->sendAlert($service, $alertToTrigger, $now);
    }
}

// ==========================================
// SERVER AGENT THRESHOLD CHECKS
// ==========================================
$servers = Capsule::table('mod_fmh_monitor_servers')->where('status', '!=', 'PAUSED')->get();
foreach ($servers as $srv) {
    $isDown = false;
    // Se o agente não enviou dados nos últimos 3 minutos, consideramos offline.
    if (!$srv->last_check || (time() - strtotime($srv->last_check) > 180)) {
        $isDown = true;
    }

    if ($isDown && $srv->status == 'UP') {
        Capsule::table('mod_fmh_monitor_servers')->where('id', $srv->id)->update(['status' => 'DOWN']);
        $dummyService = (object)[
            'name' => $srv->name . " (Server Agent)",
            'ip' => $srv->ip ?: 'Desconhecido',
            'port' => 'AGENT',
            'client_id' => $srv->client_id ?? 0,
            'notify_client' => $srv->notify_client ?? 0,
            'channels' => $srv->channels
        ];
        $notifications->sendAlert($dummyService, 'DOWN', $now);
        Capsule::table('mod_fmh_monitor_logs')->insert([
            'service_id' => 0, 'server_id' => $srv->id, 'event_type' => 'DOWN', 'details' => "Conexão com o Server Agent perdida.", 'created_at' => $now
        ]);
    } elseif (!$isDown && $srv->status == 'DOWN') {
        Capsule::table('mod_fmh_monitor_servers')->where('id', $srv->id)->update(['status' => 'UP']);
        $dummyService = (object)[
            'name' => $srv->name . " (Server Agent)",
            'ip' => $srv->ip ?: 'Desconhecido',
            'port' => 'AGENT',
            'client_id' => $srv->client_id ?? 0,
            'notify_client' => $srv->notify_client ?? 0,
            'channels' => $srv->channels
        ];
        $notifications->sendAlert($dummyService, 'UP', $now);
        Capsule::table('mod_fmh_monitor_logs')->insert([
            'service_id' => 0, 'server_id' => $srv->id, 'event_type' => 'UP', 'details' => "Conexão com o Server Agent restabelecida.", 'created_at' => $now
        ]);
    }
    
    if (!$isDown) {
        $latestMetric = Capsule::table('mod_fmh_monitor_server_metrics')->where('server_id', $srv->id)->orderBy('id', 'desc')->first();
        if ($latestMetric) {
            $thresholds = Capsule::table('mod_fmh_monitor_server_thresholds')->where('server_id', $srv->id)->get();
            foreach ($thresholds as $th) {
                // Prevenir spam: só alerta se o último alerta foi há mais de 1 hora (3600 seg)
                if ($th->last_alert && (time() - strtotime($th->last_alert) < 3600)) {
                    continue; 
                }

                $metricKey = $th->metric;
                $currentValue = 0;
                $metricDisplay = $metricKey;
                $templateKey = 'template_down';
                $replaces = ['{valor}' => 0, '{top_senders}' => '', '{top_processes}' => ''];
                
                if (!empty($latestMetric->top_processes)) {
                    $formattedProcs = "";
                    $procs = explode('|', $latestMetric->top_processes);
                    foreach ($procs as $p) {
                        $parts = preg_split('/\s+/', trim($p), 5);
                        if (count($parts) >= 5) {
                            $formattedProcs .= "- " . $parts[4] . " (" . $parts[2] . "% CPU / " . $parts[3] . "% RAM)\n";
                        }
                    }
                    $replaces['{top_processes}'] = trim($formattedProcs);
                } else {
                    $replaces['{top_processes}'] = "Nenhum processo listado.";
                }
                
                if ($metricKey == 'mem_used_pct' && $latestMetric->mem_total > 0) {
                    $currentValue = ($latestMetric->mem_used / $latestMetric->mem_total) * 100;
                    $metricDisplay = "Uso de Memória RAM (%)";
                    $templateKey = 'template_alert_ram';
                } elseif ($metricKey == 'disk_used_pct') {
                    $currentValue = $latestMetric->disk_used_pct;
                    $metricDisplay = "Uso de Disco (%)";
                    $templateKey = 'template_alert_disk';
                } elseif ($metricKey == 'inodes_used_pct') {
                    $currentValue = $latestMetric->inodes_used_pct;
                    $metricDisplay = "Uso de Inodes (%)";
                    $templateKey = 'template_alert_disk';
                } elseif ($metricKey == 'net_connections') {
                    $currentValue = $latestMetric->net_connections;
                    $metricDisplay = "Conexões de Rede (Possível DDoS)";
                    $templateKey = 'template_alert_ddos';
                } elseif ($metricKey == 'mail_queue') {
                    $currentValue = $latestMetric->mail_queue;
                    $metricDisplay = "Fila de E-mail";
                    $templateKey = 'template_alert_spam';
                    if (!empty($latestMetric->mail_top_senders)) {
                        $replaces['{top_senders}'] = str_replace('|', "\n", $latestMetric->mail_top_senders);
                    } else {
                        $replaces['{top_senders}'] = "Nenhum detalhe adicional.";
                    }
                } elseif ($metricKey == 'load_1') {
                    $currentValue = $latestMetric->load_1;
                    $metricDisplay = "Load Average (1m)";
                    $templateKey = 'template_alert_cpu';
                }
                
                $replaces['{valor}'] = round($currentValue, 2);
                
                $alertTriggered = false;
                if ($th->condition == '>' && $currentValue > $th->value) $alertTriggered = true;
                if ($th->condition == '<' && $currentValue < $th->value) $alertTriggered = true;
                if ($th->condition == '==' && $currentValue == $th->value) $alertTriggered = true;
                
                if ($alertTriggered) {
                    // Send alert via standard method using a dummy service object
                    $dummyService = (object)[
                        'name' => $srv->name,
                        'ip' => "{$metricDisplay} atingiu " . round($currentValue, 2) . " (Limite: {$th->condition} {$th->value})",
                        'port' => 'OVERLOAD',
                        'client_id' => $srv->client_id ?? 0,
                        'notify_client' => $srv->notify_client ?? 0,
                        'channels' => $th->alert_channels
                    ];
                    $notifications->sendAlert($dummyService, 'DOWN', $now, $templateKey, $replaces);
                    Capsule::table('mod_fmh_monitor_logs')->insert([
                        'service_id' => 0, 'server_id' => $srv->id, 'event_type' => 'ALERTA', 'details' => "{$metricDisplay} atingiu " . round($currentValue, 2), 'created_at' => $now
                    ]);
                    
                    Capsule::table('mod_fmh_monitor_server_thresholds')->where('id', $th->id)->update(['last_alert' => $now]);
                }
            }
            
            // Check Base Services Transitions (UP -> DOWN or DOWN -> UP)
            $previousMetric = Capsule::table('mod_fmh_monitor_server_metrics')->where('server_id', $srv->id)->orderBy('id', 'desc')->skip(1)->first();
            if ($previousMetric && $latestMetric->services_status && $previousMetric->services_status) {
                $currSvcs = json_decode($latestMetric->services_status, true);
                $prevSvcs = json_decode($previousMetric->services_status, true);
                if (is_array($currSvcs) && is_array($prevSvcs)) {
                    foreach ($currSvcs as $svcName => $svcStatus) {
                        if (isset($prevSvcs[$svcName])) {
                            if ($prevSvcs[$svcName] == 'UP' && $svcStatus == 'DOWN') {
                                // Service went down
                                $dummyService = (object)[
                                    'name' => $srv->name . " (Daemon: {$svcName})",
                                    'ip' => "Localhost",
                                    'port' => 'DAEMON',
                                    'client_id' => $srv->client_id ?? 0,
                                    'notify_client' => $srv->notify_client ?? 0,
                                    'channels' => $srv->channels
                                ];
                                $notifications->sendAlert($dummyService, 'DOWN', $now);
                                Capsule::table('mod_fmh_monitor_logs')->insert([
                                    'service_id' => 0, 'server_id' => $srv->id, 'event_type' => 'DOWN', 'details' => "Daemon {$svcName} caiu.", 'created_at' => $now
                                ]);
                            } elseif ($prevSvcs[$svcName] == 'DOWN' && $svcStatus == 'UP') {
                                // Service recovered or auto-remediated
                                $dummyService = (object)[
                                    'name' => $srv->name . " (Daemon: {$svcName})",
                                    'ip' => "Localhost",
                                    'port' => 'DAEMON',
                                    'client_id' => $srv->client_id ?? 0,
                                    'notify_client' => $srv->notify_client ?? 0,
                                    'channels' => $srv->channels
                                ];
                                if ($srv->auto_remediation) {
                                    $replaces = ['{acao}' => 'Systemctl Restart Automático', '{detalhe}' => "Daemon {$svcName} reiniciado com sucesso pela auto-cura."];
                                    $notifications->sendAlert($dummyService, 'UP', $now, 'template_alert_remediation', $replaces);
                                    Capsule::table('mod_fmh_monitor_logs')->insert([
                                        'service_id' => 0, 'server_id' => $srv->id, 'event_type' => 'AUTO-CURA', 'details' => "Daemon {$svcName} reiniciado automaticamente.", 'created_at' => $now
                                    ]);
                                } else {
                                    $notifications->sendAlert($dummyService, 'UP', $now);
                                    Capsule::table('mod_fmh_monitor_logs')->insert([
                                        'service_id' => 0, 'server_id' => $srv->id, 'event_type' => 'UP', 'details' => "Daemon {$svcName} restabelecido.", 'created_at' => $now
                                    ]);
                                }
                            }
                        }
                    }
                }
            }
            // Check SSL Certificate Expiry
            $sslCerts = Capsule::table('mod_fmh_monitor_ssl_certs')
                ->where('server_id', $srv->id)
                ->get();
            foreach ($sslCerts as $cert) {
                if ($cert->days_remaining <= 30 && $cert->days_remaining > 14) {
                    $severity = 'AVISO';
                    $alertChannels = $srv->channels;
                } elseif ($cert->days_remaining <= 14 && $cert->days_remaining > 7) {
                    $severity = 'ALERTA';
                    $alertChannels = $srv->channels;
                } elseif ($cert->days_remaining <= 7 && $cert->days_remaining > 0) {
                    $severity = 'CRITICO';
                    $alertChannels = $srv->channels;
                } elseif ($cert->days_remaining <= 0) {
                    $severity = 'EXPIRADO';
                    $alertChannels = $srv->channels;
                } else {
                    continue;
                }
                
                // Prevent spam: only alert once per day per certificate
                $lastSslAlert = Capsule::table('mod_fmh_monitor_logs')
                    ->where('server_id', $srv->id)
                    ->where('event_type', 'SSL_EXPIRY')
                    ->where('details', 'like', "%{$cert->domain}%")
                    ->where('created_at', '>=', date('Y-m-d H:i:s', strtotime('-1 day')))
                    ->first();
                if ($lastSslAlert) continue;
                
                $dummyService = (object)[
                    'name' => $srv->name . " (SSL: {$cert->domain})",
                    'ip' => "Certificado SSL",
                    'port' => $cert->days_remaining . ' dias restantes',
                    'client_id' => $srv->client_id ?? 0,
                    'notify_client' => $srv->notify_client ?? 0,
                    'channels' => $alertChannels
                ];
                $replaces = [
                    '{nome_servico}' => $srv->name,
                    '{dominio}' => $cert->domain,
                    '{dias}' => $cert->days_remaining,
                    '{severidade}' => $severity
                ];
                $sslTpl = Capsule::table('mod_fmh_monitor_settings')->where('setting_key', 'template_alert_ssl')->value('setting_value');
                if (!$sslTpl) $sslTpl = "🔒 *ALERTA DE CERTIFICADO SSL* 🔒\nServidor: {nome_servico}\nDominio: {dominio}\nDias Restantes: {dias}\nSeveridade: {severidade}\nData/Hora: {hora}";
                $notifications->sendAlert($dummyService, 'SSL_EXPIRY', $now, 'template_alert_ssl', $replaces);
                
                Capsule::table('mod_fmh_monitor_logs')->insert([
                    'service_id' => 0, 'server_id' => $srv->id, 'event_type' => 'SSL_EXPIRY',
                    'details' => "Certificado SSL para {$cert->domain} expira em {$cert->days_remaining} dias. Severidade: {$severity}",
                    'created_at' => $now
                ]);
            }
        }
    }
}

// ==========================================
// AUTO CLEANUP (PURGE OLD DATA)
// ==========================================
// Delete server metrics older than 24 horas to prevent DB bloat
$purgeMetricsDate = date('Y-m-d H:i:s', strtotime('-1 days'));
Capsule::table('mod_fmh_monitor_server_metrics')->where('created_at', '<', $purgeMetricsDate)->delete();

// Delete service logs older than 7 days
Capsule::table('mod_fmh_monitor_logs')
    ->where('created_at', '<', date('Y-m-d H:i:s', strtotime('-7 days')))
    ->delete();

// DELETE audit commands and SSH logins older than 30 days
if (Capsule::schema()->hasTable('mod_fmh_monitor_audit_commands')) {
    Capsule::table('mod_fmh_monitor_audit_commands')
        ->where('created_at', '<', date('Y-m-d H:i:s', strtotime('-30 days')))
        ->delete();
}
if (Capsule::schema()->hasTable('mod_fmh_monitor_ssh_logins')) {
    Capsule::table('mod_fmh_monitor_ssh_logins')
        ->where('created_at', '<', date('Y-m-d H:i:s', strtotime('-30 days')))
        ->delete();
}

echo "Cron rodou com sucesso as {$now}\n";

// =============================================
// Validate agent_bash.sh syntax (CLI PHP has exec enabled)
// Creates/deletes agent_bash.valid flag used by update safety check
// =============================================
$agentFile = __DIR__ . '/agent_bash.sh';
$validFile = __DIR__ . '/agent_bash.valid';
if (file_exists($agentFile) && function_exists('exec')) {
    $tmpFile = sys_get_temp_dir() . '/fmh_cron_validate.sh';
    $content = str_replace("\r\n", "\n", str_replace("\r", "\n", file_get_contents($agentFile)));
    file_put_contents($tmpFile, $content);
    @chmod($tmpFile, 0700);
    
    exec('bash -n ' . escapeshellarg($tmpFile) . ' 2>&1', $output, $exitCode);
    @unlink($tmpFile);
    
    if ($exitCode === 0) {
        touch($validFile);
        @chmod($validFile, 0666);
    } else {
        @unlink($validFile);
    }
}
exit(0);
