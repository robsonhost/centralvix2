<?php
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use Illuminate\Database\Capsule\Manager as Capsule;

// Define a versão atual do agente em um único lugar para evitar erros e inconsistências
if (!defined('AGENT_CURRENT_VERSION')) {
    define('AGENT_CURRENT_VERSION', '1.46');
}

function fmh_monitor_config()
{
    return [
        'name' => 'FMH Monitor',
        'description' => 'Módulo avançado de monitoramento de ping e serviços (IP+Porta). Painel completo para gerenciamento de notificações, templates e webhooks.',
        'author' => 'FMHospeda',
        'language' => 'portuguese-br',
        'version' => '2.0',
        'fields' => [] // Configurações foram movidas para o painel do módulo
    ];
}

function fmh_monitor_activate()
{
    try {
        if (!Capsule::schema()->hasTable('mod_fmh_monitor_services')) {
            Capsule::schema()->create(
                'mod_fmh_monitor_services',
                function ($table) {
                    $table->increments('id');
                    $table->string('name', 255);
                    $table->string('ip', 100);
                    $table->integer('port')->nullable();
                    $table->enum('type', ['PORT', 'PING', 'HTTP'])->default('PORT');
                    $table->integer('interval_minutes')->default(1);
                    $table->enum('status', ['UP', 'DOWN', 'PAUSED'])->default('UP');
                    $table->string('channels', 255)->default('whatsapp,telegram,email,webhooks');
                    $table->dateTime('last_check')->nullable();
            $table->text('ssh_keys')->nullable();
            $table->dateTime('last_pwd_alert')->nullable();
                    $table->dateTime('last_down')->nullable();
                    $table->dateTime('last_up')->nullable();
                }
            );
        }

        if (!Capsule::schema()->hasTable('mod_fmh_monitor_logs')) {
            Capsule::schema()->create(
                'mod_fmh_monitor_logs',
                function ($table) {
                    $table->increments('id');
                    $table->integer('service_id');
                    $table->string('event_type', 50);
                    $table->text('details')->nullable();
                    $table->dateTime('created_at');
                }
            );
        }

        if (!Capsule::schema()->hasTable('mod_fmh_monitor_settings')) {
            Capsule::schema()->create(
                'mod_fmh_monitor_settings',
                function ($table) {
                    $table->string('setting_key', 100)->unique();
                    $table->text('setting_value')->nullable();
                }
            );
            
            // Inserir configurações padrão
            $defaultSettings = [
                ['setting_key' => 'enable_whatsapp', 'setting_value' => '1'],
                ['setting_key' => 'enable_telegram', 'setting_value' => '1'],
                ['setting_key' => 'enable_email', 'setting_value' => '1'],
                ['setting_key' => 'wa_api_url', 'setting_value' => ''],
                ['setting_key' => 'wa_api_token', 'setting_value' => ''],
                ['setting_key' => 'wa_numbers', 'setting_value' => ''],
                ['setting_key' => 'notify_email', 'setting_value' => ''],
                ['setting_key' => 'telegram_token', 'setting_value' => ''],
                ['setting_key' => 'telegram_chat_id', 'setting_value' => ''],
                ['setting_key' => 'timeout', 'setting_value' => '5'],
                ['setting_key' => 'ssh_public_key', 'setting_value' => ''],
                ['setting_key' => 'template_up', 'setting_value' => "✅ *SERVIÇO RESTABELECIDO* ✅\n\nServiço: *{nome_servico}*\nEndereço: {ip}:{porta}\nStatus: ONLINE 🟢\nData/Hora: {hora}"],
                ['setting_key' => 'template_down', 'setting_value' => "⚠️ *ALERTA DE QUEDA* ⚠️\n\nServiço: *{nome_servico}*\nEndereço: {ip}:{porta}\nStatus: OFFLINE 🔴\nData/Hora: {hora}"],
                ['setting_key' => 'template_alert_cpu', 'setting_value' => "⚠️ *ALERTA DE CARGA* ⚠️\nServidor: {nome_servico}\nCPU Load (1m): {valor}\nStatus: {status}\nData/Hora: {hora}"],
                ['setting_key' => 'template_alert_ram', 'setting_value' => "⚠️ *ALERTA DE MEMÓRIA* ⚠️\nServidor: {nome_servico}\nUso de RAM: {valor}%\nStatus: {status}\nData/Hora: {hora}"],
                ['setting_key' => 'template_alert_disk', 'setting_value' => "⚠️ *ALERTA DE DISCO/INODES* ⚠️\nServidor: {nome_servico}\nUso: {valor}%\nStatus: {status}\nData/Hora: {hora}"],
                ['setting_key' => 'template_alert_spam', 'setting_value' => "⚠️ *ALERTA DE SPAM* ⚠️\nServidor: {nome_servico}\nFila Total: {valor}\n\nTop Remetentes:\n{top_senders}"],
                ['setting_key' => 'template_alert_remediation', 'setting_value' => "🪄 *AUTO-CURA EXECUTADA* 🪄\nServidor: {nome_servico}\nAção: {acao}\nDetalhe: {detalhe}\nStatus: RESOLVIDO ✅"],
                ['setting_key' => 'template_alert_ddos', 'setting_value' => "🚨 *ALERTA DE REDE / POSSÍVEL DDOS* 🚨\nServidor: {nome_servico}\nConexões TCP Abertas: {valor}\nStatus: {status}\nData/Hora: {hora}"],
                ['setting_key' => 'template_alert_ssh', 'setting_value' => "⚠️ *NOVO LOGIN SSH DETECTADO* ⚠️\n\nServidor: {nome_servico}\nUsuário: {usuario}\nIP: {ip}\nLocalização: {localidade}\nData/Hora: {hora}"],
                ['setting_key' => 'template_alert_pwd', 'setting_value' => "⚠️ *ALERTA CRÍTICO DE SEGURANÇA* ⚠️\n\nServidor: {nome_servico}\nA senha do usuário root foi alterada!\nIP de origem (estimado): {ip}\nLocalização: {geolocation}\nData/Hora: {hora}"],
                ['setting_key' => 'template_alert_ssh_key', 'setting_value' => "🔑 *NOVA CHAVE SSH ADICIONADA* 🔑\n\nServidor: {nome_servico}\nUma nova chave pública foi inserida em /root/.ssh/authorized_keys.\n\nIdentificação: {key_comment}\nIP de origem (estimado): {ip}\nLocalização: {geolocation}\nData/Hora: {hora}"],
                ['setting_key' => 'template_alert_bruteforce', 'setting_value' => "🚨 *ALERTA DE BRUTE-FORCE SSH* 🚨\n\nServidor: {nome_servico}\nIP Bloqueado: {ip}\nMotivo: {detalhe}\nData/Hora: {hora}"],
                ['setting_key' => 'template_alert_ssl', 'setting_value' => "🔒 *ALERTA DE CERTIFICADO SSL* 🔒\n\nServidor: {nome_servico}\nDominio: {dominio}\nDias Restantes: {dias}\nSeveridade: {severidade}\nData/Hora: {hora}"]
            ];
            Capsule::table('mod_fmh_monitor_settings')->insert($defaultSettings);
        }

        if (!Capsule::schema()->hasTable('mod_fmh_monitor_webhooks')) {
            Capsule::schema()->create(
                'mod_fmh_monitor_webhooks',
                function ($table) {
                    $table->increments('id');
                    $table->string('name', 255);
                    $table->string('url', 255);
                    $table->string('method', 10)->default('POST');
                    $table->text('headers')->nullable();
                    $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE');
                }
            );
        }

        if (!Capsule::schema()->hasTable('mod_fmh_monitor_servers')) {
            Capsule::schema()->create('mod_fmh_monitor_servers', function ($table) {
                $table->increments('id');
                $table->string('name', 255);
                $table->string('token', 64)->unique();
                $table->string('environment', 50)->default('linux');
                $table->boolean('auto_remediation')->default(0);
                $table->string('ip', 100)->nullable();
                $table->string('os', 100)->nullable();
                $table->enum('status', ['UP', 'DOWN', 'PAUSED'])->default('DOWN');
                $table->string('channels', 255)->default('whatsapp,telegram,email,webhooks');
                $table->dateTime('created_at')->nullable();
                $table->dateTime('last_check')->nullable();
            $table->text('ssh_keys')->nullable();
            $table->dateTime('last_pwd_alert')->nullable();
                $table->string('hmac_secret', 128)->nullable();
                $table->boolean('hmac_enforced')->default(0);
                $table->string('expected_ip', 255)->nullable();
                $table->string('last_ip', 100)->nullable();
                $table->string('ssh_user', 50)->nullable();
                $table->string('ssh_key', 255)->nullable();
                $table->text('ssh_pubkey')->nullable();
            });
        }

        if (!Capsule::schema()->hasTable('mod_fmh_monitor_server_metrics')) {
            Capsule::schema()->create('mod_fmh_monitor_server_metrics', function ($table) {
                $table->increments('id');
                $table->integer('server_id');
                $table->dateTime('created_at');
                $table->float('load_1')->nullable();
                $table->float('load_5')->nullable();
                $table->float('load_15')->nullable();
                $table->integer('mem_total')->nullable();
                $table->integer('mem_used')->nullable();
                $table->string('disk_total', 50)->nullable();
                $table->integer('disk_used_pct')->nullable();
                $table->integer('inodes_used_pct')->nullable();
                $table->integer('net_connections')->nullable();
                $table->integer('mail_queue')->nullable();
                $table->text('mail_top_senders')->nullable();
                $table->text('services_status')->nullable();
            });
        }

        if (!Capsule::schema()->hasTable('mod_fmh_monitor_server_thresholds')) {
            Capsule::schema()->create('mod_fmh_monitor_server_thresholds', function ($table) {
                $table->increments('id');
                $table->integer('server_id');
                $table->string('metric', 50); // e.g. load_1, mem_used_pct, disk_used_pct, mail_queue
                $table->string('condition', 10); // e.g. >, <, ==
                $table->float('value');
                $table->string('alert_channels', 255)->nullable();
                $table->dateTime('last_alert')->nullable();
            });
        }

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

        if (!Capsule::schema()->hasTable('mod_fmh_monitor_file_integrity')) {
            Capsule::schema()->create('mod_fmh_monitor_file_integrity', function ($table) {
                $table->increments('id');
                $table->integer('server_id');
                $table->string('file_path', 500);
                $table->string('checksum', 128);
                $table->string('status', 20)->default('OK');
                $table->dateTime('last_checked');
                $table->dateTime('created_at');
            });
        }

        if (!Capsule::schema()->hasTable('mod_fmh_monitor_api_nonces')) {
            Capsule::schema()->create('mod_fmh_monitor_api_nonces', function ($table) {
                $table->string('nonce', 128)->unique();
                $table->integer('server_id');
                $table->dateTime('created_at');
            });
        }

        return [
            'status' => 'success',
            'description' => 'Módulo ativado com sucesso! Tabelas de banco de dados criadas.',
        ];
    } catch (\Exception $e) {
        return [
            'status' => 'error',
            'description' => 'Não foi possível ativar o módulo: ' . $e->getMessage(),
        ];
    }
}

function fmh_monitor_deactivate()
{
    return [
        'status' => 'success',
        'description' => 'Módulo desativado com sucesso. As tabelas do banco de dados foram mantidas por segurança.',
    ];
}

function fmh_monitor_output($vars)
{
    // Auto-Upgrade / Table check para caso o usuário atualize o módulo sem Desativar e Ativar
    if (!Capsule::schema()->hasTable('mod_fmh_monitor_settings') || !Capsule::schema()->hasTable('mod_fmh_monitor_servers')) {
        fmh_monitor_activate();
    }

    // Cleanup: Remove old FAILED SSH logins from the database to clear the UI
    try {
        if (Capsule::schema()->hasTable('mod_fmh_monitor_ssh_logins')) {
            Capsule::table('mod_fmh_monitor_ssh_logins')->where('status', 'FAILED')->delete();
        }
        // Auto-repair empty channels that were wiped by previous bug
        Capsule::table('mod_fmh_monitor_servers')->where('channels', '')->update(['channels' => 'whatsapp,telegram,email,webhooks']);
        Capsule::table('mod_fmh_monitor_services')->where('channels', '')->update(['channels' => 'whatsapp,telegram,email,webhooks']);

        // Cleanup expired IP blacklist entries
        if (Capsule::schema()->hasTable('mod_fmh_monitor_ip_blacklist')) {
            Capsule::table('mod_fmh_monitor_ip_blacklist')->where('blocked_until', '<', date('Y-m-d H:i:s'))->delete();
        }

        // Cleanup old nonces
        if (Capsule::schema()->hasTable('mod_fmh_monitor_api_nonces')) {
            Capsule::table('mod_fmh_monitor_api_nonces')
                ->where('created_at', '<', date('Y-m-d H:i:s', strtotime('-10 minutes')))
                ->delete();
        }
    } catch (\Exception $e) {
        // Silent catch
    }
    
    // Add created_at if missing
    if (!Capsule::schema()->hasColumn('mod_fmh_monitor_servers', 'created_at')) {
        Capsule::schema()->table('mod_fmh_monitor_servers', function($table) {
            $table->dateTime('created_at')->nullable();
        });
    }

    if (!Capsule::schema()->hasColumn('mod_fmh_monitor_servers', 'ssh_keys')) {
        Capsule::schema()->table('mod_fmh_monitor_servers', function($table) {
            $table->text('ssh_keys')->nullable();
            $table->dateTime('last_pwd_alert')->nullable();
        });
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
    if (!Capsule::schema()->hasColumn('mod_fmh_monitor_server_thresholds', 'last_alert')) {
        Capsule::schema()->table('mod_fmh_monitor_server_thresholds', function($table) {
            $table->dateTime('last_alert')->nullable();
        });
    }
    if (!Capsule::schema()->hasColumn('mod_fmh_monitor_servers', 'environment')) {
        Capsule::schema()->table('mod_fmh_monitor_servers', function($table) {
            $table->string('environment', 50)->default('linux');
            $table->boolean('auto_remediation')->default(0);
        });
    }
    if (!Capsule::schema()->hasColumn('mod_fmh_monitor_server_metrics', 'cpu_usage')) {
        Capsule::schema()->table('mod_fmh_monitor_server_metrics', function($table) {
            $table->float('cpu_usage')->nullable();
        });
    }

    if (!Capsule::schema()->hasColumn('mod_fmh_monitor_server_metrics', 'inodes_used_pct')) {
        Capsule::schema()->table('mod_fmh_monitor_server_metrics', function($table) {
            $table->integer('inodes_used_pct')->nullable();
            $table->integer('net_connections')->nullable();
            $table->text('mail_top_senders')->nullable();
        });
    }
    
    if (!Capsule::schema()->hasColumn('mod_fmh_monitor_server_metrics', 'os_uptime')) {
        Capsule::schema()->table('mod_fmh_monitor_server_metrics', function($table) {
            $table->string('os_uptime', 255)->nullable();
            $table->text('top_processes')->nullable();
        });
    }
    
    // Check if new templates exist
    $hasTemplates = Capsule::table('mod_fmh_monitor_settings')->where('setting_key', 'template_alert_cpu')->count();
    if ($hasTemplates == 0) {
        Capsule::table('mod_fmh_monitor_settings')->insert([
            ['setting_key' => 'template_alert_cpu', 'setting_value' => "⚠️ *ALERTA DE CARGA* ⚠️\nServidor: {nome_servico}\nCPU Load (1m): {valor}\nStatus: {status}\nData/Hora: {hora}\n\n🛠️ *Top Processos Causadores:*\n{top_processes}"],
            ['setting_key' => 'template_alert_ram', 'setting_value' => "⚠️ *ALERTA DE MEMÓRIA* ⚠️\nServidor: {nome_servico}\nUso de RAM: {valor}%\nStatus: {status}\nData/Hora: {hora}\n\n🛠️ *Top Processos Causadores:*\n{top_processes}"],
            ['setting_key' => 'template_alert_disk', 'setting_value' => "⚠️ *ALERTA DE DISCO/INODES* ⚠️\nServidor: {nome_servico}\nUso: {valor}%\nStatus: {status}\nData/Hora: {hora}"],
            ['setting_key' => 'template_alert_spam', 'setting_value' => "⚠️ *ALERTA DE SPAM* ⚠️\nServidor: {nome_servico}\nFila Total: {valor}\n\nTop Remetentes:\n{top_senders}"],
            ['setting_key' => 'template_alert_remediation', 'setting_value' => "🪄 *AUTO-CURA EXECUTADA* 🪄\nServidor: {nome_servico}\nAção: {acao}\nDetalhe: {detalhe}\nStatus: RESOLVIDO ✅"],
            ['setting_key' => 'template_alert_ddos', 'setting_value' => "🚨 *ALERTA DE REDE / POSSÍVEL DDOS* 🚨\nServidor: {nome_servico}\nConexões TCP Abertas: {valor}\nStatus: {status}\nData/Hora: {hora}"]
        ]);
    } else {
        // Auto-patch existing templates to include top_processes
        $cpu = Capsule::table('mod_fmh_monitor_settings')->where('setting_key', 'template_alert_cpu')->first();
        if ($cpu && strpos($cpu->setting_value, '{top_processes}') === false) {
            $val = trim($cpu->setting_value);
            if (empty($val)) {
$newVal = "⚠️ *ALERTA DE CARGA* ⚠️\nServidor: {nome_servico}\nCPU Load (1m): {valor}\nStatus: {status}\nData/Hora: {hora}\n\n🛠️ *Top Processos Causadores:*\n{top_processes}";
            } else {
                $newVal = $cpu->setting_value . "\n\n🛠️ *Top Processos Causadores:*\n{top_processes}";
            }
            Capsule::table('mod_fmh_monitor_settings')->where('setting_key', 'template_alert_cpu')->update(['setting_value' => $newVal]);
        } elseif ($cpu && strlen(trim($cpu->setting_value)) < 40) {
            // Restore default if it was just the appended string
$newVal = "⚠️ *ALERTA DE CARGA* ⚠️\nServidor: {nome_servico}\nCPU Load (1m): {valor}\nStatus: {status}\nData/Hora: {hora}\n\n🛠️ *Top Processos Causadores:*\n{top_processes}";
            Capsule::table('mod_fmh_monitor_settings')->where('setting_key', 'template_alert_cpu')->update(['setting_value' => $newVal]);
        }
        
        $ram = Capsule::table('mod_fmh_monitor_settings')->where('setting_key', 'template_alert_ram')->first();
        if ($ram && strpos($ram->setting_value, '{top_processes}') === false) {
            $val = trim($ram->setting_value);
            if (empty($val)) {
$newVal = "⚠️ *ALERTA DE MEMÓRIA* ⚠️\nServidor: {nome_servico}\nUso de RAM: {valor}%\nStatus: {status}\nData/Hora: {hora}\n\n🛠️ *Top Processos Causadores:*\n{top_processes}";
            } else {
                $newVal = $ram->setting_value . "\n\n🛠️ *Top Processos Causadores:*\n{top_processes}";
            }
            Capsule::table('mod_fmh_monitor_settings')->where('setting_key', 'template_alert_ram')->update(['setting_value' => $newVal]);
        } elseif ($ram && strlen(trim($ram->setting_value)) < 40) {
$newVal = "⚠️ *ALERTA DE MEMÓRIA* ⚠️\nServidor: {nome_servico}\nUso de RAM: {valor}%\nStatus: {status}\nData/Hora: {hora}\n\n🛠️ *Top Processos Causadores:*\n{top_processes}";
            Capsule::table('mod_fmh_monitor_settings')->where('setting_key', 'template_alert_ram')->update(['setting_value' => $newVal]);
        }
        $ddos = Capsule::table('mod_fmh_monitor_settings')->where('setting_key', 'template_alert_ddos')->first();
        if (!$ddos) {
            Capsule::table('mod_fmh_monitor_settings')->insert([
                ['setting_key' => 'template_alert_ddos', 'setting_value' => "🚨 *ALERTA DE REDE / POSSÍVEL DDOS* 🚨\nServidor: {nome_servico}\nConexões TCP Abertas: {valor}\nStatus: {status}\nData/Hora: {hora}"]
            ]);
        }
    }

    $hasBruteForceTpl = Capsule::table('mod_fmh_monitor_settings')->where('setting_key', 'template_alert_bruteforce')->count();
    if ($hasBruteForceTpl == 0) {
        Capsule::table('mod_fmh_monitor_settings')->insert([
            ['setting_key' => 'template_alert_bruteforce', 'setting_value' => "🚨 *ALERTA DE BRUTE-FORCE SSH* 🚨\nServidor: {nome_servico}\nIP Bloqueado: {ip}\nMotivo: {detalhe}\nData/Hora: {hora}"],
            ['setting_key' => 'template_alert_ssl', 'setting_value' => "🔒 *ALERTA DE CERTIFICADO SSL* 🔒\nServidor: {nome_servico}\nDominio: {dominio}\nDias Restantes: {dias}\nSeveridade: {severidade}\nData/Hora: {hora}"]
        ]);
    }

    if (!Capsule::schema()->hasColumn('mod_fmh_monitor_logs', 'server_id')) {
        Capsule::schema()->table('mod_fmh_monitor_logs', function($table) {
            $table->integer('server_id')->nullable();
        });
        // Permitir service_id ser nulo também, pois agora o log pode ser de um servidor
        Capsule::schema()->table('mod_fmh_monitor_logs', function($table) {
            // Em SQLite o change pode dar problema, então deixamos para inserir 0 se for obrigatorio.
        });
    }

    if (!Capsule::schema()->hasColumn('mod_fmh_monitor_servers', 'agent_version')) {
        Capsule::schema()->table('mod_fmh_monitor_servers', function($table) {
            $table->string('agent_version', 20)->default('1.0');
        });
    }

    if (!Capsule::schema()->hasColumn('mod_fmh_monitor_servers', 'hostname')) {
        Capsule::schema()->table('mod_fmh_monitor_servers', function($table) {
            $table->string('hostname', 255)->nullable();
            $table->integer('ssh_port')->default(22);
        });
    }

    if (!Capsule::schema()->hasColumn('mod_fmh_monitor_servers', 'machine_id')) {
        Capsule::schema()->table('mod_fmh_monitor_servers', function($table) {
            $table->string('machine_id', 100)->nullable();
        });
    }

    if (!Capsule::schema()->hasColumn('mod_fmh_monitor_servers', 'ssh_whitelist_ips')) {
        Capsule::schema()->table('mod_fmh_monitor_servers', function($table) {
            $table->text('ssh_whitelist_ips')->nullable();
        });
    }

    if (!Capsule::schema()->hasColumn('mod_fmh_monitor_servers', 'ipv6')) {
        Capsule::schema()->table('mod_fmh_monitor_servers', function($table) {
            $table->string('ipv6', 100)->nullable();
        });
    }
    if (!Capsule::schema()->hasColumn('mod_fmh_monitor_servers', 'client_id')) {
        Capsule::schema()->table('mod_fmh_monitor_servers', function($table) {
            $table->integer('client_id')->default(0);
            $table->boolean('notify_client')->default(0);
        });
    }

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
    } elseif (!Capsule::schema()->hasColumn('mod_fmh_monitor_server_metrics', 'root_audit')) {
        Capsule::schema()->table('mod_fmh_monitor_server_metrics', function($table) {
            $table->text('root_audit')->nullable();
        });
    }

    // Comprehensive column check: verify ALL critical columns exist
    $criticalCols = [
        'mod_fmh_monitor_servers' => ['hmac_secret', 'hmac_enforced', 'expected_ip', 'last_ip', 'ssh_user', 'ssh_key', 'ssh_pubkey', 'machine_id', 'agent_version', 'hostname', 'ssh_port', 'ssh_whitelist_ips'],
        'mod_fmh_monitor_server_metrics' => ['ssl_certs', 'docker_containers', 'db_health', 'kernel_vulns', 'backup_status', 'file_integrity', 'root_audit', 'net_bandwidth_rx', 'net_bandwidth_tx', 'net_speed_rx', 'net_speed_tx', 'health_score', 'mail_top_senders', 'os_uptime', 'top_processes']
    ];
    foreach ($criticalCols as $table => $cols) {
        foreach ($cols as $col) {
            try {
                if (!Capsule::schema()->hasColumn($table, $col)) {
                    Capsule::schema()->table($table, function($t) use ($col) {
                        if ($col === 'net_bandwidth_rx' || $col === 'net_bandwidth_tx') $t->bigInteger($col)->nullable();
                        elseif ($col === 'health_score') $t->integer($col)->nullable();
                        elseif ($col === 'ssh_port') $t->integer($col)->default(22);
                        elseif ($col === 'hmac_enforced' || $col === 'auto_remediation' || $col === 'notify_client') $t->boolean($col)->default(0);
                        elseif (in_array($col, ['agent_version'])) $t->string($col, 20)->default('1.0');
                        elseif (in_array($col, ['machine_id', 'hostname', 'expected_ip', 'last_ip', 'ssh_user'])) $t->string($col, 255)->nullable();
                        elseif (in_array($col, ['ssh_key', 'hmac_secret'])) $t->string($col, 128)->nullable();
                        else $t->text($col)->nullable();
                    });
                }
            } catch (\Exception $e) {}
        }
    }

    if (!Capsule::schema()->hasTable('mod_fmh_monitor_audit_commands')) {
        Capsule::schema()->create('mod_fmh_monitor_audit_commands', function ($table) {
            $table->increments('id');
            $table->integer('server_id');
            $table->string('user', 100);
            $table->string('ip', 100);
            $table->string('geolocation', 255)->nullable();
            $table->text('command');
            $table->timestamp('created_at')->default(Capsule::raw('CURRENT_TIMESTAMP'));
        });
    } else {
        if (!Capsule::schema()->hasColumn('mod_fmh_monitor_audit_commands', 'geolocation')) {
            Capsule::schema()->table('mod_fmh_monitor_audit_commands', function($table) {
                $table->string('geolocation', 255)->nullable();
            });
        }
    }

    if (!Capsule::schema()->hasTable('mod_fmh_monitor_ssh_logins')) {
        Capsule::schema()->create('mod_fmh_monitor_ssh_logins', function ($table) {
            $table->increments('id');
            $table->integer('server_id');
            $table->string('user', 50);
            $table->string('ip', 100);
            $table->string('geolocation', 255)->nullable();
            $table->string('status', 20)->default('SUCCESS');
            $table->dateTime('created_at');
        });
    }

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

    if (!Capsule::schema()->hasTable('mod_fmh_monitor_file_integrity')) {
        Capsule::schema()->create('mod_fmh_monitor_file_integrity', function ($table) {
            $table->increments('id');
            $table->integer('server_id');
            $table->string('file_path', 500);
            $table->string('checksum', 128);
            $table->string('status', 20)->default('OK');
            $table->dateTime('last_checked');
            $table->dateTime('created_at');
        });
    }

    if (!Capsule::schema()->hasColumn('mod_fmh_monitor_services', 'type')) {
        Capsule::schema()->table('mod_fmh_monitor_services', function($table) {
            $table->enum('type', ['PORT', 'PING', 'HTTP'])->default('PORT');
            // Como port não era nullable, vamos dar um change, porem em sqlite/mysql antigo pode falhar, 
            // então não vamos forçar alteração estrutural pesada, apenas permitiremos 0 no codigo.
        });
    }
    if (!Capsule::schema()->hasColumn('mod_fmh_monitor_services', 'interval_minutes')) {
        Capsule::schema()->table('mod_fmh_monitor_services', function($table) {
            $table->integer('interval_minutes')->default(1);
        });
    }

    if (!Capsule::schema()->hasColumn('mod_fmh_monitor_servers', 'hmac_secret')) {
        Capsule::schema()->table('mod_fmh_monitor_servers', function($table) {
            $table->string('hmac_secret', 128)->nullable();
            $table->boolean('hmac_enforced')->default(0);
            $table->string('expected_ip', 255)->nullable();
            $table->string('last_ip', 100)->nullable();
            $table->string('ssh_user', 50)->nullable();
            $table->string('ssh_key', 255)->nullable();
            $table->text('ssh_pubkey')->nullable();
        });
    }

    $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
    $tab = isset($_REQUEST['tab']) ? $_REQUEST['tab'] : 'dashboard';
    
    // Proteção CSRF para todas as ações que modificam estado
    $stateChangingActions = ['add_service', 'edit_service', 'delete_service', 'toggle_pause', 'save_settings', 'add_webhook', 'delete_webhook', 'add_server', 'delete_server', 'add_server_threshold', 'delete_server_threshold', 'edit_server_agent', 'rotate_token'];
    if (in_array($action, $stateChangingActions)) {
        check_token("WHMCS.admin.default");
    }
    
    // Tratamento de ações do painel
    if ($action == 'add_service') {
        $name = $_POST['name'];
        $ip = $_POST['ip'];
        $type = isset($_POST['type']) ? $_POST['type'] : 'PORT';
        $port = (int) $_POST['port']; // Pode ser 0 para PING
        $interval_minutes = isset($_POST['interval_minutes']) ? (int) $_POST['interval_minutes'] : 1;
        $client_id = isset($_POST['client_id']) ? (int) $_POST['client_id'] : 0;
        $notify_client = isset($_POST['notify_client']) ? 1 : 0;
        $channels = isset($_POST['channels']) && is_array($_POST['channels']) ? implode(',', $_POST['channels']) : 'whatsapp,telegram,email,webhooks';
        
        if (!empty($name) && !empty($ip)) {
            Capsule::table('mod_fmh_monitor_services')->insert([
                'name' => $name,
                'ip' => $ip,
                'port' => $port,
                'type' => $type,
                'interval_minutes' => $interval_minutes,
                'client_id' => $client_id,
                'notify_client' => $notify_client,
                'status' => 'UP',
                'channels' => $channels
            ]);
            header("Location: addonmodules.php?module=fmh_monitor&tab=dashboard&success=added");
            exit;
        }
    } elseif ($action == 'edit_service') {
        $id = (int) $_POST['id'];
        $name = $_POST['name'];
        $ip = $_POST['ip'];
        $type = isset($_POST['type']) ? $_POST['type'] : 'PORT';
        $port = (int) $_POST['port']; // Pode ser 0 para PING
        $interval_minutes = isset($_POST['interval_minutes']) ? (int) $_POST['interval_minutes'] : 1;
        $client_id = isset($_POST['client_id']) ? (int) $_POST['client_id'] : 0;
        $notify_client = isset($_POST['notify_client']) ? 1 : 0;
        $channels = isset($_POST['channels']) && is_array($_POST['channels']) ? implode(',', $_POST['channels']) : '';

        if (!empty($name) && !empty($ip) && $id > 0) {
            Capsule::table('mod_fmh_monitor_services')->where('id', $id)->update([
                'name' => $name,
                'ip' => $ip,
                'port' => $port,
                'type' => $type,
                'interval_minutes' => $interval_minutes,
                'client_id' => $client_id,
                'notify_client' => $notify_client,
                'channels' => $channels
            ]);
            header("Location: addonmodules.php?module=fmh_monitor&tab=dashboard&success=updated");
            exit;
        }
    } elseif ($action == 'delete_service') {
        $id = (int) $_GET['id'];
        Capsule::table('mod_fmh_monitor_services')->where('id', $id)->delete();
        Capsule::table('mod_fmh_monitor_logs')->where('service_id', $id)->delete();
        header("Location: addonmodules.php?module=fmh_monitor&tab=dashboard&success=deleted");
        exit;
    } elseif ($action == 'toggle_pause') {
        $id = (int) $_GET['id'];
        $service = Capsule::table('mod_fmh_monitor_services')->where('id', $id)->first();
        if ($service) {
            $newStatus = $service->status == 'PAUSED' ? 'UP' : 'PAUSED';
            Capsule::table('mod_fmh_monitor_services')->where('id', $id)->update(['status' => $newStatus]);
        }
        header("Location: addonmodules.php?module=fmh_monitor&tab=dashboard&success=updated");
        exit;
    } elseif ($action == 'save_settings') {
        $settingsKeys = ['enable_whatsapp', 'enable_telegram', 'enable_email', 'wa_api_url', 'wa_api_token', 'wa_numbers', 'notify_email', 'telegram_token', 'telegram_chat_id', 'timeout', 'ssh_public_key', 'template_up', 'template_down', 'template_alert_cpu', 'template_alert_ram', 'template_alert_disk', 'template_alert_spam', 'template_alert_remediation', 'template_alert_ddos', 'template_alert_ssh', 'template_alert_pwd', 'template_alert_ssh_key', 'template_alert_bruteforce', 'template_alert_ssl'];
        foreach ($settingsKeys as $key) {
            if (isset($_POST[$key])) {
                $val = $_POST[$key];
                $exists = Capsule::table('mod_fmh_monitor_settings')->where('setting_key', $key)->first();
                if ($exists) {
                    Capsule::table('mod_fmh_monitor_settings')->where('setting_key', $key)->update(['setting_value' => $val]);
                } else {
                    Capsule::table('mod_fmh_monitor_settings')->insert(['setting_key' => $key, 'setting_value' => $val]);
                }
            }
        }
        header("Location: addonmodules.php?module=fmh_monitor&tab={$_POST['current_tab']}&success=settings_saved");
        exit;
    } elseif ($action == 'add_webhook') {
        Capsule::table('mod_fmh_monitor_webhooks')->insert([
            'name' => $_POST['wh_name'],
            'url' => $_POST['wh_url'],
            'method' => $_POST['wh_method'],
            'headers' => $_POST['wh_headers'],
            'status' => 'ACTIVE'
        ]);
        header("Location: addonmodules.php?module=fmh_monitor&tab=webhooks&success=webhook_added");
        exit;
    } elseif ($action == 'delete_webhook') {
        $id = (int) $_GET['id'];
        Capsule::table('mod_fmh_monitor_webhooks')->where('id', $id)->delete();
        header("Location: addonmodules.php?module=fmh_monitor&tab=webhooks&success=webhook_deleted");
        exit;
    } elseif ($action == 'search_clients') {
        $term = isset($_GET['q']) ? trim($_GET['q']) : '';
        $results = [];
        if (strlen($term) >= 2) {
            $clients = Capsule::table('tblclients')
                ->where('firstname', 'like', "%{$term}%")
                ->orWhere('lastname', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('companyname', 'like', "%{$term}%")
                ->select('id', 'firstname', 'lastname', 'email', 'companyname')
                ->limit(20)
                ->get();
                
            foreach ($clients as $client) {
                $name = $client->firstname . ' ' . $client->lastname;
                if (!empty($client->companyname)) {
                    $name .= ' (' . $client->companyname . ')';
                }
                $results[] = [
                    'id' => $client->id,
                    'text' => "#{$client->id} - {$name} - {$client->email}"
                ];
            }
        }
        header('Content-Type: application/json');
        echo json_encode(['results' => $results]);
        exit;
    } elseif ($action == 'add_server') {
        $name = $_POST['name'];
        $environment = $_POST['environment'];
        $auto_remediation = isset($_POST['auto_remediation']) ? 1 : 0;
        $client_id = isset($_POST['client_id']) ? (int) $_POST['client_id'] : 0;
        $notify_client = isset($_POST['notify_client']) ? 1 : 0;
        $channels = isset($_POST['channels']) && is_array($_POST['channels']) ? implode(',', $_POST['channels']) : 'whatsapp,telegram,email,webhooks';
        
        if (!empty($name)) {
            $token = bin2hex(random_bytes(32));
            $hmac_secret = bin2hex(random_bytes(32));
            Capsule::table('mod_fmh_monitor_servers')->insert([
                'name' => $name,
                'token' => $token,
                'hmac_secret' => $hmac_secret,
                'environment' => $environment,
                'auto_remediation' => $auto_remediation,
                'client_id' => $client_id,
                'notify_client' => $notify_client,
                'channels' => $channels,
                'status' => 'UP',
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }
        header("Location: addonmodules.php?module=fmh_monitor&tab=servers&success=server_added");
        exit;
    } elseif ($action == 'edit_server_agent') {
        $server_id = (int) $_POST['server_id'];
        $environment = trim($_POST['environment']);
        $auto_remediation = isset($_POST['auto_remediation']) ? 1 : 0;
        $client_id = isset($_POST['client_id']) ? (int) $_POST['client_id'] : 0;
        $notify_client = isset($_POST['notify_client']) ? 1 : 0;
        $ssh_whitelist_ips = trim($_POST['ssh_whitelist_ips']);
        
        // Preserve existing channels if not submitted, or allow empty if explicitly provided via a form with channels
        if (isset($_POST['channels'])) {
            $channels = is_array($_POST['channels']) ? implode(',', $_POST['channels']) : '';
            Capsule::table('mod_fmh_monitor_servers')->where('id', $server_id)->update([
                'environment' => $environment,
                'auto_remediation' => $auto_remediation,
                'client_id' => $client_id,
                'notify_client' => $notify_client,
                'channels' => $channels,
                'ssh_whitelist_ips' => $ssh_whitelist_ips
            ]);
        } else {
            Capsule::table('mod_fmh_monitor_servers')->where('id', $server_id)->update([
                'environment' => $environment,
                'auto_remediation' => $auto_remediation,
                'client_id' => $client_id,
                'notify_client' => $notify_client,
                'ssh_whitelist_ips' => $ssh_whitelist_ips
            ]);
        }
        
        // Cleanup old SSH logs for these IPs retroactively
        if (!empty($ssh_whitelist_ips)) {
            $ips = array_map('trim', explode(',', $ssh_whitelist_ips));
            if (!empty($ips)) {
                Capsule::table('mod_fmh_monitor_ssh_logins')
                    ->where('server_id', $server_id)
                    ->whereIn('ip', $ips)
                    ->delete();
            }
        }
        
        header("Location: addonmodules.php?module=fmh_monitor&action=view_server&id={$server_id}&success=agent_updated");
        exit;
    } elseif ($action == 'reset_machine_id') {
        $server_id = (int) $_GET['id'];
        Capsule::table('mod_fmh_monitor_servers')->where('id', $server_id)->update(['machine_id' => null]);
        header("Location: addonmodules.php?module=fmh_monitor&action=view_server&id={$server_id}&success=machine_reset");
        exit;
    } elseif ($action == 'delete_server') {
        $id = (int) $_GET['id'];
        Capsule::table('mod_fmh_monitor_servers')->where('id', $id)->delete();
        Capsule::table('mod_fmh_monitor_server_metrics')->where('server_id', $id)->delete();
        Capsule::table('mod_fmh_monitor_server_thresholds')->where('server_id', $id)->delete();
        header("Location: addonmodules.php?module=fmh_monitor&tab=servers&success=server_deleted");
        exit;
    } elseif ($action == 'add_server_threshold') {
        $server_id = (int) $_POST['server_id'];
        $metric = $_POST['metric'];
        $condition = $_POST['condition'];
        $value = (float) $_POST['value'];
        $alert_channels = isset($_POST['alert_channels']) && is_array($_POST['alert_channels']) ? implode(',', $_POST['alert_channels']) : 'whatsapp,telegram,email';
        
        if ($server_id && $metric && $condition && $value !== '') {
            Capsule::table('mod_fmh_monitor_server_thresholds')->insert([
                'server_id' => $server_id,
                'metric' => $metric,
                'condition' => $condition,
                'value' => $value,
                'alert_channels' => $alert_channels
            ]);
        }
        header("Location: addonmodules.php?module=fmh_monitor&action=view_server&id={$server_id}&success=threshold_added");
        exit;
    } elseif ($action == 'delete_server_threshold') {
        $id = (int) $_GET['id'];
        $server_id = (int) $_GET['server_id'];
        Capsule::table('mod_fmh_monitor_server_thresholds')->where('id', $id)->delete();
        header("Location: addonmodules.php?module=fmh_monitor&action=view_server&id={$server_id}&success=threshold_deleted");
        exit;
    } elseif ($action == 'rotate_token') {
        $server_id = (int) $_POST['server_id'];
        $server = Capsule::table('mod_fmh_monitor_servers')->where('id', $server_id)->first();
        if ($server) {
            $newToken = bin2hex(random_bytes(32));
            $newSecret = bin2hex(random_bytes(32));
            Capsule::table('mod_fmh_monitor_servers')->where('id', $server_id)->update([
                'token' => $newToken,
                'hmac_secret' => $newSecret,
                'machine_id' => null
            ]);
            // Old agent will fail auth, triggering reinstall with new token
            header("Location: addonmodules.php?module=fmh_monitor&action=view_server&id={$server_id}&success=token_rotated");
            exit;
        }
    } elseif ($action == 'generate_recovery_script') {
        // Generate a recovery script for all OFFLINE servers
        $offlineServers = Capsule::table('mod_fmh_monitor_servers')
            ->where('status', 'DOWN')
            ->orWhere('last_check', '<', date('Y-m-d H:i:s', strtotime('-5 minutes')))
            ->get();
        
        $whmcsUrl = rtrim(Capsule::table('tblconfiguration')->where('setting', 'SystemURL')->value('value'), '/');
        header('Content-Type: text/plain; charset=utf-8');
        header('Content-Disposition: attachment; filename="fmh_recovery_' . date('Ymd_His') . '.sh"');
        echo "#!/bin/bash\n";
        echo "# FMH Monitor - Recovery Script\n";
        echo "# Generated: " . date('Y-m-d H:i:s') . "\n";
        echo "# Servers to recover: " . count($offlineServers) . "\n\n";
        
        $count = 0;
        foreach ($offlineServers as $srv) {
            $count++;
            $hmac = $srv->hmac_secret ?? '';
            $env = $srv->environment ?? 'linux';
            $autoRem = $srv->auto_remediation ?? 0;
            echo "echo '[$count/" . count($offlineServers) . "] Recovering: " . addslashes($srv->name) . " (hostname: " . addslashes($srv->hostname ?? 'unknown') . ")'\n";
            echo "curl -sL '" . $whmcsUrl . "/modules/addons/fmh_monitor/agent_download.php?t='\"\$(date +%s)\" | bash -s -- --install --token='" . $srv->token . "' --url='" . $whmcsUrl . "' --env='" . $env . "' --auto-remediation='" . $autoRem . "' --hmac-secret='" . $hmac . "' --clean\n";
            echo "if [ \$? -eq 0 ]; then echo '  [OK] Recovered successfully'; else echo '  [FAIL] Check server manually'; fi\n";
            echo "sleep 2\n\n";
        }
        echo "echo 'Recovery complete. Check panel for server status.'\n";
        exit;
    } elseif ($action == 'repair_all_offline') {
        // Reset machine_id for ALL offline servers so they can reconnect
        $count = Capsule::table('mod_fmh_monitor_servers')
            ->where('status', 'DOWN')
            ->update(['machine_id' => null]);
        header("Location: addonmodules.php?module=fmh_monitor&tab=servers&success=repaired_all&count={$count}");
        exit;
    } elseif ($action == 'clear_server_logs') {
        $server_id = (int) $_GET['id'];
        $type = $_GET['type'] ?? 'all';
        if ($type == 'logs' || $type == 'all') {
            Capsule::table('mod_fmh_monitor_logs')->where('server_id', $server_id)->delete();
        }
        if ($type == 'ssh' || $type == 'all') {
            Capsule::table('mod_fmh_monitor_ssh_logins')->where('server_id', $server_id)->delete();
        }
        if ($type == 'audit' || $type == 'all') {
            Capsule::table('mod_fmh_monitor_audit_commands')->where('server_id', $server_id)->delete();
        }
        header("Location: addonmodules.php?module=fmh_monitor&action=view_server&id={$server_id}&success=logs_cleared");
        exit;
    } elseif ($action == 'reset_ssh_key') {
        $server_id = (int) $_GET['id'];
        // Delete stored SSH key for this server (agent will resend on next sync)
        $keyPath = __DIR__ . '/keys/server_' . $server_id;
        @unlink($keyPath);
        @unlink($keyPath . '.pub');
        Capsule::table('mod_fmh_monitor_servers')->where('id', $server_id)->update(['ssh_key' => null, 'ssh_pubkey' => null]);
        header("Location: addonmodules.php?module=fmh_monitor&action=view_server&id={$server_id}&success=ssh_key_reset");
        exit;
    } elseif ($action == 'auto_repair_agent') {
        $server_id = (int) $_GET['id'];
        $server = Capsule::table('mod_fmh_monitor_servers')->where('id', $server_id)->first();
        if (!$server) { header("Location: addonmodules.php?module=fmh_monitor&tab=servers"); exit; }
        
        $whmcsUrl = rtrim(Capsule::table('tblconfiguration')->where('setting', 'SystemURL')->value('value'), '/');
        $hmac = $server->hmac_secret ?? ''; $env = $server->environment ?? 'linux';
        $autoRem = $server->auto_remediation ?? 0;
        // Always prefer IPv4 for SSH (hostname may not resolve, IPv6 may not route)
        $targetHost = null;
        if ($server->ip && filter_var($server->ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $targetHost = $server->ip;
        }
        if (!$targetHost) $targetHost = $server->hostname;
        if (!$targetHost) $targetHost = $server->ip;
        
        $sshUser = $server->ssh_user ?? 'root';
        $sshKey = $server->ssh_key ?? null;
        // Try multiple key paths (agent sends key to new module-dir, DB may have old /etc path)
        if (!$sshKey || !file_exists($sshKey)) {
            $altKey = __DIR__ . '/keys/server_' . $server_id;
            if (file_exists($altKey) && file_exists($altKey . '.pub')) {
                $sshKey = $altKey;
            } elseif ($sshKey && file_exists(str_replace('/etc/fmh_monitor/keys/', __DIR__ . '/keys/', $sshKey))) {
                $sshKey = str_replace('/etc/fmh_monitor/keys/', __DIR__ . '/keys/', $sshKey);
            }
        }
        $sshPort = $server->ssh_port ?? 22;
        
        // Check if key actually exists before attempting SSH
        if (!$sshKey || !file_exists($sshKey)) {
            header("Location: addonmodules.php?module=fmh_monitor&action=view_server&id={$server_id}&repair=fail&reason=" . urlencode('Chave SSH nao encontrada. Execute o comando de instalacao 1x no servidor para o agente enviar a chave.'));
            exit;
        }
        
        if (!$targetHost) { header("Location: addonmodules.php?module=fmh_monitor&action=view_server&id={$server_id}&repair=fail&reason=no_ip"); exit; }
        
        $pubkey = $server->ssh_pubkey ?? Capsule::table('mod_fmh_monitor_settings')->where('setting_key', 'ssh_public_key')->value('setting_value');
        $dlUrl = $whmcsUrl . '/modules/addons/fmh_monitor/agent_download.php?t=' . time();
        $escToken = addslashes($server->token);
        $escUrl = addslashes($whmcsUrl);
        $escPubkey = $pubkey ? addslashes($pubkey) : '';
        $pubkeyArg = $escPubkey ? " --ssh-public-key='{$escPubkey}'" : '';
        
        // Step 1: Download script
        // Step 2: Execute script  
        // Step 3: Signal success
        $repairCmd = "curl -s '{$dlUrl}' -o /tmp/fmh_repair.sh 2>&1; E1=\$?; bash /tmp/fmh_repair.sh -- --install --token='{$escToken}' --url='{$escUrl}' --env='{$env}' --auto-remediation='{$autoRem}' --hmac-secret='{$hmac}' --clean{$pubkeyArg}; E2=\$?; echo REPAIR_RESULT:\${E1}_\${E2}";
        
        $output = []; $exitCode = -1; $method = 'none'; $errors = [];
        
        // Method 1: ssh2 extension
        if (function_exists('ssh2_connect') && !in_array('ssh2_connect', explode(',', ini_get('disable_functions') ?: ''))) {
            try {
                $errors[] = "ssh2: trying {$sshUser}@{$targetHost}:{$sshPort}";
                $conn = @ssh2_connect($targetHost, intval($sshPort));
                if (!$conn) {
                    $errors[] = "ssh2: connection failed to {$targetHost}:{$sshPort}";
                } else {
                    $authOk = false;
                    if ($sshKey && file_exists($sshKey) && file_exists("{$sshKey}.pub")) {
                        $authOk = @ssh2_auth_pubkey_file($conn, $sshUser, "{$sshKey}.pub", $sshKey);
                        if (!$authOk) $errors[] = "ssh2: pubkey auth failed (key: {$sshKey})";
                    } else {
                        $errors[] = "ssh2: no key found at {$sshKey}";
                    }
                    if ($authOk) {
                        $stream = @ssh2_exec($conn, $repairCmd);
                        if ($stream) {
                            stream_set_blocking($stream, true);
                            $stderrStream = @ssh2_fetch_stream($stream, SSH2_STREAM_STDERR);
                            if ($stderrStream) {
                                stream_set_blocking($stderrStream, true);
                                $errors[] = "ssh2: stderr: " . stream_get_contents($stderrStream);
                                fclose($stderrStream);
                            }
                            $output = [stream_get_contents($stream)];
                            $exitCode = 0; $method = 'ssh2';
                            fclose($stream);
                        } else {
                            $errors[] = "ssh2: exec failed";
                        }
                    }
                }
            } catch (\Exception $e) {
                $errors[] = "ssh2: exception - " . $e->getMessage();
            }
        } else {
            $errors[] = "ssh2: extension not available";
        }
        
        // Method 2: exec + ssh binary (disabled by user, keep as log only)
        if ($exitCode !== 0 && function_exists('exec') && !in_array('exec', explode(',', ini_get('disable_functions') ?: ''))) {
            $errors[] = "exec: trying ssh binary";
            $sshTarget = escapeshellarg("{$sshUser}@{$targetHost}");
            $keyArg = ($sshKey && file_exists($sshKey)) ? '-i ' . escapeshellarg($sshKey) . ' ' : '';
            $fullCmd = "ssh -o StrictHostKeyChecking=no -o ConnectTimeout=10 -o BatchMode=yes -p " . intval($sshPort) . " {$keyArg}{$sshTarget} " . escapeshellarg($repairCmd) . " 2>&1";
            exec($fullCmd, $output, $exitCode);
            $method = 'exec';
        } else {
            $errors[] = "exec: disabled or not available";
        }
        
        $outputStr = is_array($output) ? implode("\n", $output) : '';
        $errorLog = implode(" | ", $errors);
        
        // Success: REPAIR_RESULT:0_0 means both curl and bash succeeded
        $isSuccess = (strpos($outputStr, 'REPAIR_RESULT:0_0') !== false) 
                  || (strpos($outputStr, 'REPAIR_OK') !== false)
                  || (strpos($outputStr, 'Concluida') !== false);
        
        if ($isSuccess) {
            header("Location: addonmodules.php?module=fmh_monitor&action=view_server&id={$server_id}&repair=success&method={$method}");
        } else {
            header("Location: addonmodules.php?module=fmh_monitor&action=view_server&id={$server_id}&repair=fail&reason=" . urlencode($errorLog));
        }
        exit;
    } elseif ($action == 'remote_exec') {
        // Auth via WHMCS session (user is already logged in as admin)
        if (empty($_SESSION['adminid'])) { echo json_encode(['error' => 'Nao autorizado.']); exit; }
        $server_id = (int) $_POST['server_id'];
        $cmd = trim($_POST['command'] ?? '');
        if (empty($cmd) || strlen($cmd) > 500) {
            echo json_encode(['output' => '', 'error' => 'Comando invalido ou muito longo.']);
            exit;
        }
        
        $server = Capsule::table('mod_fmh_monitor_servers')->where('id', $server_id)->first();
        if (!$server) { echo json_encode(['error' => 'Servidor nao encontrado.']); exit; }
        
        $targetHost = $server->ip;
        if (!$targetHost || !filter_var($targetHost, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $targetHost = $server->hostname ?: $server->ip;
        }
        $sshPort = $server->ssh_port ?? 22;
        $sshUser = $server->ssh_user ?? 'root';
        $sshKey = $server->ssh_key ?? null;
        
        if (!$sshKey || !file_exists($sshKey)) {
            $altKey = __DIR__ . '/keys/server_' . $server_id;
            if (file_exists($altKey) && file_exists($altKey . '.pub')) $sshKey = $altKey;
        }
        if (!$sshKey || !file_exists($sshKey)) {
            echo json_encode(['error' => 'Chave SSH nao configurada. Execute o agente 1x no servidor.']);
            exit;
        }
        
        $result = ['output' => '', 'error' => ''];
        
        if (function_exists('ssh2_connect')) {
            $conn = @ssh2_connect($targetHost, intval($sshPort));
            if ($conn && @ssh2_auth_pubkey_file($conn, $sshUser, "{$sshKey}.pub", $sshKey)) {
                // Use script for PTY allocation (colors, htop, etc.) - wrapped in timeout
                $needsPty = (strpos($cmd, 'htop') !== false || strpos($cmd, 'vim') !== false || strpos($cmd, 'nano') !== false || strpos($cmd, 'mc') !== false);
                if ($needsPty) {
                    $execCmd = "script -q -c " . escapeshellarg("TERM=xterm-256color COLUMNS=120 LINES=40 timeout 8 " . $cmd) . " /dev/null 2>&1";
                } elseif (strpos($cmd, 'top') !== false && strpos($cmd, '-b') === false) {
                    $execCmd = 'top -bn1 -w120 2>&1 | head -25';
                } else {
                    $execCmd = "timeout 10 bash -c " . escapeshellarg("export TERM=xterm-256color; " . $cmd . ' 2>&1');
                }
                $stream = @ssh2_exec($conn, $execCmd);
                if ($stream) {
                    stream_set_blocking($stream, false);
                    $startTime = time(); $buffer = '';
                    while (!feof($stream) && (time() - $startTime) < 15) {
                        $buffer .= fread($stream, 8192);
                        usleep(30000);
                    }
                    fclose($stream);
                    $result['output'] = $buffer ?: '(sem saida)';
                } else {
                    $result['error'] = 'Execucao falhou.';
                }
            } else {
                $result['error'] = 'Conexao SSH falhou. Verifique a chave.';
            }
        } else {
            $result['error'] = 'Extensao ssh2 nao disponivel.';
        }
        
        // Audit log - always log even if command failed
        try {
            Capsule::table('mod_fmh_monitor_audit_commands')->insert([
                'server_id' => $server_id,
                'user' => $sshUser,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                'geolocation' => 'Web Terminal',
                'command' => $cmd,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {}
        
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }

    if ($action == 'view') {
        $id = (int)$_GET['id'];
        $service = Capsule::table('mod_fmh_monitor_services')->where('id', $id)->first();
        
        if (!$service) {
            header("Location: addonmodules.php?module=fmh_monitor");
            exit;
        }

        // Buscar todos os logs do serviço, ordenados do mais antigo para o mais novo para pareamento
        $allLogs = Capsule::table('mod_fmh_monitor_logs')
            ->where('service_id', $id)
            ->orderBy('created_at', 'asc')
            ->get();

        $historyData = [];
        $downEvent = null;
        $totalDowntimeMinutes = 0;
        $outagesCount = 0;
        
        // Dados para o gráfico (últimos 30 dias)
        $chartData = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = date('d/m', strtotime("-$i days"));
            $chartData[$date] = 0;
        }

        foreach ($allLogs as $log) {
            $logDateStr = date('d/m', strtotime($log->created_at));
            
            if ($log->event_type == 'DOWN') {
                $downEvent = $log;
                $outagesCount++;
                if (isset($chartData[$logDateStr])) {
                    $chartData[$logDateStr]++;
                }
            } elseif ($log->event_type == 'UP' && $downEvent) {
                // Fechou o ciclo
                $downTimeObj = new DateTime($downEvent->created_at);
                $upTimeObj = new DateTime($log->created_at);
                $diff = $downTimeObj->diff($upTimeObj);
                $minutes = ($diff->days * 24 * 60) + ($diff->h * 60) + $diff->i;
                
                $totalDowntimeMinutes += $minutes;

                // Adiciona no início do array para exibição (mais recente primeiro)
                array_unshift($historyData, [
                    'down_time' => $downEvent->created_at,
                    'up_time' => $log->created_at,
                    'duration_minutes' => $minutes
                ]);
                $downEvent = null;
            }
        }
        
        // Se ainda estiver DOWN (não voltou ainda)
        if ($downEvent) {
            $downTimeObj = new DateTime($downEvent->created_at);
            $upTimeObj = new DateTime(); // Agora
            $diff = $downTimeObj->diff($upTimeObj);
            $minutes = ($diff->days * 24 * 60) + ($diff->h * 60) + $diff->i;
            
            $totalDowntimeMinutes += $minutes;
            
            array_unshift($historyData, [
                'down_time' => $downEvent->created_at,
                'up_time' => 'Ainda Offline',
                'duration_minutes' => $minutes
            ]);
        }
        
        // Calcular Uptime %
        $totalMinutes30Days = 30 * 24 * 60;
        $uptimePercentage = 100;
        if ($totalDowntimeMinutes > 0) {
            $uptimePercentage = (($totalMinutes30Days - $totalDowntimeMinutes) / $totalMinutes30Days) * 100;
            if ($uptimePercentage < 0) $uptimePercentage = 0;
        }

        // Variáveis para a view (locais para o include)
        $serviceDetails = $service;
        $uptimePercentageFormatted = number_format($uptimePercentage, 2, ',', '');
        // $historyData, $outagesCount e $totalDowntimeMinutes já estão definidos no escopo local
        $chartLabels = json_encode(array_keys($chartData));
        $chartValues = json_encode(array_values($chartData));
        
        // Rendereiza o template
        include __DIR__ . '/templates/admin_dashboard.php';
        return;
    }

    // Listagem padrão do Dashboard
    $services = Capsule::table('mod_fmh_monitor_services')
        ->leftJoin('tblclients', 'mod_fmh_monitor_services.client_id', '=', 'tblclients.id')
        ->select('mod_fmh_monitor_services.*', 'tblclients.firstname as client_firstname', 'tblclients.lastname as client_lastname', 'tblclients.companyname as client_companyname')
        ->get();
    
    // Adicionar uptime percentage para cada serviço na listagem
    $thirtyDaysAgo = date('Y-m-d H:i:s', strtotime('-30 days'));
    $allRecentLogs = Capsule::table('mod_fmh_monitor_logs')
        ->where('created_at', '>=', $thirtyDaysAgo)
        ->orderBy('created_at', 'asc')
        ->get();
        
    // Agrupar logs por service_id
    $logsByService = [];
    foreach ($allRecentLogs as $log) {
        $logsByService[$log->service_id][] = $log;
    }

    $total = count($services);
    $upCount = 0;
    $downCount = 0;
    $pausedCount = 0;
    $lastCronRun = null;
    
    foreach ($services as $srv) {
        // Calcular Uptime de 30 dias para a listagem
        $sTotalDown = 0;
        $sDownEvent = null;
        if (isset($logsByService[$srv->id])) {
            foreach ($logsByService[$srv->id] as $l) {
                if ($l->event_type == 'DOWN') {
                    $sDownEvent = $l;
                } elseif ($l->event_type == 'UP' && $sDownEvent) {
                    $diff = (new DateTime($sDownEvent->created_at))->diff(new DateTime($l->created_at));
                    $sTotalDown += ($diff->days * 24 * 60) + ($diff->h * 60) + $diff->i;
                    $sDownEvent = null;
                }
            }
            if ($sDownEvent) {
                $diff = (new DateTime($sDownEvent->created_at))->diff(new DateTime());
                $sTotalDown += ($diff->days * 24 * 60) + ($diff->h * 60) + $diff->i;
            }
        }
        
        $totalMinutes30Days = 30 * 24 * 60;
        $uptime = 100;
        if ($sTotalDown > 0) {
            $uptime = (($totalMinutes30Days - $sTotalDown) / $totalMinutes30Days) * 100;
            if ($uptime < 0) $uptime = 0;
        }
        $srv->uptime_percentage = number_format($uptime, 2, ',', '');

        if ($srv->status == 'UP') $upCount++;
        elseif ($srv->status == 'DOWN') $downCount++;
        elseif ($srv->status == 'PAUSED') $pausedCount++;
        
        if (!$lastCronRun || strtotime($srv->last_check) > strtotime($lastCronRun)) {
            $lastCronRun = $srv->last_check;
        }
        
        // SLA/Uptime em tempo real (Geral, simplificado para % de up x total logs caso aplicavel)
        // Aqui apenas calculamos os down baseado na última vez que caiu. Para um % real, o ideal é contar logs ou usar dias.
        // Como simplificação, se ele não está caído, digamos que está 99.9%, mas por agora, não vamos gerar cálculos pesados.
    }
    
    $cronStatus = 'INATIVO / AGUARDANDO';
    $cronColor = '#e02424'; // Red
    if ($lastCronRun) {
        $diff = time() - strtotime($lastCronRun);
        if ($diff <= 180) { // Menos de 3 minutos
            $cronStatus = 'ATIVO E RODANDO';
            $cronColor = '#059669'; // Green
        }
    }
    
    $cronCommand = 'php -q ' . __DIR__ . '/cron.php';

    // Pegar Servidores (Agents)
    $servers = Capsule::table('mod_fmh_monitor_servers')
        ->leftJoin('tblclients', 'mod_fmh_monitor_servers.client_id', '=', 'tblclients.id')
        ->select('mod_fmh_monitor_servers.*', 'tblclients.firstname as client_firstname', 'tblclients.lastname as client_lastname', 'tblclients.companyname as client_companyname')
        ->get();
    
    $serverDetails = null;
    $serverMetrics = [];
    $serverThresholds = [];
    $serverLogs = [];
    if ($action == 'view_server' && isset($_GET['id'])) {
        $server_id = (int)$_GET['id'];
        $serverDetails = Capsule::table('mod_fmh_monitor_servers')->where('id', $server_id)->first();
        if ($serverDetails) {
            // Métricas das últimas 24 horas (aprox 1440 minutos)
            $sevenDaysAgo = date('Y-m-d H:i:s', strtotime('-1 days'));
            $metrics = Capsule::table('mod_fmh_monitor_server_metrics')
                ->where('server_id', $server_id)
                ->where('created_at', '>=', $sevenDaysAgo)
                ->orderBy('id', 'desc')
                ->limit(1440)
                ->get();
            $serverMetricsRaw = array_reverse((array) $metrics->toArray());
            
            // Otimização: Reduzir para no máximo 300 pontos no gráfico para não travar o navegador
            $serverMetrics = [];
            $totalPoints = count($serverMetricsRaw);
            if ($totalPoints > 150) {
                $step = ceil($totalPoints / 150);
                for ($i = 0; $i < $totalPoints; $i += $step) {
                    $serverMetrics[] = $serverMetricsRaw[$i];
                }
                // Garante que o último ponto (tempo real) esteja no gráfico
                if (end($serverMetrics)->id !== end($serverMetricsRaw)->id) {
                    $serverMetrics[] = end($serverMetricsRaw);
                }
            } else {
                $serverMetrics = $serverMetricsRaw;
            }
            
            // Logs Historico
            $serverLogs = Capsule::table('mod_fmh_monitor_logs')
                ->where('server_id', $server_id)
                ->orderBy('id', 'desc')
                ->limit(100)
                ->get();

            $serverThresholds = Capsule::table('mod_fmh_monitor_server_thresholds')
                ->where('server_id', $server_id)
                ->get();
                
            $auditCommands = [];
            if (Capsule::schema()->hasTable('mod_fmh_monitor_audit_commands')) {
                $auditCommands = Capsule::table('mod_fmh_monitor_audit_commands')
                    ->where('server_id', $server_id)
                    ->orderBy('id', 'desc')
                    ->limit(50)
                    ->get();
            }

            $sshLogins = [];
            if (Capsule::schema()->hasTable('mod_fmh_monitor_ssh_logins')) {
                $sshLogins = Capsule::table('mod_fmh_monitor_ssh_logins')
                    ->where('server_id', $server_id)
                    ->orderBy('id', 'desc')
                    ->limit(20)
                    ->get();
            }

            $sslCerts = [];
            if (Capsule::schema()->hasTable('mod_fmh_monitor_ssl_certs')) {
                $sslCerts = Capsule::table('mod_fmh_monitor_ssl_certs')
                    ->where('server_id', $server_id)
                    ->orderBy('days_remaining', 'asc')
                    ->get();
            }

            $ipBlacklist = [];
            if (Capsule::schema()->hasTable('mod_fmh_monitor_ip_blacklist')) {
                $ipBlacklist = Capsule::table('mod_fmh_monitor_ip_blacklist')
                    ->where('server_id', $server_id)
                    ->orderBy('created_at', 'desc')
                    ->limit(50)
                    ->get();
            }
        }
    }

    // Pegar Settings
    $settingsRaw = Capsule::table('mod_fmh_monitor_settings')->get();
    $settings = [];
    foreach ($settingsRaw as $s) {
        $settings[$s->setting_key] = $s->setting_value;
    }

    // Pegar Webhooks
    $webhooks = Capsule::table('mod_fmh_monitor_webhooks')->get();
    
    // Pegar Logs Recentes (Últimos 50)
    $logs = Capsule::table('mod_fmh_monitor_logs')
        ->join('mod_fmh_monitor_services', 'mod_fmh_monitor_logs.service_id', '=', 'mod_fmh_monitor_services.id')
        ->select('mod_fmh_monitor_logs.*', 'mod_fmh_monitor_services.name as service_name', 'mod_fmh_monitor_services.ip')
        ->orderBy('mod_fmh_monitor_logs.id', 'desc')
        ->limit(50)
        ->get();

    $templateFile = __DIR__ . '/templates/admin_dashboard.php';
    if (file_exists($templateFile)) {
        require $templateFile;
    } else {
        echo "<div class='alert alert-danger'>Arquivo de template admin_dashboard.php não encontrado.</div>";
    }
}
