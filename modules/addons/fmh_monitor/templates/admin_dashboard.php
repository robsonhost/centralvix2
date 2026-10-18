<?php
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}
?>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link rel="stylesheet" href="../modules/addons/fmh_monitor/templates/fmh-monitor.css?v=3.0">

<div class="fmh-monitor-wrapper">
    
    <!-- SIDEBAR -->
    <div class="fmh-sidebar">
        <div class="fmh-brand">
            <div class="fmh-brand-icon"><i class="fa-solid fa-satellite-dish"></i></div>
            <div>
                <div class="fmh-brand-text">FMH Monitor</div>
                <div class="fmh-brand-sub">v<?php echo AGENT_CURRENT_VERSION; ?></div>
            </div>
        </div>
        <button class="fmh-theme-toggle" onclick="fmhToggleTheme()">
            <i class="fa-solid fa-moon" id="theme-icon"></i> <span id="theme-text">Tema Escuro</span>
        </button>
        <?php 
        $validFilePath = dirname(__DIR__) . '/agent_bash.valid';
        $agentValid = file_exists($validFilePath);
        $whmcsBase = rtrim(\Illuminate\Database\Capsule\Manager::table('tblconfiguration')->where('setting', 'SystemURL')->value('value'), '/');
        ?>
        <a href="#" onclick="event.preventDefault();var x=new XMLHttpRequest();x.open('GET','<?php echo $whmcsBase; ?>/modules/addons/fmh_monitor/agent_api.php?action=validate_agent&t='+Date.now());x.onload=function(){try{var d=JSON.parse(x.responseText);var m=d.valid?'✅ Agent v'+d.version+' - '+d.lines+' linhas - Sintaxe OK':'❌ ERRO: '+(d.errors||['unknown']).join(' | ');fmhToast(m,d.valid?'success':'error');}catch(e){fmhToast('Validacao OK - veja agent_api.php','success');}};x.onerror=function(){fmhToast('Erro de rede ao validar','error');};x.send();return false;" style="display:block;margin:0 12px 6px;padding:6px 10px;border-radius:6px;text-decoration:none;font-size:10px;font-weight:600;text-align:center;cursor:pointer;<?php echo $agentValid?'color:#22c55e;background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.2)':'color:#ef4444;background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.2)';?>">
            <?php echo $agentValid?'✅ Agent Script OK':'❌ Agent Script com Erro';?>
        </a>
        <ul class="fmh-nav">
            <li><a href="addonmodules.php?module=fmh_monitor&tab=dashboard" class="<?php echo $tab == 'dashboard' && $action != 'view_server' ? 'active' : ''; ?>"><i class="fa-solid fa-grid-2"></i> Dashboard</a></li>
            <li><a href="addonmodules.php?module=fmh_monitor&tab=servers" class="<?php echo $tab == 'servers' || $action == 'view_server' ? 'active' : ''; ?>"><?php if(!empty($srv->last_pwd_alert)): ?><div style="margin-bottom:8px;"><span class="fmh-status-badge" style="background:rgba(239, 68, 68, 0.1); color:#ef4444; border-color:rgba(239,68,68,0.3); padding:2px 6px; font-size:10px;"><i class="fa-solid fa-triangle-exclamation" style="margin-right:4px;"></i>Senha Root Alterada!</span></div><?php endif; ?><i class="fa-solid fa-server"></i> Servidores</a></li>
            <li class="fmh-nav-divider"></li>
            <li><a href="addonmodules.php?module=fmh_monitor&tab=settings" class="<?php echo $tab == 'settings' ? 'active' : ''; ?>"><i class="fa-solid fa-sliders"></i> Integrações</a></li>
            <li><a href="addonmodules.php?module=fmh_monitor&tab=templates" class="<?php echo $tab == 'templates' ? 'active' : ''; ?>"><i class="fa-solid fa-pen-to-square"></i> Templates</a></li>
            <li><a href="addonmodules.php?module=fmh_monitor&tab=webhooks" class="<?php echo $tab == 'webhooks' ? 'active' : ''; ?>"><i class="fa-solid fa-plug"></i> Webhooks</a></li>
            <li><a href="addonmodules.php?module=fmh_monitor&tab=logs" class="<?php echo $tab == 'logs' ? 'active' : ''; ?>"><i class="fa-solid fa-list-check"></i> Histórico</a></li>
        </ul>
    </div>

    <!-- CONTENT -->
    <div class="fmh-content">
        <div class="fmh-container">
            <div id="fmh-live-indicator" class="fmh-live-indicator" style="display:none;">
                <span class="pulse-dot up" style="margin-right:8px;"></span> Monitoramento ao vivo - Atualizando automaticamente
            </div>
            <?php if (isset($_GET['repair'])): ?>
                <?php if ($_GET['repair'] == 'success'): ?>
                <div class="fmh-alert" style="border-color: rgba(34,197,94,0.3); background: rgba(34,197,94,0.05); color: #22c55e;">
                    <i class="fa-solid fa-circle-check" style="font-size: 20px;"></i> 
                    <strong>Auto-Reparo concluído!</strong> O agente foi reinstalado com sucesso via SSH. Em até 1 minuto o servidor deve aparecer online.
                </div>
                <?php else: ?>
                <div class="fmh-alert" style="border-color: rgba(239,68,68,0.3); background: rgba(239,68,68,0.05); color: #fca5a5;">
                    <i class="fa-solid fa-triangle-exclamation" style="font-size: 20px;"></i> 
                    <strong>Auto-Reparo falhou</strong>
                    <pre style="margin:8px 0; font-size:11px; color:#9ca3af; white-space:pre-wrap; word-break:break-all;"><?php echo htmlspecialchars(str_replace(' | ', "\n", $_GET['reason'] ?? 'Erro desconhecido')); ?></pre>
                    <?php if (strpos($_GET['reason'] ?? '', 'Chave SSH nao encontrada') !== false): ?>
                    <strong style="color:#f59e0b;">Para resolver:</strong> execute o <strong>Comando de Instalação</strong> 1x no servidor via SSH. O agente vai gerar a chave e enviar ao WHMCS. Depois disso o Auto-Reparo funciona permanentemente.
                    <?php else: ?>
                    Verifique: a chave SSH foi enviada? O WHMCS consegue resolver o hostname? A porta SSH (<?php echo (int)($serverDetails->ssh_port ?? 22); ?>) esta acessivel?
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            <?php endif; ?>
            <?php if (isset($_GET['success'])): ?>
                <div class="fmh-alert">
                    <i class="fa-solid fa-circle-check" style="font-size: 20px;"></i> 
                    <?php 
                    if ($_GET['success'] == 'agent_updated') {
                        echo "Configurações atualizadas com sucesso! <br><small style='color:#9ca3af;'>Se você alterou o Ambiente ou a opção de Auto-Cura, lembre-se de rodar o comando de instalação no servidor novamente para aplicar as mudanças. Para IPs na lista branca, nenhuma ação é necessária.</small>";
                    } elseif ($_GET['success'] == 'token_rotated') {
                        echo "Token rotacionado com sucesso! <br><small style='color:#9ca3af;'>Copie o novo comando de instalação e execute no servidor.</small>";
                    } elseif ($_GET['success'] == 'repaired_all') {
                        $cnt = $_GET['count'] ?? 0;
                        echo "Machine ID resetado em <strong>{$cnt} servidores offline</strong>! <br><small style='color:#9ca3af;'>Agora execute o agente em cada servidor ou use o comando de instalação com --clean.</small>";
                    } else {
                        echo "Ação realizada com sucesso.";
                    }
                    ?>
                </div>
            <?php endif; ?>            
                <!-- ==============================================
                 VIEW: SERVICE DETAILS
                 ============================================== -->
            <?php if (isset($serviceDetails) && $serviceDetails): ?>
                
                <div class="fmh-header-flex" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 35px;">
                    <div>
                        <a href="addonmodules.php?module=fmh_monitor&tab=dashboard" style="color: #9ca3af; text-decoration: none; font-size: 14px; margin-bottom: 8px; display: inline-block;">
                            <i class="fa-solid fa-arrow-left"></i> Voltar ao Dashboard
                        </a>
                        <h2 class="fmh-page-title">
                            <?php echo htmlspecialchars($serviceDetails->name); ?>
                            <?php if ($serviceDetails->status == 'UP'): ?>
                                <span class="fmh-status-badge fmh-badge-up" style="margin-left: 10px; font-size: 14px; vertical-align: middle;">ONLINE</span>
                            <?php elseif ($serviceDetails->status == 'DOWN'): ?>
                                <span class="fmh-status-badge fmh-badge-down" style="margin-left: 10px; font-size: 14px; vertical-align: middle;">OFFLINE</span>
                            <?php endif; ?>
                        </h2>
                        <p class="fmh-page-subtitle">
                            <code style="padding: 4px 8px; border-radius: 6px;">
                                <?php echo htmlspecialchars($serviceDetails->ip); ?><?php echo $serviceDetails->port ? ':' . (int)$serviceDetails->port : ''; ?>
                            </code>
                            <span style="margin-left: 10px;"><i class="fa-solid fa-radar"></i> <?php echo $serviceDetails->type; ?></span>
                        </p>
                    </div>
                </div>

                <div class="fmh-stats-grid fmh-grid-3">
                    <div class="fmh-stat-card">
                        <div>
                            <div class="fmh-stat-info"><h4>Uptime (Últimos 30 dias)</h4><h2><?php echo $uptimePercentageFormatted; ?>%</h2></div>
                        </div>
                        <div class="fmh-icon-box fmh-icon-up"><i class="fa-solid fa-percent"></i></div>
                    </div>
                    <div class="fmh-stat-card">
                        <div>
                            <div class="fmh-stat-info"><h4>Total de Quedas (Geral)</h4><h2><?php echo $outagesCount; ?></h2></div>
                        </div>
                        <div class="fmh-icon-box fmh-icon-down"><i class="fa-solid fa-triangle-exclamation"></i></div>
                    </div>
                    <div class="fmh-stat-card">
                        <div>
                            <div class="fmh-stat-info"><h4>Tempo Total Offline</h4><h2><?php echo $totalDowntimeMinutes; ?> <span style="font-size: 16px; font-weight: 400;">min</span></h2></div>
                        </div>
                        <div class="fmh-icon-box fmh-icon-down"><i class="fa-solid fa-stopwatch"></i></div>
                    </div>
                </div>

                <!-- Gráfico Chart.js -->
                <div class="fmh-panel">
                    <h3 class="fmh-panel-title"><i class="fa-solid fa-chart-column"></i> Ocorrências nos Últimos 30 Dias</h3>
                    <div style="height: 250px; width: 100%;">
                        <canvas id="outagesChart"></canvas>
                    </div>
                </div>

                <!-- Tabela de Pareamento -->
                <div class="fmh-panel" style="padding: 0; overflow: hidden;">
                    <div style="padding: 24px; border-bottom: 1px solid rgba(255,255,255,0.05);">
                        <h3 class="fmh-panel-title" style="margin:0;"><i class="fa-solid fa-clock-rotate-left"></i> Histórico de Indisponibilidade</h3>
                    </div>
                    <div class="fmh-table-responsive">
                        <table class="fmh-table">
                            <thead>
                                <tr>
                                    <th style="padding-left: 24px;">Data/Hora da Queda</th>
                                    <th>Data/Hora do Retorno</th>
                                    <th>Duração (Minutos)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($historyData)): ?>
                                    <tr><td colspan="3" style="text-align:center; padding: 40px; color:#6b7280;">Nenhuma queda registrada para este serviço! Tudo 100%.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($historyData as $hd): ?>
                                        <tr>
                                            <td style="padding-left: 24px; color: #fca5a5;">
                                                <i class="fa-solid fa-arrow-down" style="margin-right: 5px;"></i> <?php echo date('d/m/Y H:i:s', strtotime($hd['down_time'])); ?>
                                            </td>
                                            <td style="color: #6ee7b7;">
                                                <?php if ($hd['up_time'] === 'Ainda Offline'): ?>
                                                    <span class="pulse-dot down" style="margin-right: 5px;"></span> Ainda Offline
                                                <?php else: ?>
                                                    <i class="fa-solid fa-arrow-up" style="margin-right: 5px;"></i> <?php echo date('d/m/Y H:i:s', strtotime($hd['up_time'])); ?>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <strong style="color: #e5e7eb;"><?php echo $hd['duration_minutes']; ?> min</strong>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <script>
                    document.addEventListener("DOMContentLoaded", function() {
                        const ctx = document.getElementById('outagesChart').getContext('2d');
                        new Chart(ctx, {
                            type: 'bar',
                            data: {
                                labels: <?php echo $chartLabels; ?>,
                                datasets: [{
                                    label: 'Número de Quedas (DOWN)',
                                    data: <?php echo $chartValues; ?>,
                                    backgroundColor: '#ef4444',
                                    borderColor: '#ef4444',
                                    borderWidth: 1,
                                    borderRadius: 6
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        ticks: { color: '#a1a1aa', stepSize: 1 },
                                        grid: { color: '#222' }
                                    },
                                    x: {
                                        ticks: { color: '#a1a1aa' },
                                        grid: { display: false }
                                    }
                                },
                                plugins: {
                                    legend: { display: false }
                                }
                            }
                        });
});
</script>

<script>
function fmhToast(message, type) {
    var container = document.querySelector('.fmh-toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'fmh-toast-container';
        document.body.appendChild(container);
    }
    var toast = document.createElement('div');
    toast.className = 'fmh-toast ' + (type || 'success');
    toast.innerHTML = '<i class="fa-solid fa-' + (type === 'error' ? 'circle-xmark' : 'circle-check') + '"></i> ' + message;
    container.appendChild(toast);
    setTimeout(function() {
        toast.style.animation = 'fmhSlideOut 0.3s ease-out forwards';
        setTimeout(function() { toast.remove(); }, 300);
    }, 4000);
}
document.addEventListener('DOMContentLoaded', function() {
    var successParam = new URLSearchParams(window.location.search).get('success');
    if (successParam) {
        var msgs = {
            'agent_updated': 'Configurações do agente atualizadas com sucesso.',
            'settings_saved': 'Configurações salvas com sucesso.',
            'added': 'Serviço adicionado com sucesso.',
            'updated': 'Atualizado com sucesso.',
            'deleted': 'Removido com sucesso.',
            'server_added': 'Servidor adicionado. Gere o token de instalação.',
            'server_deleted': 'Servidor removido com sucesso.',
            'threshold_added': 'Regra de alerta criada.',
            'threshold_deleted': 'Regra de alerta removida.',
            'webhook_added': 'Webhook registrado.',
            'webhook_deleted': 'Webhook removido.',
            'token_rotated': 'Token rotacionado. Reinstale o agente com o novo comando.',
            'repaired_all': 'Machine ID resetado em todos servidores offline. Rode o agente no SSH.',
        };
        fmhToast(msgs[successParam] || 'Ação realizada com sucesso.', 'success');
        var url = new URL(window.location.href);
        url.searchParams.delete('success');
        window.history.replaceState({}, '', url.toString());
    }
});
</script>

            <!-- ==============================================
                 TAB: DASHBOARD
                 ============================================== -->
            <?php elseif ($tab == 'servers'): ?>
                <div class="fmh-header-flex" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 35px;">
                    <div>
                        <h2 class="fmh-page-title">Servidores (Agents)</h2>
                        <p class="fmh-page-subtitle">Monitore infraestrutura completa (CPU, Memória, Disco e Serviços).</p>
                        <div style="background: rgba(139,92,246,0.1); border: 1px solid rgba(139,92,246,0.2); padding: 8px 15px; border-radius: 8px;">
                        <span style="color: #c4b5fd; font-size: 12px; font-weight: 600;"><i class="fa-solid fa-code-branch"></i> Última Versão do Sistema: <span style="color: #fff;">v<?php echo AGENT_CURRENT_VERSION; ?></span></span>
                    </div>                   </div>
                </div>

                <div class="fmh-panel" style="margin-bottom: 40px;">
                    <h3 class="fmh-panel-title"><?php if(!empty($srv->last_pwd_alert)): ?><div style="margin-bottom:8px;"><span class="fmh-status-badge" style="background:rgba(239, 68, 68, 0.1); color:#ef4444; border-color:rgba(239,68,68,0.3); padding:2px 6px; font-size:10px;"><i class="fa-solid fa-triangle-exclamation" style="margin-right:4px;"></i>Senha Root Alterada!</span></div><?php endif; ?><i class="fa-solid fa-server"></i> Adicionar Novo Servidor</h3>
                    <form action="addonmodules.php?module=fmh_monitor&action=add_server" method="POST">
                        <input type="hidden" name="token" value="<?php echo generate_token('plain'); ?>">
                        <div class="fmh-grid-3">
                            <div class="fmh-form-group">
                                <label>Nome de Identificação</label>
                                <input type="text" name="name" class="fmh-form-control" placeholder="Ex: VPS-01" required>
                            </div>
                            <div class="fmh-form-group">
                                <label>Ambiente do Servidor</label>
                                <select name="environment" class="fmh-form-control" required>
                                    <option value="linux">Linux Genérico / Sem Painel</option>
                                    <option value="cpanel">cPanel / WHM</option>
                                    <option value="aapanel">aaPanel</option>
                                    <option value="plesk">Plesk</option>
                                </select>
                            </div>
                            <div class="fmh-form-group" style="padding-top: 30px; display: flex; align-items: center; gap: 10px;">
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; color: #d1d5db; margin:0;"><input type="checkbox" name="auto_remediation" value="1"> <span style="color:#22c55e;"><i class="fa-solid fa-wand-magic-sparkles"></i> Auto-Cura Ativa</span></label>
                            </div>
                        </div>
                        <div class="fmh-grid-2" style="margin-top: 15px;">
                            <div class="fmh-form-group">
                                <label>ID Cliente WHMCS (Opcional)</label>
                                <select name="client_id" class="fmh-form-control fmh-client-select" style="width: 100%;"><option value=""></option></select>
                            </div>
                            <div class="fmh-form-group" style="padding-top: 30px; display: flex; align-items: center; gap: 10px;">
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; color: #60a5fa; margin:0;"><input type="checkbox" name="notify_client" value="1"> <i class="fa-solid fa-user"></i> Notificar Cliente em Quedas</label>
                            </div>
                        </div>
                        <div style="margin-top: 15px; text-align: right;">
                            <button type="submit" class="fmh-btn fmh-btn-primary"><i class="fa-solid fa-plus"></i> Gerar Agent Token</button>
                        </div>
                    </form>
                </div>

                <div class="fmh-panel" style="padding: 0; overflow: hidden;">
                    <div style="padding: 24px; border-bottom: 1px solid rgba(255,255,255,0.05); display: flex; justify-content: space-between; align-items: center;">
                        <h3 class="fmh-panel-title" style="margin:0; border:0; padding:0;"><i class="fa-solid fa-network-wired"></i> Servidores Gerenciados</h3>
                        <div style="display:flex; gap:8px;">
                            <a href="addonmodules.php?module=fmh_monitor&action=repair_all_offline" class="fmh-btn" style="background: rgba(245,158,11,0.2); color: #f59e0b; border: 1px solid rgba(245,158,11,0.4); font-size: 12px;" onclick="return confirm('Resetar machine_id de TODOS servidores offline? Isso permite que eles se reconectem.');" title="Reseta machine_id de todos offline"><i class="fa-solid fa-link-slash"></i> Reparar Tudo</a>
                            <a href="addonmodules.php?module=fmh_monitor&action=generate_recovery_script" class="fmh-btn" style="background: rgba(239, 68, 68, 0.15); color: #fca5a5; border: 1px solid rgba(239,68,68,0.3); font-size: 12px;" title="Gera script .sh com comandos de reinstalacao para todos os servidores offline"><i class="fa-solid fa-screwdriver-wrench"></i> Script Recuperacao</a>
                        </div>
                    </div>
                    <div class="fmh-table-responsive">
                        <table class="fmh-table">
                            <thead>
                                <tr>
                                    <th style="padding-left: 24px;">Nome / Hostname</th>
                                    <th>Endereço IP</th>
                                    <th>Status (Agent)</th>
                                    <th>OS / Ambiente</th>
                                    <th>Versão (Agente)</th>
                                    <th>Último Sincronismo</th>
                                    <th style="text-align: right; padding-right: 24px;">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($servers)): ?>
                                    <tr><td colspan="6" style="text-align:center; padding: 40px; color:#6b7280;">Nenhum servidor monitorado via agent.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($servers as $srv): ?>
                                        <?php 
                                        $isOffline = ($srv->status == 'DOWN');
                                        $offlineMinutes = 0;
                                        if ($isOffline && $srv->last_check) {
                                            $offlineMinutes = round((time() - strtotime($srv->last_check)) / 60);
                                        }
                                        $whmcsUrl = rtrim(\Illuminate\Database\Capsule\Manager::table('tblconfiguration')->where('setting', 'SystemURL')->value('value'), '/');
                                        ?>
                                        <tr style="<?php echo $isOffline ? 'border-left: 3px solid #ef4444;' : ''; ?>">
                                            <td style="padding-left: 24px;">
                                                <strong style="color: #f3f4f6;"><?php echo htmlspecialchars($srv->name); ?></strong>
                                                <div style="color: #9ca3af; font-size: 11px; margin-top: 2px;"><i class="fa-solid fa-server"></i> <?php echo htmlspecialchars($srv->hostname ?: 'Aguardando Sincronismo...'); ?></div>
                                                <?php if(!empty($srv->client_id)): ?>
                                                    <div style="margin-top: 5px; font-size: 11px;">
                                                        <a href="clientssummary.php?userid=<?php echo $srv->client_id; ?>" target="_blank" style="color: <?php echo $srv->notify_client ? '#60a5fa' : '#6b7280'; ?>; text-decoration: none; padding: 2px 6px; border-radius: 4px; background: rgba(255,255,255,0.05); display: inline-block;" title="ID Cliente: <?php echo $srv->client_id; ?>">
                                                            <i class="fa-solid <?php echo $srv->notify_client ? 'fa-user-check' : 'fa-user'; ?>"></i> <?php echo htmlspecialchars($srv->client_firstname . ' ' . $srv->client_lastname); ?>
                                                        </a>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if ($isOffline && $offlineMinutes > 0): ?>
                                                    <div style="margin-top: 8px; background: rgba(239,68,68,0.08); border: 1px solid rgba(239,68,68,0.2); border-radius: 6px; padding: 10px 12px;">
                                                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                                                            <i class="fa-solid fa-triangle-exclamation" style="color: #ef4444; font-size: 16px;"></i>
                                                            <div>
                                                                <strong style="color: #fca5a5; font-size: 13px;">Offline há <?php echo $offlineMinutes; ?> min</strong>
                                                                <div style="color: #9ca3af; font-size: 11px; margin-top: 2px;">
                                                                    <?php if ($offlineMinutes > 60): ?>
                                                                        Várias horas offline. Execute o comando abaixo no servidor.
                                                                    <?php elseif ($offlineMinutes > 10): ?>
                                                                        Agent pode estar com problema de configuração. Reinstale com o comando.
                                                                    <?php else: ?>
                                                                        Agent pode estar iniciando. Aguarde ou use o comando para forçar reinstalação.
                                                                    <?php endif; ?>
                        </div>
                    </div>
                                                        </div>
                                                        <?php 
                                                        $pubkey = (!empty($srv->ssh_pubkey)) ? $srv->ssh_pubkey : ($settings['ssh_public_key'] ?? '');
                                                        $recoveryCmd = "curl -sL '" . $whmcsUrl . "/modules/addons/fmh_monitor/agent_download.php?t='\"\$(date +%s)\" | bash -s -- --install --token='" . $srv->token . "' --url='" . $whmcsUrl . "' --env='" . ($srv->environment ?? 'linux') . "' --auto-remediation='" . ($srv->auto_remediation ?? 0) . "' --hmac-secret='" . ($srv->hmac_secret ?? '') . "' --clean" . (!empty($pubkey) ? " --ssh-public-key='" . addslashes($pubkey) . "'" : '');
                                                        ?>
                                                        <div style="display: flex; gap: 6px; align-items: stretch;">
                                                            <input type="text" readonly value="<?php echo htmlspecialchars($recoveryCmd); ?>" style="flex:1; background: #000; color: #22c55e; border: 1px solid #333; border-radius: 4px; padding: 6px 10px; font-family: monospace; font-size: 11px; min-width:0;" onclick="this.select();document.execCommand('copy');" title="Clique para copiar">
                                                            <button onclick="var inp=this.previousElementSibling;inp.select();document.execCommand('copy');this.innerHTML='<i class=\'fa-solid fa-check\'></i>';setTimeout(()=>this.innerHTML='<i class=\'fa-solid fa-copy\'></i>',2000);" class="fmh-btn" style="background:#22c55e;color:#000;padding:4px 10px;font-size:11px;white-space:nowrap;" title="Copiar comando"><i class="fa-solid fa-copy"></i> Copiar</button>
                                                        </div>
                                                        <div style="color: #6b7280; font-size: 10px; margin-top: 6px; display: flex; gap: 10px; align-items: center;">
                                                            <span><i class="fa-solid fa-terminal"></i> SSH no servidor → Cole o comando → Enter.</span>
                                                            <?php if (!empty($srv->ssh_user)): ?>
                                                            <a href="addonmodules.php?module=fmh_monitor&action=auto_repair_agent&id=<?php echo $srv->id; ?>" style="color:#22c55e; font-weight:600; text-decoration: none; background: rgba(34,197,94,0.1); padding: 3px 8px; border-radius: 4px;" onclick="this.textContent='Reparando...';" title="WHMCS faz SSH no servidor e reinstala o agente"><i class="fa-solid fa-wrench"></i> Auto-Reparar Agora</a>
                                                            <?php endif; ?>
                                                            <a href="addonmodules.php?module=fmh_monitor&action=view_server&id=<?php echo $srv->id; ?>" style="color:#60a5fa;">Dashboard</a>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <code style="font-size: 12px; background: rgba(0,0,0,0.3); color: #93c5fd; padding: 4px 8px; border-radius: 4px;"><?php echo htmlspecialchars($srv->ip ?: '---'); ?><?php echo $srv->ssh_port ? ':' . $srv->ssh_port : ''; ?></code>
                                                <?php if (isset($srv->ipv6) && $srv->ipv6): ?>
                                                    <br><code style="font-size: 10px; background: rgba(0,0,0,0.2); color: #6b7280; padding: 2px 6px; border-radius: 4px; display: inline-block; margin-top: 4px;"><?php echo htmlspecialchars($srv->ipv6); ?></code>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($srv->status == 'UP'): ?>
                                                    <span style="color: #22c55e; font-size: 12px; font-weight: 600; padding: 4px 10px; background: rgba(34,197,94,0.1); border-radius: 12px;"><i class="fa-solid fa-circle" style="font-size: 8px; margin-right: 4px; vertical-align: middle;"></i> ONLINE</span>
                                                <?php else: ?>
                                                    <span style="color: #ef4444; font-size: 12px; font-weight: 600; padding: 4px 10px; background: rgba(239,68,68,0.1); border-radius: 12px;"><i class="fa-solid fa-circle" style="font-size: 8px; margin-right: 4px; vertical-align: middle;"></i> OFFLINE</span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="color: #9ca3af;">
                                                <div style="margin-bottom: 4px;"><?php echo htmlspecialchars($srv->os ?? 'Desconhecido'); ?></div>
                                                <?php if (isset($srv->environment)): ?>
                                                    <?php
                                                        $envColors = [
                                                            'cpanel' => ['bg' => 'rgba(249, 115, 22, 0.1)', 'text' => '#f97316', 'label' => 'cPanel', 'icon' => 'fa-c'],
                                                            'aapanel' => ['bg' => 'rgba(16, 185, 129, 0.1)', 'text' => '#10b981', 'label' => 'aaPanel', 'icon' => 'fa-a'],
                                                            'linux' => ['bg' => 'rgba(147, 197, 253, 0.1)', 'text' => '#93c5fd', 'label' => 'Linux Puro', 'icon' => 'fa-linux']
                                                        ];
                                                        $envConf = $envColors[$srv->environment] ?? ['bg' => 'rgba(107, 114, 128, 0.1)', 'text' => '#9ca3af', 'label' => ucfirst($srv->environment), 'icon' => 'fa-server'];
                                                    ?>
                                                    <span style="background: <?php echo $envConf['bg']; ?>; color: <?php echo $envConf['text']; ?>; padding: 2px 8px; border-radius: 4px; font-size: 10px; font-weight: 600; display: inline-block;">
                                                        <i class="fa-brands <?php echo $envConf['icon']; ?>" style="margin-right: 3px;"></i> <?php echo htmlspecialchars($envConf['label']); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php 
                                                $v = isset($srv->agent_version) ? $srv->agent_version : '1.0';
                                                if ($v == AGENT_CURRENT_VERSION) {
                                                    echo '<span style="color: #6ee7b7; font-size: 12px;"><i class="fa-solid fa-check-circle"></i> v' . $v . '</span>';
                                                } else {
                                                    echo '<span style="color: #fca5a5; font-size: 12px;" title="Será atualizado em breve"><i class="fa-solid fa-arrow-rotate-right"></i> v' . $v . '</span>';
                                                }
                                                ?>
                                            </td>
                                            <td style="color: #9ca3af;"><i class="fa-solid fa-clock"></i> <?php echo $srv->last_check ? date('d/m/Y H:i', strtotime($srv->last_check)) : 'Nunca'; ?></td>
                                            <td style="text-align: right; padding-right: 24px;">
                                                <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                                    <button onclick="window.location.href='addonmodules.php?module=fmh_monitor&action=view_server&id=<?php echo $srv->id; ?>'" class="fmh-btn fmh-btn-secondary fmh-btn-sm" title="Dashboard Raio-X"><i class="fa-solid fa-chart-line"></i> Dashboard</button>
                                                    <a href="addonmodules.php?module=fmh_monitor&action=delete_server&id=<?php echo $srv->id; ?>&token=<?php echo generate_token('plain'); ?>" class="fmh-btn" style="padding: 6px 12px; font-size: 12px; background: rgba(239,68,68,0.1); color: #ef4444;" onclick="return confirm('ATENÇÃO: Isso excluirá todo o histórico e regras de alerta deste servidor. Continuar?');"><i class="fa-solid fa-trash"></i></a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <!-- ==============================================
                 TAB: VIEW SERVER DASHBOARD
                 ============================================== -->
            <?php elseif ($action == 'view_server' && isset($serverDetails) && $serverDetails): ?>
                
                <div class="fmh-header-flex" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 35px;">
                    <div>
                        <a href="addonmodules.php?module=fmh_monitor&tab=servers" style="color: #9ca3af; text-decoration: none; font-size: 14px; margin-bottom: 8px; display: inline-block;">
                            <i class="fa-solid fa-arrow-left"></i> Voltar aos Servidores
                        </a>
                        <h2 class="fmh-page-title" style="margin-top: 0; margin-bottom: 4px; display: flex; align-items: center; gap: 12px;">
                            <?php echo htmlspecialchars($serverDetails->name); ?>
                            <?php if ($serverDetails->status == 'UP'): ?>
                                <span style="color: #22c55e; font-size: 12px; font-weight: 600; padding: 4px 10px; background: rgba(34,197,94,0.1); border-radius: 12px; letter-spacing: 0.5px;">AGENT CONECTADO</span>
                            <?php else: ?>
                                <span style="color: #ef4444; font-size: 12px; font-weight: 600; padding: 4px 10px; background: rgba(239,68,68,0.1); border-radius: 12px; letter-spacing: 0.5px;">AGENT DESCONECTADO</span>
                            <?php endif; ?>
                            <?php if (!empty($serverMetrics) && isset($serverMetrics[0]->os_uptime) && $serverMetrics[0]->os_uptime): ?>
                                <span style="color: #93c5fd; font-size: 12px; font-weight: 600; padding: 4px 10px; background: rgba(59,130,246,0.1); border-radius: 12px; letter-spacing: 0.5px;"><i class="fa-solid fa-clock"></i> UPTIME: <?php echo htmlspecialchars($serverMetrics[0]->os_uptime); ?></span>
                            <?php endif; ?>
                         </h2>
                        <?php if ($serverDetails->status != 'UP'): 
                            $offSecs = $serverDetails->last_check ? time() - strtotime($serverDetails->last_check) : 99999;
                            $offMins = round($offSecs / 60);
                            $offHrs = round($offMins / 60, 1);
                            $whmcsUrl2 = rtrim(\Illuminate\Database\Capsule\Manager::table('tblconfiguration')->where('setting', 'SystemURL')->value('value'), '/');
                            $pubkey2 = (!empty($serverDetails->ssh_pubkey)) ? $serverDetails->ssh_pubkey : ($settings['ssh_public_key'] ?? '');
                            $recoveryCmd = "curl -sL '{$whmcsUrl2}/modules/addons/fmh_monitor/agent_download.php?t='\"\$(date +%s)\" | bash -s -- --install --token='{$serverDetails->token}' --url='{$whmcsUrl2}' --env='" . ($serverDetails->environment ?? 'linux') . "' --auto-remediation='" . ($serverDetails->auto_remediation ?? 0) . "' --hmac-secret='" . ($serverDetails->hmac_secret ?? '') . "' --clean" . (!empty($pubkey2) ? " --ssh-public-key='" . addslashes($pubkey2) . "'" : '');
                        ?>
                        <div style="margin-top: 20px; background: rgba(239,68,68,0.08); border: 1px solid rgba(239,68,68,0.3); border-radius: 8px; padding: 20px 24px;">
                            <div style="display: flex; align-items: flex-start; gap: 14px;">
                                <i class="fa-solid fa-circle-exclamation" style="color: #ef4444; font-size: 28px; margin-top: 2px;"></i>
                                <div style="flex:1;">
                                    <h3 style="margin:0 0 6px 0; color: #fca5a5; font-size: 16px;">
                                        <?php if ($offMins > 120): ?>
                                            Agente offline há <?php echo $offHrs; ?> horas - Requer intervenção!
                                        <?php elseif ($offMins > 30): ?>
                                            Agente offline há <?php echo $offMins; ?> minutos - Recuperação necessária
                                        <?php else: ?>
                                            Agente offline há <?php echo $offMins; ?> min - Possível falha de configuração
                                        <?php endif; ?>
                                    </h3>
                                    <p style="margin:0 0 12px 0; color: #9ca3af; font-size: 13px;">
                                        O agente não está enviando métricas. Última comunicação: <?php echo $serverDetails->last_check ? date('d/m/Y H:i:s', strtotime($serverDetails->last_check)) : 'Nunca'; ?>.
                                        Causa provável: configuração corrompida ou arquivo de credenciais ausente.
                                    </p>
                                    <div style="display: flex; gap: 8px; align-items: stretch; flex-wrap: wrap;">
                                        <input type="text" readonly value="<?php echo htmlspecialchars($recoveryCmd); ?>" style="flex:1; min-width:280px; background: #000; color: #22c55e; border: 1px solid #333; border-radius: 6px; padding: 10px 14px; font-family: monospace; font-size: 12px;" onclick="this.select();document.execCommand('copy');fmhToast('Comando copiado! Execute no SSH do servidor.', 'success')">
                                        <button onclick="var i=this.parentElement.querySelector('input');i.select();document.execCommand('copy');fmhToast('Comando copiado! Execute no SSH do servidor.', 'success');" style="background:#ef4444; color:#fff; border:none; border-radius:6px; padding:10px 20px; cursor:pointer; font-weight:600; font-size:13px; white-space:nowrap;"><i class="fa-solid fa-copy"></i> Copiar Comando</button>
                                    </div>
                                    <div style="margin-top: 12px; color: #6b7280; font-size: 11px; line-height: 1.6;">
                                        <strong>Como usar:</strong> <code style="background:#111; padding:2px 6px; border-radius:3px;">1. SSH no servidor (<?php echo htmlspecialchars($serverDetails->hostname ?: $serverDetails->ip ?: 'IP'); ?>)</code> →
                                        <code style="background:#111; padding:2px 6px; border-radius:3px;">2. Cole o comando</code> →
                                        <code style="background:#111; padding:2px 6px; border-radius:3px;">3. Enter</code> →
                                        <span style="color:#22c55e;">Pronto, agente reinstalado e online!</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        <div style="display:flex; gap:15px; margin-top:10px; color:#9ca3af; font-size:13px; align-items:center;">
                            <span>
                                <i class="fa-solid fa-network-wired"></i> IP: <strong><?php echo $serverDetails->ip ?: '---'; ?></strong>
                                <?php if (isset($serverDetails->ipv6) && $serverDetails->ipv6): ?>
                                    <br><span style="font-size: 11px; margin-left: 18px; color: #6b7280;"><?php echo htmlspecialchars($serverDetails->ipv6); ?></span>
                                <?php endif; ?>
                            </span>
                            <span><?php if(!empty($srv->last_pwd_alert)): ?><div style="margin-bottom:8px;"><span class="fmh-status-badge" style="background:rgba(239, 68, 68, 0.1); color:#ef4444; border-color:rgba(239,68,68,0.3); padding:2px 6px; font-size:10px;"><i class="fa-solid fa-triangle-exclamation" style="margin-right:4px;"></i>Senha Root Alterada!</span></div><?php endif; ?><i class="fa-solid fa-server"></i> Hostname: <strong><?php echo $serverDetails->hostname ?: '---'; ?></strong></span>
                            <span><i class="fa-solid fa-layer-group"></i> Ambiente: <strong style="text-transform: capitalize; color: #93c5fd;"><?php echo htmlspecialchars($serverDetails->environment ?: 'Linux Puro'); ?></strong></span>
                            <span><i class="fa-solid fa-key"></i> Porta SSH: <strong><?php echo $serverDetails->ssh_port ?: '22'; ?></strong></span>
                            <?php if (!empty($serverDetails->hmac_secret)): ?>
                            <span style="color:#22c55e;"><i class="fa-solid fa-shield-check"></i> HMAC: <strong>Ativo</strong></span>
                            <?php else: ?>
                            <span style="color:#f59e0b;"><i class="fa-solid fa-shield"></i> HMAC: <strong>Pendente</strong></span>
                            <?php endif; ?>
                            <?php if (!empty($serverDetails->expected_ip)): ?>
                            <span style="color:#93c5fd;"><i class="fa-solid fa-lock"></i> IP Binding: <strong><?php echo htmlspecialchars($serverDetails->expected_ip); ?></strong></span>
                            <?php endif; ?>
                        </div>
                        <p class="fmh-page-subtitle" style="margin-top:10px;">Token: <code style="padding:4px 8px; font-size:12px;"><?php echo $serverDetails->token; ?></code></p>
                    </div>
                    <div class="fmh-header-flex" style="text-align: right;">
                        <button onclick="openSshKeysModal('<?php echo htmlspecialchars($serverDetails->ssh_keys ?? '', ENT_QUOTES, 'UTF-8'); ?>', '<?php echo htmlspecialchars($serverDetails->ssh_pubkey ?? '', ENT_QUOTES, 'UTF-8'); ?>')" class="fmh-btn" style="background: rgba(16, 185, 129, 0.2); color: #10b981; border: 1px solid rgba(16,185,129,0.4); margin-right: 10px;"><i class="fa-solid fa-key"></i> Chaves SSH Autorizadas</button>
                        <?php 
                        $keyPath = $serverDetails->ssh_key ?? '';
                        $keyExists = !empty($keyPath) && file_exists($keyPath);
                        $keyType = '';
                        if ($keyExists) {
                            $kc = @file_get_contents($keyPath);
                            if (strpos($kc, 'RSA') !== false) $keyType = 'RSA';
                            elseif (strpos($kc, 'ed25519') !== false) $keyType = 'ed25519 ⚠️';
                        }
                        if ($keyExists): ?>
                        <span style="font-size:10px; color:#22c55e; margin-right:10px;" title="Chave privada <?php echo $keyType; ?> armazenada em: <?php echo htmlspecialchars($keyPath); ?>"><i class="fa-solid fa-shield-check"></i> <?php echo $keyType ? 'Chave ' . $keyType : 'Chave recebida'; ?></span>
                        <?php elseif (!empty($serverDetails->ssh_keys)): ?>
                        <span style="font-size:10px; color:#f59e0b; margin-right:10px;" title="Agente enviou authorized_keys mas a chave privada nao foi salva. Execute o comando de instalacao 1x."><i class="fa-solid fa-triangle-exclamation"></i> Chave privada pendente</span>
                        <?php endif; ?>
                        <button onclick="document.getElementById('historyModal').style.display='flex';" class="fmh-btn" style="background: rgba(59, 130, 246, 0.2); color: #3b82f6; border: 1px solid rgba(59,130,246,0.4); margin-right: 10px;"><i class="fa-solid fa-clock-rotate-left"></i> Histórico de Eventos</button>
                        <form action="addonmodules.php?module=fmh_monitor&action=add_service" method="POST" style="display:inline-block;">
                            <input type="hidden" name="token" value="<?php echo generate_token('plain'); ?>">
                            <input type="hidden" name="name" value="Ping - <?php echo htmlspecialchars($serverDetails->name); ?>">
                            <input type="hidden" name="ip" value="<?php echo htmlspecialchars($serverDetails->ip); ?>">
                            <input type="hidden" name="type" value="PING">
                            <input type="hidden" name="port" value="0">
                            <input type="hidden" name="interval_minutes" value="1">
                            <input type="hidden" name="channels[]" value="whatsapp">
                            <button type="submit" class="fmh-btn" style="background: rgba(16, 185, 129, 0.2); color: #10b981; border: 1px solid rgba(16,185,129,0.4); margin-right: 10px;" onclick="return confirm('Deseja criar um Monitor de Ping Externo para o IP deste Servidor no Painel Geral?')"><i class="fa-solid fa-wifi"></i> Monitorar Ping Externo</button>
                        </form>
                        <form action="addonmodules.php?module=fmh_monitor&action=rotate_token" method="POST" style="display:inline-block;" onsubmit="return confirm('ATENÇÃO: Rotacionar o token invalidará o atual. O agente precisará ser reinstalado com o novo token. Continuar?')">
                            <input type="hidden" name="token" value="<?php echo generate_token('plain'); ?>">
                            <input type="hidden" name="server_id" value="<?php echo $serverDetails->id; ?>">
                            <button type="submit" class="fmh-btn" style="background: rgba(239, 68, 68, 0.2); color: #fca5a5; border: 1px solid rgba(239,68,68,0.4); margin-right: 10px;" title="<?php echo $serverDetails->status != 'UP' ? 'SERVIDOR OFFLINE: Rotacionar agora vai PIORAR. Reinstale o agente primeiro.' : 'Gera novo token e HMAC secret'; ?>" <?php echo $serverDetails->status != 'UP' ? 'disabled style="opacity:0.4;cursor:not-allowed;"' : ''; ?>><i class="fa-solid fa-rotate"></i> Rotacionar Token</button>
                            <?php if ($serverDetails->status != 'UP'): ?><span style="font-size:10px;color:#fca5a5;">Reinstale o agente primeiro</span><?php endif; ?>
                        </form>
                        <button onclick="document.getElementById('editModal').style.display='flex';" class="fmh-btn" style="background: rgba(255,255,255,0.1); color: #fff; margin-right: 10px;"><i class="fa-solid fa-pen"></i> Editar Agente</button>
                        <button onclick="openTerminal('<?php echo $serverDetails->id; ?>', '<?php echo htmlspecialchars($serverDetails->hostname ?: $serverDetails->ip ?: ''); ?>', '<?php echo htmlspecialchars($serverDetails->ssh_user ?? 'root'); ?>@<?php echo htmlspecialchars($serverDetails->ip ?: ''); ?>')" class="fmh-btn" style="background: rgba(6,182,212,0.2); color: #06b6d4; border: 1px solid rgba(6,182,212,0.4); margin-right: 6px;"><i class="fa-solid fa-terminal"></i> Terminal</button>
                        <button onclick="var c='ssh <?php echo htmlspecialchars($serverDetails->ssh_user ?? 'root'); ?>@<?php echo htmlspecialchars($serverDetails->ip ?: ($serverDetails->hostname ?: '')); ?> -p <?php echo (int)($serverDetails->ssh_port ?? 22); ?>';navigator.clipboard.writeText(c);fmhToast('SSH copiado! Cole no PuTTY/Bitvise/Terminal','success');" class="fmh-btn" style="background: rgba(245,158,11,0.2); color: #f59e0b; border: 1px solid rgba(245,158,11,0.4); margin-right: 10px; font-size: 11px;" title="Copia comando SSH para colar no seu terminal local">SSH Direto</button>
                        <?php if (!empty($serverDetails->machine_id) && $serverDetails->status != 'UP'): ?>
                        <a href="addonmodules.php?module=fmh_monitor&action=reset_machine_id&id=<?php echo $serverDetails->id; ?>" class="fmh-btn" style="background: rgba(245,158,11,0.2); color: #f59e0b; border: 1px solid rgba(245,158,11,0.4); margin-right: 10px; font-size: 12px;" title="Limpa o vinculo machine_id permitindo novo servidor usar este token"><i class="fa-solid fa-link-slash"></i> Resetar Machine ID</a>
                        <?php endif; ?>
                        <?php 
                        $keyPath = $serverDetails->ssh_key ?? '';
                        $keyExists = !empty($keyPath) && file_exists($keyPath);
                        $keyType = '';
                        if ($keyExists) {
                            $keyContent = @file_get_contents($keyPath);
                            if (strpos($keyContent, 'RSA') !== false) $keyType = 'RSA';
                            elseif (strpos($keyContent, 'ed25519') !== false) $keyType = 'ed25519 ⚠️';
                        }
                        if ($keyExists): ?>
                        <a href="addonmodules.php?module=fmh_monitor&action=auto_repair_agent&id=<?php echo $serverDetails->id; ?>" class="fmh-btn" style="background: rgba(34,197,94,0.2); color: #22c55e; border: 1px solid rgba(34,197,94,0.4); margin-right: 8px;" onclick="this.innerHTML='<i class=\'fa-solid fa-spinner fa-spin\'></i> Reparando...';" title="WHMCS faz SSH no servidor e reinstala o agente. Chave: <?php echo $keyType ?: 'OK'; ?>">🛠 <?php echo $serverDetails->status != 'UP' ? 'Auto-Reparar' : 'Reinstalar Agente'; ?></a>
                        <a href="addonmodules.php?module=fmh_monitor&action=reset_ssh_key&id=<?php echo $serverDetails->id; ?>" class="fmh-btn" style="background: rgba(245,158,11,0.15); color: #f59e0b; border: 1px solid rgba(245,158,11,0.3); margin-right: 10px; font-size: 11px; padding: 4px 8px;" onclick="return confirm('Deletar a chave SSH armazenada? O agente reenviara no proximo ciclo.')" title="Remove chave SSH armazenada. Agente reenvia nova chave no proximo sync."><i class="fa-solid fa-arrows-rotate"></i></a>
                        <?php elseif ($serverDetails->status != 'UP'): ?>
                        <span class="fmh-btn" style="background: rgba(245,158,11,0.15); color: #f59e0b; border: 1px solid rgba(245,158,11,0.3); margin-right: 10px; cursor: default; font-size: 11px;" title="Execute o comando de instalacao 1x no servidor para ativar"><i class="fa-solid fa-clock"></i> Aguardando chave SSH</span>
                        <?php endif; ?>
                        <button onclick="document.getElementById('installModal').style.display='flex';" class="fmh-btn fmh-btn-primary"><i class="fa-solid fa-terminal"></i> Comando de Instalação</button>
                    </div>
                </div>
                
                
                <!-- SSH Keys Modal -->
                <div id="sshKeysModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:9999; justify-content:center; align-items:center;">
                    <div style="background:#1e1e1e; padding:30px; border-radius:12px; width:700px; max-width:90%; color:#fff; border: 1px solid #333; position:relative;">
                        <h2 style="margin-top:0; border-bottom:1px solid #333; padding-bottom:15px; font-size:18px; display:flex; align-items:center; gap:10px;">
                            <i class="fa-solid fa-key" style="color:#10b981;"></i> Chaves SSH Autorizadas (authorized_keys)
                        </h2>
                        
                        <div id="sshKeysList" style="background:#050505; border: 1px solid #333; border-radius:8px; padding:15px; margin-top:20px; max-height: 400px; overflow-y: auto;"></div><div style="display:none;">  <div style="background:#111; border: 1px solid #333; border-radius:8px; padding:15px; margin-top:20px; font-family:monospace; font-size:13px; max-height: 400px; overflow-y: auto;">
                            <?php 
                            if (!empty($serverDetails->ssh_keys)) {
                                $keys = json_decode($serverDetails->ssh_keys, true);
                                if (is_array($keys) && count($keys) > 0) {
                                    foreach ($keys as $key) {
                                        // Highlight comment (last part of the key)
                                        $parts = explode(' ', trim($key));
                                        $comment = count($parts) > 2 ? htmlspecialchars(end($parts)) : 'Sem Comentário';
                                        $type = count($parts) > 0 ? htmlspecialchars($parts[0]) : '';
                                        echo "<div style='padding:10px; border-bottom:1px solid #222; margin-bottom:10px; word-break:break-all;'>";
                                        echo "<div style='color:#10b981; font-weight:bold; margin-bottom:5px;'><i class='fa-solid fa-tag'></i> " . $comment . "</div>";
                                        echo "<div style='color:#6b7280; font-size:11px;'>" . htmlspecialchars(substr($key, 0, 80)) . "...</div>";
                                        echo "</div>";
                                    }
                                } else {
                                    echo "<div style='color:#9ca3af; text-align:center; padding: 20px;'>Nenhuma chave SSH encontrada no servidor.</div>";
                                }
                            } else {
                                echo "<div style='color:#9ca3af; text-align:center; padding: 20px;'>Nenhuma chave SSH recebida ainda. O agente deve enviar na próxima sincronização.</div>";
                            }
                            ?>
                        </div>
                        </div>
                        
                        <div style="text-align:right; margin-top:20px;">
                            <button onclick="document.getElementById('sshKeysModal').style.display='none';" class="fmh-btn" style="background:#4b5563; color:#fff;">Fechar</button>
                        </div>
                    </div>
                </div>
<!-- History Modal -->
                <div id="historyModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:9999; justify-content:center; align-items:center;">
                    <div style="background:#0a0a0a; border: 1px solid #222; padding:30px; border-radius:8px; width: 100%; max-width:800px; max-height: 80vh; display: flex; flex-direction: column;">
                        <h3 style="color:#fff; margin-top:0; flex-shrink: 0;"><i class="fa-solid fa-clock-rotate-left"></i> Histórico de Eventos</h3>
                        <p style="color:#9ca3af; flex-shrink: 0;">Últimas 100 ocorrências registradas pelo coletor deste servidor.</p>
                        
                        <div style="flex-grow: 1; overflow-y: auto; margin: 15px 0; border: 1px solid #222; border-radius: 6px;">
                            <table class="fmh-table">
                                <thead>
                                    <tr>
                                        <th>Data/Hora</th>
                                        <th>Evento</th>
                                        <th>Detalhes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($serverLogs)): ?>
                                        <tr><td colspan="3" style="text-align:center; color:#9ca3af; padding: 20px;">Nenhum evento registrado ainda.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($serverLogs as $l): ?>
                                            <tr>
                                                <td style="color: #9ca3af; font-size: 13px;"><?php echo date('d/m/Y H:i', strtotime($l->created_at)); ?></td>
                                                <td>
                                                    <?php if ($l->event_type == 'UP'): ?>
                                                        <span class="fmh-status-badge fmh-badge-up"><i class="fa-solid fa-arrow-up" style="margin-right: 4px;"></i> RECUPERADO</span>
                                                    <?php elseif ($l->event_type == 'DOWN'): ?>
                                                        <span class="fmh-status-badge fmh-badge-down"><i class="fa-solid fa-arrow-down" style="margin-right: 4px;"></i> QUEDA</span>
                                                    <?php else: ?>
                                                        <span class="fmh-status-badge fmh-badge-warning"><i class="fa-solid fa-triangle-exclamation" style="margin-right: 4px;"></i> ALERTA</span>
                                                <?php endif; ?>
                                                <td style="font-size: 13px; color: #9ca3af;"><?php echo htmlspecialchars($l->details); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div style="text-align:right; flex-shrink: 0; padding-top: 15px;">
                            <button onclick="document.getElementById('historyModal').style.display='none';" class="fmh-btn" style="background:#222; color:#fff;">Fechar</button>
                        </div>
                    </div>
                </div>
                
                <!-- Install Modal -->
                <div id="installModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:9999; justify-content:center; align-items:center;">
                    <div style="background:#0a0a0a; border: 1px solid #222; padding:30px; border-radius:8px; width: 100%; max-width:600px;">
                        <h3 style="color:#fff; margin-top:0;"><i class="fa-solid fa-terminal"></i> Instalar Agente (Via SSH / Root)</h3>
                        <p style="color:#9ca3af;">Copie o comando abaixo e rode no terminal do seu servidor. Ele instalará o coletor leve que enviará métricas a cada minuto.</p>
                        
                        <?php 
                        $whmcsUrl = \Illuminate\Database\Capsule\Manager::table('tblconfiguration')->where('setting', 'SystemURL')->value('value');
                        $whmcsUrl = rtrim($whmcsUrl, '/');
                        ?>
                        <div style="background:#000; padding:15px; border:1px solid #333; border-radius:6px; margin: 20px 0; font-family:monospace; color:#22c55e; word-break:break-all;">
                            curl -sL <?php echo $whmcsUrl; ?>/modules/addons/fmh_monitor/agent_download.php?t=<?php echo time(); ?> | bash -s -- --install --token="<?php echo $serverDetails->token; ?>" --url="<?php echo $whmcsUrl; ?>" --env="<?php echo $serverDetails->environment; ?>" --auto-remediation="<?php echo $serverDetails->auto_remediation; ?>" --hmac-secret="<?php echo $serverDetails->hmac_secret ?? ''; ?>" --clean<?php if (!empty($serverDetails->ssh_pubkey)) echo ' --ssh-public-key="' . htmlspecialchars($serverDetails->ssh_pubkey) . '"'; elseif (!empty($settings['ssh_public_key'])) echo ' --ssh-public-key="' . htmlspecialchars($settings['ssh_public_key']) . '"'; ?>
                        </div>
                        
                        <div style="text-align:right;">
                            <button onclick="document.getElementById('installModal').style.display='none';" class="fmh-btn" style="background:#222; color:#fff;">Fechar</button>
                        </div>
                    </div>
                </div>

                <!-- Edit Modal -->
                <div id="editModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:9999; justify-content:center; align-items:center;">
                    <div style="background:#0a0a0a; border: 1px solid #222; padding:30px; border-radius:8px; width: 100%; max-width:550px;">
                        <h3 style="color:#fff; margin-top:0;"><i class="fa-solid fa-pen"></i> Editar Configurações do Agente</h3>
                        <form method="post" action="addonmodules.php?module=fmh_monitor">
                            <input type="hidden" name="action" value="edit_server_agent">
                            <input type="hidden" name="server_id" value="<?php echo $serverDetails->id; ?>">
                            <input type="hidden" name="token" value="<?php echo generate_token('plain'); ?>">
                            
                            <div class="fmh-form-group">
                                <label>Ambiente Base</label>
                                <select name="environment" class="fmh-form-control" required>
                                    <option value="linux" <?php echo $serverDetails->environment == 'linux' ? 'selected' : ''; ?>>Linux Genérico / Sem Painel</option>
                                    <option value="cpanel" <?php echo $serverDetails->environment == 'cpanel' ? 'selected' : ''; ?>>cPanel / WHM</option>
                                    <option value="aapanel" <?php echo $serverDetails->environment == 'aapanel' ? 'selected' : ''; ?>>aaPanel</option>
                                </select>
                            </div>
                            
                            <div class="fmh-form-group">
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                    <input type="checkbox" name="auto_remediation" value="1" style="width:16px; height:16px; accent-color: #3b82f6;" <?php echo $serverDetails->auto_remediation ? 'checked' : ''; ?>>
                                    Ativar Auto-Cura (Remediation)
                                </label>
                            </div>
                            
                            <div class="fmh-form-group">
                                <label>IPs Lista Branca SSH</label>
                                <input type="text" name="ssh_whitelist_ips" class="fmh-form-control" value="<?php echo htmlspecialchars($serverDetails->ssh_whitelist_ips ?? ''); ?>" placeholder="Ex: 142.4.222.112, 192.168.1.10">
                            </div>
                            
                            <?php if (!empty($serverDetails->ssh_key)): ?>
                            <div class="fmh-form-group" style="border: 1px solid rgba(34,197,94,0.2); background: rgba(34,197,94,0.03); border-radius: 6px; padding: 12px;">
                                <label style="color: #22c55e;"><i class="fa-solid fa-wrench"></i> Auto-Reparo SSH: Configurado</label>
                                <p style="margin: 4px 0 0; font-size: 11px; color: #9ca3af;">Chave SSH gerada pelo agente. Usuario: <?php echo htmlspecialchars($serverDetails->ssh_user ?? 'root'); ?>. O botao "Auto-Reparar" esta ativo.</p>
                            </div>
                            <?php else: ?>
                            <div class="fmh-form-group" style="border: 1px solid rgba(245,158,11,0.2); background: rgba(245,158,11,0.03); border-radius: 6px; padding: 12px;">
                                <label style="color: #f59e0b;"><i class="fa-solid fa-wrench"></i> Auto-Reparo SSH: Aguardando</label>
                                <p style="margin: 4px 0 0; font-size: 11px; color: #9ca3af;">Aguardando o agente enviar a chave SSH no primeiro sync.</p>
                            </div>
                            <?php endif; ?>
                            
                            <div style="text-align:right; margin-top: 25px;">
                                <button type="button" onclick="document.getElementById('editModal').style.display='none';" class="fmh-btn" style="background:#222; color:#fff; margin-right:10px;">Cancelar</button>
                                <button type="submit" class="fmh-btn fmh-btn-primary">Salvar Alterações</button>
                            </div>
                        </form>
                    </div>
                </div>

                <?php 
                $latest = !empty($serverMetrics) ? $serverMetrics[count($serverMetrics) - 1] : null; 
                ?>
                <div class="fmh-stats-grid" style="grid-template-columns: repeat(4, 1fr);">
                    <div class="fmh-stat-card">
                        <div>
                            <div class="fmh-stat-info"><h4>CPU Load (1m)</h4>
                            <h2><?php echo $latest ? number_format($latest->load_1, 2) : '--'; ?></h2>
                            <span style="font-size: 11px; color: #9ca3af; margin-top: 4px; display: block;"><i class="fa-solid fa-microchip"></i> Uso: <?php echo $latest ? number_format(isset($latest->cpu_usage) && $latest->cpu_usage !== null ? $latest->cpu_usage : 0, 2) : '--'; ?>%</span>
                            </div>
                        </div>
                        <div class="fmh-stat-icon"><i class="fa-solid fa-microchip"></i></div>
                    </div>
                    <div class="fmh-stat-card">
                        <div>
                            <div class="fmh-stat-info"><h4>Memória RAM</h4>
                            <h2><?php echo $latest ? number_format(($latest->mem_used/$latest->mem_total)*100, 1) . '%' : '--'; ?></h2>
                            <p style="margin:0; font-size:12px; color:#6b7280; margin-top:4px;">Usado: <?php echo $latest ? $latest->mem_used : 0; ?>MB</p>
                            </div>
                        </div>
                        <div class="fmh-stat-icon"><i class="fa-solid fa-memory"></i></div>
                    </div>
                    <div class="fmh-stat-card">
                        <div>
                            <div class="fmh-stat-info"><h4>Uso de Disco (Espaço)</h4>
                            <h2><?php echo $latest ? $latest->disk_used_pct . '%' : '--'; ?></h2>
                            <p style="margin:0; font-size:12px; color:#6b7280; margin-top:4px;">Total: <?php echo $latest ? htmlspecialchars($latest->disk_total) : '/'; ?></p></div>
                        </div>
                        <div class="fmh-stat-icon"><i class="fa-solid fa-hard-drive"></i></div>
                    </div>
                    <div class="fmh-stat-card">
                        <div>
                            <div class="fmh-stat-info"><h4>Uso de Inodes</h4>
                            <h2><?php echo $latest && $latest->inodes_used_pct ? $latest->inodes_used_pct . '%' : '--'; ?></h2></div>
                        </div>
                        <div class="fmh-stat-icon"><i class="fa-solid fa-folder-tree"></i></div>
                    </div>
                    <?php
                    $healthScore = ($latest && isset($latest->health_score)) ? $latest->health_score : null;
                    $healthColor = '#22c55e';
                    $healthLabel = 'Saudável';
                    if ($healthScore !== null) {
                        if ($healthScore >= 90) { $healthColor = '#22c55e'; $healthLabel = 'Excelente'; }
                        elseif ($healthScore >= 75) { $healthColor = '#84cc16'; $healthLabel = 'Saudável'; }
                        elseif ($healthScore >= 50) { $healthColor = '#f59e0b'; $healthLabel = 'Atenção'; }
                        else { $healthColor = '#ef4444'; $healthLabel = 'Crítico'; }
                    }
                    ?>
                    <div class="fmh-stat-card" style="border-left: 3px solid <?php echo $healthColor; ?>;">
                        <div>
                            <div class="fmh-stat-info"><h4>Health Score</h4>
                            <h2 style="color: <?php echo $healthColor; ?>;"><?php echo $healthScore !== null ? $healthScore . '/100' : '--'; ?></h2>
                            <span style="font-size:11px; color:<?php echo $healthColor; ?>;"><?php echo $healthScore !== null ? $healthLabel : 'Aguardando...'; ?></span>
                            </div>
                        </div>
                        <div class="fmh-stat-icon" style="color: <?php echo $healthColor; ?>;"><i class="fa-solid fa-heart-pulse"></i></div>
                    </div>
                    <?php $bwRX = ($latest && isset($latest->net_bandwidth_rx)) ? $latest->net_bandwidth_rx : null; $bwTX = ($latest && isset($latest->net_bandwidth_tx)) ? $latest->net_bandwidth_tx : null; $spRX = ($latest && isset($latest->net_speed_rx)) ? $latest->net_speed_rx : null; $spTX = ($latest && isset($latest->net_speed_tx)) ? $latest->net_speed_tx : null; ?>
                    <div class="fmh-stat-card">
                        <div>
                            <div class="fmh-stat-info"><h4>Rede (Velocidade)</h4>
                            <?php if($spRX !== null): ?>
                            <h2 style="font-size:16px;"><span style="color:#60a5fa;">↓<?php echo ($spRX > 1048576) ? number_format($spRX/1048576, 1).' MB/s' : (($spRX > 1024) ? number_format($spRX/1024, 1).' KB/s' : $spRX.' B/s'); ?></span> <span style="color:#f87171;">↑<?php echo ($spTX > 1048576) ? number_format($spTX/1048576, 1).' MB/s' : (($spTX > 1024) ? number_format($spTX/1024, 1).' KB/s' : $spTX.' B/s'); ?></span></h2>
                            <?php elseif($bwRX !== null): ?>
                            <h2 style="font-size:13px; color:#f59e0b;">⏳ Aguardando speed...</h2>
                            <?php else: ?>
                            <h2 style="font-size:13px; color:#9ca3af;">Aguardando dados...</h2>
                            <?php endif; ?>
                            <?php if($bwRX !== null): ?><p style="margin:4px 0 0; font-size:10px; color:#6b7280;">Total: ↓<?php echo number_format($bwRX/1073741824, 1); ?>GB ↑<?php echo number_format($bwTX/1073741824, 1); ?>GB</p><?php endif; ?>
                            </div>
                        </div>
                        <div class="fmh-stat-icon"><i class="fa-solid fa-gauge-high"></i></div>
                    </div>
                </div>

                <?php
                $labels = [];
                $cpuData = [];
                $ramData = [];
                if (!empty($serverMetrics)) {
                    $metricsArray = [];
                    foreach ($serverMetrics as $m) {
                        $metricsArray[] = $m;
                    }
                    $reversed = array_reverse($metricsArray);
                    foreach ($reversed as $m) {
                        $labels[] = date('H:i', strtotime($m->created_at));
                        $cpuData[] = round($m->load_1, 2);
                        if ($m->mem_total > 0) {
                            $ramData[] = round(($m->mem_used / $m->mem_total) * 100, 1);
                        } else {
                            $ramData[] = 0;
                        }
                    }
                }
                ?>
                <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
                <div class="fmh-panel" style="margin-bottom: 30px;">
                    <h3 class="fmh-panel-title"><i class="fa-solid fa-chart-area"></i> Histórico de Consumo (Últimas 24 Horas)</h3>
                    <div style="height: 300px; width: 100%;">
                        <canvas id="serverChart"></canvas>
                    </div>
                </div>
                <script>
                const ctx = document.getElementById('serverChart').getContext('2d');
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: <?php echo json_encode($labels); ?>,
                        datasets: [
                            {
                                label: 'CPU Load (1m)',
                                data: <?php echo json_encode($cpuData); ?>,
                                borderColor: '#3b82f6',
                                backgroundColor: 'rgba(59, 130, 246, 0.08)',
                                borderWidth: 1.5,
                                fill: true,
                                tension: 0.3,
                                pointRadius: 0,
                                pointHitRadius: 8
                            },
                            {
                                label: 'RAM (%)',
                                data: <?php echo json_encode($ramData); ?>,
                                borderColor: '#10b981',
                                backgroundColor: 'rgba(16, 185, 129, 0.05)',
                                borderWidth: 1.5,
                                fill: true,
                                tension: 0.3,
                                pointRadius: 0,
                                pointHitRadius: 8
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: { intersect: false, mode: 'index' },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: { color: 'rgba(255,255,255,0.04)' },
                                ticks: { color: '#9ca3af', font: { size: 10 } }
                            },
                            x: {
                                grid: { display: false },
                                ticks: { color: '#9ca3af', maxTicksLimit: 12, font: { size: 10 }, autoSkip: true }
                            }
                        },
                        plugins: {
                            legend: { labels: { color: '#a1a1aa', usePointStyle: true, padding: 20, font: { size: 11 } } },
                            tooltip: { backgroundColor: '#1e1e1e', titleColor: '#fff', bodyColor: '#d1d5db', borderColor: '#333', borderWidth: 1 }
                        }
    }
});
</script>

<!-- TERMINAL MODAL -->
<div id="terminalModal" class="fmh-modal-overlay" style="display:none;" onclick="if(event.target===this)closeTerminal()">
    <div style="background:var(--space-dark); border:1px solid var(--glass-border); border-radius:14px; width:95%; max-width:900px; height:500px; display:flex; flex-direction:column; box-shadow:var(--shadow-lg);">
        <div style="display:flex; justify-content:space-between; align-items:center; padding:12px 18px; border-bottom:1px solid var(--glass-border); background:var(--space-light); border-radius:14px 14px 0 0;">
            <div style="display:flex; align-items:center; gap:8px;">
                <div style="display:flex; gap:6px;">
                    <div onclick="closeTerminal()" style="width:12px;height:12px;border-radius:50%;background:#ef4444;cursor:pointer;"></div>
                    <div style="width:12px;height:12px;border-radius:50%;background:#f59e0b;"></div>
                    <div style="width:12px;height:12px;border-radius:50%;background:#22c55e;"></div>
                </div>
                <span style="font-size:12px; color:var(--text-dim); margin-left:12px;" id="terminalTitle">Terminal</span>
            </div>
            <div style="display:flex; gap:6px;">
                <button onclick="terminalQuick('uptime')" class="fmh-btn fmh-btn-xs" style="background:var(--space-hover);color:var(--text-dim);">uptime</button>
                <button onclick="terminalQuick('df -h')" class="fmh-btn fmh-btn-xs" style="background:var(--space-hover);color:var(--text-dim);">df -h</button>
                <button onclick="terminalQuick('free -m')" class="fmh-btn fmh-btn-xs" style="background:var(--space-hover);color:var(--text-dim);">free -m</button>
                <button onclick="terminalQuick('htop')" class="fmh-btn fmh-btn-xs" style="background:var(--space-hover);color:var(--text-dim);">htop</button>
                <button onclick="terminalQuick('who')" class="fmh-btn fmh-btn-xs" style="background:var(--space-hover);color:var(--text-dim);">who</button>
                <button onclick="terminalQuick('last -5')" class="fmh-btn fmh-btn-xs" style="background:var(--space-hover);color:var(--text-dim);">last</button>
                <button onclick="terminalQuick('ps aux --sort=-%cpu | head -8')" class="fmh-btn fmh-btn-xs" style="background:var(--space-hover);color:var(--text-dim);">ps</button>
                <button onclick="terminalQuick('ss -tulpn')" class="fmh-btn fmh-btn-xs" style="background:var(--space-hover);color:var(--text-dim);">ports</button>
            </div>
        </div>
        <div id="terminalOutput" style="flex:1; overflow-y:auto; padding:14px 18px; font-family:'JetBrains Mono','SF Mono','Consolas',monospace; font-size:12px; color:var(--accent-emerald); background:#0a0a0f; line-height:1.6; white-space:pre-wrap; word-break:break-all;"></div>
        <div style="display:flex; align-items:center; padding:8px 14px; border-top:1px solid var(--glass-border); background:var(--space-light); border-radius:0 0 14px 14px;">
            <span style="color:var(--accent-emerald); font-family:monospace; font-size:12px; margin-right:8px;" id="terminalPrompt">$</span>
            <input type="text" id="terminalInput" placeholder="Comando..." style="flex:1; background:transparent; border:none; color:var(--text-primary); font-family:monospace; font-size:12px; outline:none;" onkeydown="terminalKey(event)">
            <span style="color:var(--text-dim); font-size:10px; margin-left:8px;">⏎ Enter | ↑↓ hist</span>
        </div>
    </div>
</div>

<script>
var termServerId = null, termHistory = [], termHistIdx = -1;
function openTerminal(id, hostname, userhost) {
    termServerId = id;
    var title = hostname ? hostname + ' (' + userhost + ')' : userhost;
    document.getElementById('terminalTitle').textContent = 'Terminal: ' + title;
    document.getElementById('terminalPrompt').textContent = userhost ? '[' + userhost + ']$' : '$';
    document.getElementById('terminalOutput').innerHTML = '<span style="color:var(--text-dim)">Conectado ao servidor. Digite um comando.</span>\n';
    document.getElementById('terminalInput').value = '';
    document.getElementById('terminalModal').style.display = 'flex';
    document.getElementById('terminalInput').focus();
    termHistory = JSON.parse(localStorage.getItem('fmh_terminal_' + id) || '[]');
    termHistIdx = termHistory.length;
}
function closeTerminal() { document.getElementById('terminalModal').style.display = 'none'; termServerId = null; }
function terminalPrint(msg, color) {
    var out = document.getElementById('terminalOutput');
    out.innerHTML += '<div style="color:' + (color || 'var(--accent-cyan)') + '">' + msg + '</div>';
    out.scrollTop = out.scrollHeight;
}
function terminalKey(e) {
    if (e.key === 'Enter') { var cmd = document.getElementById('terminalInput').value.trim(); if (cmd) terminalExec(cmd); }
    else if (e.key === 'ArrowUp') { e.preventDefault(); if (termHistIdx > 0) { termHistIdx--; document.getElementById('terminalInput').value = termHistory[termHistIdx] || ''; } }
    else if (e.key === 'ArrowDown') { e.preventDefault(); if (termHistIdx < termHistory.length - 1) { termHistIdx++; document.getElementById('terminalInput').value = termHistory[termHistIdx] || ''; } else { termHistIdx = termHistory.length; document.getElementById('terminalInput').value = ''; } }
    else if (e.key === 'l' && e.ctrlKey) { e.preventDefault(); document.getElementById('terminalOutput').innerHTML = ''; }
}
function terminalQuick(cmd) { document.getElementById('terminalInput').value = cmd; terminalExec(cmd); }
function terminalExec(cmd) {
    // Auto-convert for commands that need batch mode even with PTY
    if (cmd === 'top') cmd = 'top -bn1';
    if (cmd === 'clear' || cmd === 'cls') { document.getElementById('terminalOutput').innerHTML = ''; return; }
    
    var out = document.getElementById('terminalOutput');
    var inp = document.getElementById('terminalInput');
    var prompt = document.getElementById('terminalPrompt').textContent;
    out.innerHTML += '<div style="color:var(--accent-cyan)">' + prompt + ' ' + cmd + '</div>';
    inp.value = '';
    termHistory.push(cmd);
    if (termHistory.length > 200) termHistory.shift();
    termHistIdx = termHistory.length;
    localStorage.setItem('fmh_terminal_' + termServerId, JSON.stringify(termHistory));
    
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'addonmodules.php?module=fmh_monitor&action=remote_exec', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function() {
        try {
            var r = JSON.parse(xhr.responseText);
            if (r.error) { out.innerHTML += '<span style="color:var(--accent-rose)">' + r.error + '</span>\n'; }
            else { out.innerHTML += r.output + '\n'; }
        } catch(e) {
            out.innerHTML += '<span style="color:var(--accent-rose)">Erro: ' + xhr.responseText.substring(0, 200) + '</span>\n';
        }
        out.scrollTop = out.scrollHeight;
        inp.focus();
    };
    xhr.onerror = function() { out.innerHTML += '<span style="color:var(--accent-rose)">Erro de rede</span>\n'; out.scrollTop = out.scrollHeight; inp.focus(); };
    xhr.send('token=' + encodeURIComponent('<?php echo generate_token('plain'); ?>') + '&server_id=' + termServerId + '&command=' + encodeURIComponent(cmd));
}
</script>

                <div class="fmh-tabs" style="margin-bottom: 20px; border-bottom: 1px solid #1f2937; display: flex; gap: 20px;">
                    <a href="javascript:void(0)" onclick="fmhSwitchServerTab('logs')" id="tab-btn-logs" style="padding: 10px 15px; color: #fff; text-decoration: none; border-bottom: 2px solid #8b5cf6; font-weight: 600;"><i class="fa-solid fa-list-check"></i> Histórico de Alertas</a>
                    <a href="javascript:void(0)" onclick="fmhSwitchServerTab('ssh')" id="tab-btn-ssh" style="padding: 10px 15px; color: #9ca3af; text-decoration: none; border-bottom: 2px solid transparent; font-weight: 600;"><i class="fa-solid fa-shield-halved"></i> Logins SSH</a>
                    <a href="javascript:void(0)" onclick="fmhSwitchServerTab('audit')" id="tab-btn-audit" style="padding: 10px 15px; color: #9ca3af; text-decoration: none; border-bottom: 2px solid transparent; font-weight: 600;"><i class="fa-solid fa-terminal"></i> Histórico de Comandos</a>
                    <span style="flex:1;"></span>
                    <a href="addonmodules.php?module=fmh_monitor&action=clear_server_logs&id=<?php echo $serverDetails->id; ?>&type=logs" class="fmh-btn" style="background: rgba(239,68,68,0.1); color: #fca5a5; border-color: rgba(239,68,68,0.3); font-size: 11px; padding: 4px 10px; opacity: 0;" id="clear-logs-btn" onclick="return confirm('Apagar todo histórico de alertas deste servidor?')"><i class="fa-solid fa-broom"></i> Limpar</a>
                </div>

                <!-- TAB CONTENT: LOGS -->
                <div id="tab-content-logs" class="server-tab-content" style="display: block;">
                    <div style="text-align:right; margin-bottom:8px;">
                        <a href="addonmodules.php?module=fmh_monitor&action=clear_server_logs&id=<?php echo $serverDetails->id; ?>&type=logs" class="fmh-btn" style="background: rgba(239,68,68,0.1); color: #fca5a5; border-color: rgba(239,68,68,0.3); font-size: 11px; padding: 4px 10px;" onclick="return confirm('Apagar todo histórico de alertas deste servidor?')"><i class="fa-solid fa-broom"></i> Limpar Histórico</a>
                    </div>
                    <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.05); border-radius: 8px; overflow: hidden;">
                        <table class="fmh-table" style="margin: 0; width: 100%;">
                            <thead>
                                <tr>
                                    <th>Data/Hora</th>
                                    <th>Evento</th>
                                    <th>Detalhes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($serverLogs)): ?>
                                    <tr><td colspan="3" style="text-align:center; padding:20px; color:#6b7280;">Nenhum evento registrado recentemente.</td></tr>
                                <?php else: ?>
                                    <?php foreach($serverLogs as $log): ?>
                                        <tr>
                                            <td style="color:#9ca3af; width:150px;"><?php echo date('d/m/Y H:i', strtotime($log->created_at)); ?></td>
                                            <td>
                                                <?php if($log->event_type == 'UP'): ?>
                                                    <span style="color: #22c55e; font-size: 11px; font-weight: 600; padding: 2px 6px; background: rgba(34,197,94,0.1); border-radius: 4px;">CONECTADO</span>
                                                <?php elseif($log->event_type == 'DOWN'): ?>
                                                    <span style="color: #ef4444; font-size: 11px; font-weight: 600; padding: 2px 6px; background: rgba(239,68,68,0.1); border-radius: 4px;">OFFLINE</span>
                                                <?php elseif($log->event_type == 'ALERTA'): ?>
                                                    <span style="color: #f59e0b; font-size: 11px; font-weight: 600; padding: 2px 6px; background: rgba(245,158,11,0.1); border-radius: 4px;">SOBRECARGA</span>
                                                <?php elseif($log->event_type == 'AUTO-CURA'): ?>
                                                    <span style="color: #8b5cf6; font-size: 11px; font-weight: 600; padding: 2px 6px; background: rgba(139,92,246,0.1); border-radius: 4px;">REMEDIADO</span>
                                                <?php else: ?>
                                                    <span style="color: #9ca3af; font-size: 11px; font-weight: 600; padding: 2px 6px; background: rgba(255,255,255,0.1); border-radius: 4px;"><?php echo htmlspecialchars($log->event_type); ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="color:#e5e7eb;"><?php echo htmlspecialchars($log->details); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                </div>

                <div id="tab-content-ssh" class="server-tab-content" style="display: none;">
                <div class="fmh-card">
                    <div class="fmh-card-header" style="display: flex; justify-content: space-between; align-items: center;">
                        <h3 class="fmh-card-title"><i class="fa-solid fa-shield-halved"></i> Auditoria de Segurança: Logins SSH</h3>
                        <span style="display:flex; gap:10px; align-items:center;">
                            <a href="addonmodules.php?module=fmh_monitor&action=clear_server_logs&id=<?php echo $serverDetails->id; ?>&type=ssh" class="fmh-btn" style="background: rgba(239,68,68,0.1); color: #fca5a5; border-color: rgba(239,68,68,0.3); font-size: 11px; padding: 4px 10px;" onclick="return confirm('Apagar todos os logins SSH deste servidor?')"><i class="fa-solid fa-broom"></i> Limpar</a>
                            <span style="font-size: 11px; color: #9ca3af;">Últimos 30 Dias</span>
                        </span>
                    </div>
                    <div class="fmh-table-responsive">
                        <table class="fmh-table">
                            <thead>
                                <tr>
                                    <th>Data/Hora</th>
                                    <th>Usuário</th>
                                    <th>IP</th>
                                    <th>Localização</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($sshLogins)): ?>
                                    <tr><td colspan="5" style="text-align:center; padding:20px; color:#6b7280;">Nenhum login SSH registrado.</td></tr>
                                <?php else: ?>
                                    <?php foreach($sshLogins as $sl): ?>
                                        <tr>
                                            <td style="color:#9ca3af;"><?php echo date('d/m/Y H:i:s', strtotime($sl->created_at)); ?></td>
                                            <td style="color:#e5e7eb; font-weight:600;"><i class="fa-solid fa-user-tie"></i> <?php echo htmlspecialchars($sl->user); ?></td>
                                            <td style="color:#93c5fd;"><?php echo htmlspecialchars($sl->ip); ?></td>
                                            <td style="color:#d1d5db;"><i class="fa-solid fa-location-dot" style="color:#ef4444;"></i> <?php echo htmlspecialchars($sl->geolocation); ?></td>
                                            <td>
                                                <?php if($sl->status == 'SUCCESS'): ?>
                                                    <span style="color: #22c55e; font-size: 11px; font-weight: 600; padding: 2px 6px; background: rgba(34,197,94,0.1); border-radius: 4px;">ACEITO</span>
                                                <?php else: ?>
                                                    <span style="color: #ef4444; font-size: 11px; font-weight: 600; padding: 2px 6px; background: rgba(239,68,68,0.1); border-radius: 4px;">FALHA</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                </div>

                <!-- TAB CONTENT: COMMANDS -->
                <div id="tab-content-audit" class="server-tab-content" style="display: none;">
                <div class="fmh-card">
                    <div class="fmh-card-header" style="display: flex; justify-content: space-between; align-items: center;">
                        <h3 class="fmh-card-title"><i class="fa-solid fa-terminal"></i> Histórico de Comandos (Live Audit)</h3>
                        <span style="display:flex; gap:10px; align-items:center;">
                            <a href="addonmodules.php?module=fmh_monitor&action=clear_server_logs&id=<?php echo $serverDetails->id; ?>&type=audit" class="fmh-btn" style="background: rgba(239,68,68,0.1); color: #fca5a5; border-color: rgba(239,68,68,0.3); font-size: 11px; padding: 4px 10px;" onclick="return confirm('Apagar todo histórico de comandos deste servidor?')"><i class="fa-solid fa-broom"></i> Limpar</a>
                            <span style="font-size: 11px; color: #9ca3af;">Monitoramento de Root / Sudo</span>
                        </span>
                    </div>
                    <div class="fmh-table-responsive" style="background: #0f111a; border-radius: 8px; border: 1px solid #1f2937;">
                        <table class="fmh-table" style="margin: 0; border: none; width: 100%;">
                            <thead style="background: rgba(255,255,255,0.02); border-bottom: 1px solid #1f2937;">
                                <tr>
                                    <th style="color: #6b7280; font-family: monospace;">TIMESTAMP</th>
                                    <th style="color: #6b7280; font-family: monospace;">USER@IP</th>
                                    <th style="color: #6b7280; font-family: monospace;">LOCALIZAÇÃO</th>
                                    <th style="color: #6b7280; font-family: monospace;">COMMAND EXECUTED</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($auditCommands)): ?>
                                    <tr><td colspan="4" style="text-align:center; padding:20px; color:#4b5563; font-family: monospace;">> Nenhum comando capturado.</td></tr>
                                <?php else: ?>
                                    <?php foreach($auditCommands as $cmd): ?>
                                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.02);">
                                            <td style="color:#6b7280; font-family: monospace; font-size: 12px; width: 1%; white-space: nowrap;">[<?php echo date('Y-m-d H:i:s', strtotime($cmd->created_at)); ?>]</td>
                                            <td style="color:#10b981; font-family: monospace; font-size: 13px; width: 1%; white-space: nowrap; padding-right: 15px;"><?php echo htmlspecialchars($cmd->user); ?>@<?php echo !empty($cmd->ip) ? htmlspecialchars($cmd->ip) : 'Local'; ?></td>
                                            <td style="color:#6b7280; font-family: monospace; font-size: 12px; width: 1%; white-space: nowrap; padding-right: 15px;"><i class="fa-solid fa-location-dot" style="color:#ef4444;"></i> <?php echo htmlspecialchars($cmd->geolocation ?? 'Desconhecido'); ?></td>
                                            <td style="color:#e5e7eb; font-family: monospace; font-size: 13px; word-break: break-word; white-space: pre-wrap;">$&nbsp; <?php 
                                                $cText = $cmd->command;
                                                $cText = preg_replace('/^\s*\d+\s+/', '', $cText);
                                                $cText = preg_replace('/^\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2}\s+/', '', $cText);
                                                echo htmlspecialchars($cText); 
                                            ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>



                        </div>

            <script>
            function fmhSwitchServerTab(tabName) {
                document.querySelectorAll('.server-tab-content').forEach(el => el.style.display = 'none');
                document.querySelectorAll('[id^="tab-btn-"]').forEach(el => {
                    el.style.borderBottom = '2px solid transparent';
                    el.style.color = '#9ca3af';
                });
                document.getElementById('tab-content-' + tabName).style.display = 'block';
                document.getElementById('tab-btn-' + tabName).style.borderBottom = '2px solid #8b5cf6';
                document.getElementById('tab-btn-' + tabName).style.color = '#fff';
                // Show/hide clear buttons based on active tab
                var types = ['logs','ssh','audit'];
                types.forEach(function(t) {
                    var btn = document.querySelector('[href*="clear_server_logs"][href*="type=' + t + '"]');
                    if (btn) btn.style.opacity = (t === tabName) ? '1' : '0';
                });
            }
            </script>
            
            <script>
            function openSshKeysModal(keysJson, whmcsPubkey) {
                var list = document.getElementById('sshKeysList');
                list.innerHTML = '';
                
                // Show WHMCS Auto-Repair key first (if exists)
                if (whmcsPubkey && whmcsPubkey.length > 50) {
                    var whmcsParts = whmcsPubkey.trim().split(' ');
                    var whmcsComment = whmcsParts.length > 2 ? whmcsParts[whmcsParts.length-1] : 'WHMCS Auto-Reparo';
                    var whmcsDiv = document.createElement('div');
                    whmcsDiv.style.cssText = 'padding:12px; margin-bottom:12px; border:1px solid rgba(34,197,94,0.3); border-radius:6px; background:rgba(34,197,94,0.05);';
                    whmcsDiv.innerHTML = '<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">' +
                        '<div style="color:#22c55e; font-weight:bold;"><i class="fa-solid fa-wrench"></i> WHMCS Auto-Reparo</div>' +
                        '<span style="font-size:10px; background:rgba(34,197,94,0.15); color:#22c55e; padding:2px 8px; border-radius:3px;">CHAVE EXCLUSIVA DESTE SERVIDOR</span></div>' +
                        '<div style="color:#9ca3af; font-size:11px; margin-bottom:4px;">' + whmcsComment + '</div>' +
                        '<div style="color:#6b7280; font-size:10px; word-break:break-all;">' + whmcsPubkey.substring(0, 120) + '...</div>';
                    list.appendChild(whmcsDiv);
                }
                
                // Show other SSH keys
                try {
                    var keys = JSON.parse(keysJson);
                    if (Array.isArray(keys) && keys.length > 0) {
                        var userKeysDiv = document.createElement('div');
                        userKeysDiv.innerHTML = '<div style="color:#a1a1aa; font-size:12px; font-weight:600; margin-bottom:8px; padding-bottom:8px; border-bottom:1px solid #222;"><i class="fa-solid fa-users"></i> Chaves de Usuários (authorized_keys)</div>';
                        list.appendChild(userKeysDiv);
                        
                        keys.forEach(function(key) {
                            var parts = key.trim().split(' ');
                            var type = parts[0] || 'ssh-rsa';
                            var comment = parts.length > 2 ? parts[parts.length-1] : 'Sem comentário';
                            var div = document.createElement('div');
                            div.style.cssText = 'padding:10px; border-bottom:1px solid #222; margin-bottom:8px; word-break:break-all;';
                            div.innerHTML = '<div style="color:#10b981; font-weight:bold; margin-bottom:5px;"><i class="fa-solid fa-tag"></i> ' + comment + '</div>' +
                                '<div style="color:#6b7280; font-size:11px;">' + key.substring(0, 80) + '...</div>';
                            list.appendChild(div);
                        });
                    } else if (!whmcsPubkey || whmcsPubkey.length < 50) {
                        list.innerHTML += '<div style="color:#9ca3af; text-align:center; padding:20px;">Nenhuma chave SSH encontrada.</div>';
                    }
                } catch(e) {
                    if (!whmcsPubkey || whmcsPubkey.length < 50) {
                        list.innerHTML += '<div style="color:#9ca3af; text-align:center; padding:20px;">Nenhuma chave SSH recebida ainda.</div>';
                    }
                }
                document.getElementById('sshKeysModal').style.display = 'flex';
            }
            
            document.addEventListener('DOMContentLoaded', function() {
                // Initialize DataTables if available in WHMCS, otherwise graceful degradation
                if (typeof jQuery !== 'undefined' && typeof jQuery.fn.DataTable !== 'undefined') {
                    jQuery('#tab-content-logs .fmh-table, #tab-content-ssh .fmh-table, #tab-content-audit .fmh-table').DataTable({
                        'pageLength': 10,
                        'lengthChange': false,
                        'language': {
                            'url': '//cdn.datatables.net/plug-ins/1.10.25/i18n/Portuguese-Brasil.json'
                        },
                        'order': [] // disable initial sort
                    });
                }
            });
            </script>

                
                <div class="fmh-grid-2" style="margin-bottom: 40px; grid-template-columns: 1fr 1fr; gap: 30px;">
                    
                    <div class="fmh-panel">
                        <h3 class="fmh-panel-title"><i class="fa-solid fa-list-check"></i> Status dos Serviços Base</h3>
                        <div class="fmh-table-responsive">
                            <table class="fmh-table">
                                <thead><tr><th>Serviço (Daemon)</th><th style="text-align:right;">Status</th></tr></thead>
                                <tbody>
                                    <?php 
                                    if ($latest && $latest->services_status) {
                                        $svcs = json_decode($latest->services_status, true);
                                        if (is_array($svcs) && count($svcs)>0) {
                                            foreach($svcs as $sName => $sStatus) {
                                                echo "<tr>";
                                                echo "<td><strong style='color:#f3f4f6;'>".htmlspecialchars($sName)."</strong></td>";
                                                echo "<td style='text-align:right;'>";
                                                if ($sStatus == 'UP') {
                                                    echo "<span style='color: #22c55e;'><i class='fa-solid fa-circle-check'></i> Rodando</span>";
                                                } else {
                                                    echo "<span style='color: #ef4444;'><i class='fa-solid fa-circle-xmark'></i> Caiu</span>";
                                                }
                                                echo "</td>";
                                                echo "</tr>";
                                            }
                                        } else {
                                            echo "<tr><td colspan='2' style='color:#6b7280;'>Nenhum serviço mapeado ainda.</td></tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='2' style='color:#6b7280;'>Aguardando agent enviar dados...</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>

                        <div style="margin-top: 25px;">
                            <h3 class="fmh-panel-title"><i class="fa-solid fa-envelope"></i> Monitoramento Anti-Spam</h3>
                            <div class="fmh-table-responsive">
                                <table class="fmh-table">
                                    <thead><tr><th>E-mails na Fila</th><th style="text-align:right;">Top Remetentes</th></tr></thead>
                                    <tbody>
                                        <tr>
                                            <td><strong style="font-size: 18px; color: <?php echo ($latest && $latest->mail_queue > 500) ? '#ef4444' : '#22c55e'; ?>;"><?php echo $latest ? $latest->mail_queue : '--'; ?></strong></td>
                                            <td style="text-align:right; font-size:12px; color:#9ca3af; line-height:1.6;">
                                                <?php
                                                if ($latest && !empty($latest->mail_top_senders)) {
                                                    $senders = explode('|', $latest->mail_top_senders);
                                                    foreach($senders as $s) {
                                                        echo htmlspecialchars($s) . "<br>";
                                                    }
                                                } else {
                                                    echo "Nenhum spam detectado.";
                                                }
                                                ?>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div style="margin-top: 25px;">
                            <h3 class="fmh-panel-title"><i class="fa-solid fa-network-wired"></i> Conexões de Rede Ativas</h3>
                            <div style="background: rgba(255,255,255,0.03); padding: 15px; border-radius: 8px; text-align: center;">
                                <h2 style="margin: 0; color: #f3f4f6;"><?php echo $latest ? number_format($latest->net_connections, 0, '', '.') : '--'; ?></h2>
                                <p style="margin: 4px 0 0 0; font-size: 12px; color: #6b7280;">Sessões TCP Estabelecidas</p>
                            </div>
                        </div>
                    </div>

                    <!-- NOVO: SSL Certificates + Docker + DB Health -->
                    <div class="fmh-panel" style="margin-bottom: 30px;">
                        <h3 class="fmh-panel-title" style="color: #93c5fd;"><i class="fa-solid fa-lock"></i> Certificados SSL</h3>
                        <div class="fmh-table-responsive">
                            <?php if(empty($sslCerts)): ?>
                                <p style="color:#6b7280; text-align:center; padding:20px;">Nenhum certificado SSL detectado. O agente v1.30+ coleta automaticamente.</p>
                            <?php else: ?>
                                <table class="fmh-table">
                                    <thead><tr><th>Domínio</th><th>Issuer</th><th>Expira em</th><th>Dias Restantes</th><th>Status</th></tr></thead>
                                    <tbody>
                                        <?php foreach($sslCerts as $cert): 
                                            $certColor = $cert->days_remaining > 30 ? '#22c55e' : ($cert->days_remaining > 14 ? '#f59e0b' : ($cert->days_remaining > 0 ? '#ef4444' : '#991b1b'));
                                            $certLabel = $cert->days_remaining > 30 ? 'OK' : ($cert->days_remaining > 14 ? 'Atenção' : ($cert->days_remaining > 0 ? 'Crítico' : 'EXPIRADO'));
                                        ?>
                                            <tr>
                                                <td><strong style="color:#e5e7eb;"><?php echo htmlspecialchars($cert->domain); ?></strong></td>
                                                <td style="color:#9ca3af; font-size:12px;"><?php echo htmlspecialchars($cert->issuer ?? '--'); ?></td>
                                                <td style="color:#d1d5db;"><?php echo date('d/m/Y', strtotime($cert->expiry_date)); ?></td>
                                                <td><strong style="color:<?php echo $certColor; ?>;"><?php echo $cert->days_remaining; ?> dias</strong></td>
                                                <td><span style="background:<?php echo $certColor; ?>20; color:<?php echo $certColor; ?>; padding:2px 8px; border-radius:4px; font-size:11px; font-weight:600;"><?php echo $certLabel; ?></span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php
                    $dockerData = $latest && $latest->docker_containers ? json_decode($latest->docker_containers, true) : null;
                    $dbHealth = $latest && $latest->db_health ? json_decode($latest->db_health, true) : null;
                    $kernelVulns = $latest && $latest->kernel_vulns ? json_decode($latest->kernel_vulns, true) : null;
                    ?>
                    <?php if ($dockerData || $dbHealth || $kernelVulns): ?>
                    <div class="fmh-grid-2" style="margin-bottom: 30px; gap: 30px;">
                        <?php if ($dockerData && is_array($dockerData) && count($dockerData) > 0): ?>
                        <div class="fmh-panel" style="margin-bottom: 0;">
                            <h3 class="fmh-panel-title" style="color: #60a5fa;"><i class="fa-brands fa-docker"></i> Docker Containers</h3>
                            <table class="fmh-table">
                                <thead><tr><th>Container</th><th>Status</th><th>Uptime</th></tr></thead>
                                <tbody>
                                <?php foreach($dockerData as $dc): ?>
                                    <tr>
                                        <td><strong style="color:#e5e7eb;"><?php echo htmlspecialchars($dc['name'] ?? '--'); ?></strong></td>
                                        <td><span style="color:<?php echo stripos($dc['status']??'', 'Up')!==false ? '#22c55e' : '#ef4444'; ?>;"><?php echo htmlspecialchars($dc['status'] ?? '--'); ?></span></td>
                                        <td style="color:#9ca3af; font-size:12px;"><?php echo htmlspecialchars($dc['uptime'] ?? '--'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                        <?php if ($dbHealth && is_array($dbHealth)): ?>
                        <div class="fmh-panel" style="margin-bottom: 0;">
                            <h3 class="fmh-panel-title" style="color: #10b981;"><i class="fa-solid fa-database"></i> Database Health (<?php echo htmlspecialchars($dbHealth['type'] ?? 'mysql'); ?>)</h3>
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                                <div style="text-align:center; padding:15px; background:rgba(255,255,255,0.02); border-radius:6px;">
                                    <h4 style="color:#9ca3af; font-size:11px; margin:0;">Status</h4>
                                    <h3 style="margin:8px 0 0; color:<?php echo ($dbHealth['status']??'DOWN')=='UP'?'#22c55e':'#ef4444'; ?>;"><?php echo htmlspecialchars($dbHealth['status'] ?? 'DOWN'); ?></h3>
                                </div>
                                <div style="text-align:center; padding:15px; background:rgba(255,255,255,0.02); border-radius:6px;">
                                    <h4 style="color:#9ca3af; font-size:11px; margin:0;">Conexões Ativas</h4>
                                    <h3 style="margin:8px 0 0; color:#e5e7eb;"><?php echo (int)($dbHealth['connections'] ?? 0); ?></h3>
                                </div>
                                <div style="text-align:center; padding:15px; background:rgba(255,255,255,0.02); border-radius:6px;">
                                    <h4 style="color:#9ca3af; font-size:11px; margin:0;">Slow Queries</h4>
                                    <h3 style="margin:8px 0 0; color:<?php echo ($dbHealth['slow_queries']??0)>100?'#ef4444':'#e5e7eb'; ?>;"><?php echo (int)($dbHealth['slow_queries'] ?? 0); ?></h3>
                                </div>
                                <div style="text-align:center; padding:15px; background:rgba(255,255,255,0.02); border-radius:6px;">
                                    <h4 style="color:#9ca3af; font-size:11px; margin:0;">Replicação</h4>
                                    <h3 style="margin:8px 0 0; color:#9ca3af;"><?php echo htmlspecialchars($dbHealth['replication_lag'] ?? 'N/A'); ?></h3>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if ($kernelVulns && is_array($kernelVulns) && count($kernelVulns) > 0): ?>
                        <div class="fmh-panel" style="margin-bottom: 0; border-top: 3px solid #ef4444; grid-column: 1 / -1;">
                            <h3 class="fmh-panel-title" style="color: #fca5a5;"><i class="fa-solid fa-bug"></i> Vulnerabilidades do Kernel</h3>
                            <?php foreach($kernelVulns as $kv): ?>
                                <div style="display:flex; justify-content:space-between; align-items:center; padding:12px; background:rgba(239,68,68,0.05); border:1px solid rgba(239,68,68,0.15); border-radius:6px; margin-bottom:8px;">
                                    <div>
                                        <strong style="color:#fca5a5;"><?php echo htmlspecialchars($kv['cve'] ?? ''); ?></strong>
                                        <p style="margin:4px 0 0; color:#9ca3af; font-size:12px;"><?php echo htmlspecialchars($kv['description'] ?? ''); ?></p>
                                    </div>
                                    <span style="background:<?php echo ($kv['severity']??'')=='CRITICAL'?'#991b1b':'#ef4444'; ?>20; color:<?php echo ($kv['severity']??'')=='CRITICAL'?'#fca5a5':'#ef4444'; ?>; padding:4px 10px; border-radius:4px; font-size:11px; font-weight:700;"><?php echo htmlspecialchars($kv['severity'] ?? 'HIGH'); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="fmh-panel" style="margin-bottom: 30px; border-top: 3px solid #ef4444;">
                        <h3 class="fmh-panel-title" style="color: #fca5a5;"><i class="fa-solid fa-shield-halved"></i> IPs Bloqueados (Brute-Force / Blacklist)</h3>
                        <div class="fmh-table-responsive">
                            <?php if(empty($ipBlacklist)): ?>
                                <p style="color:#6b7280; text-align:center; padding:20px;">Nenhum IP bloqueado. O sistema monitora automaticamente tentativas de brute-force SSH.</p>
                            <?php else: ?>
                                <table class="fmh-table">
                                    <thead><tr><th>IP</th><th>Motivo</th><th>Bloqueado até</th><th>Data do Bloqueio</th><th>Status</th></tr></thead>
                                    <tbody>
                                        <?php foreach($ipBlacklist as $bl): 
                                            $expired = strtotime($bl->blocked_until) < time();
                                        ?>
                                            <tr>
                                                <td><code style="color:#fca5a5;"><?php echo htmlspecialchars($bl->ip); ?></code></td>
                                                <td style="color:#9ca3af; font-size:12px;"><?php echo htmlspecialchars($bl->reason); ?></td>
                                                <td style="color:#fcd34d;"><?php echo date('d/m/Y H:i', strtotime($bl->blocked_until)); ?></td>
                                                <td style="color:#9ca3af;"><?php echo date('d/m/Y H:i', strtotime($bl->created_at)); ?></td>
                                                <td><span style="background:<?php echo $expired?'rgba(34,197,94,0.1)':'rgba(239,68,68,0.1)'; ?>; color:<?php echo $expired?'#22c55e':'#ef4444'; ?>; padding:2px 8px; border-radius:4px; font-size:11px; font-weight:600;"><?php echo $expired ? 'EXPIRADO' : 'ATIVO'; ?></span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php 
                    $rootAudit = $latest && !empty($latest->root_audit) ? json_decode($latest->root_audit, true) : null;
                    if ($rootAudit && is_array($rootAudit) && count($rootAudit) > 0):
                    ?>
                    <div class="fmh-panel" style="margin-bottom: 30px; border-top: 3px solid #f59e0b;">
                        <h3 class="fmh-panel-title" style="color: #fcd34d;"><i class="fa-solid fa-user-shield"></i> Auditoria de Acesso Root (Usuários com Privilégios Elevados)</h3>
                        <div class="fmh-table-responsive">
                            <table class="fmh-table">
                                <thead><tr><th>Usuário</th><th>Problema Detectado</th><th>Nível de Risco</th></tr></thead>
                                <tbody>
                                    <?php foreach($rootAudit as $ra): 
                                        $riskColor = ($ra['risk'] == 'CRITICAL') ? '#ef4444' : (($ra['risk'] == 'HIGH') ? '#f97316' : '#f59e0b');
                                        $riskBg = ($ra['risk'] == 'CRITICAL') ? 'rgba(239,68,68,0.15)' : (($ra['risk'] == 'HIGH') ? 'rgba(249,115,22,0.15)' : 'rgba(245,158,11,0.1)');
                                    ?>
                                        <tr>
                                            <td><strong style="color:#fca5a5;"><i class="fa-solid fa-user-secret"></i> <?php echo htmlspecialchars($ra['user']); ?></strong></td>
                                            <td style="color:#e5e7eb;"><?php echo htmlspecialchars($ra['issue']); ?></td>
                                            <td><span style="background:<?php echo $riskBg; ?>; color:<?php echo $riskColor; ?>; padding:4px 10px; border-radius:4px; font-size:11px; font-weight:700;"><?php echo htmlspecialchars($ra['risk']); ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <p style="font-size:11px; color:#6b7280; margin:12px 0 0;">
                            <i class="fa-solid fa-circle-info"></i> Usuários com privilégios de root detectados pelo agente. Revise periodicamente e remova acessos desnecessários.
                        </p>
                    </div>
                    <?php endif; ?>
                    
                    <div class="fmh-panel">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                            <h3 class="fmh-panel-title" style="margin:0;"><i class="fa-solid fa-triangle-exclamation"></i> Alertas e Limites (Thresholds)</h3>
                            <button onclick="document.getElementById('thresholdForm').style.display='block';" class="fmh-btn fmh-btn-primary" style="padding:4px 10px; font-size:12px;"><i class="fa-solid fa-plus"></i> Nova Regra</button>
                        </div>
                        
                        <div id="thresholdForm" style="display:none; background:#050505; border:1px solid #222; padding:15px; border-radius:6px; margin-bottom:20px;">
                            <form action="addonmodules.php?module=fmh_monitor&action=add_server_threshold" method="POST">
                                <input type="hidden" name="token" value="<?php echo generate_token('plain'); ?>">
                                <input type="hidden" name="server_id" value="<?php echo $serverDetails->id; ?>">
                                <div style="display:flex; gap:10px; margin-bottom:10px;">
                                    <select name="metric" class="fmh-form-control" style="flex:2;" required>
                                        <option value="load_1">Load Average (1m)</option>
                                        <option value="mem_used_pct">Memória RAM (%)</option>
                                        <option value="disk_used_pct">Uso de Disco (%)</option>
                                        <option value="inodes_used_pct">Uso de Inodes (%)</option>
                                        <option value="net_connections">Conexões de Rede (Qtd)</option>
                                        <option value="mail_queue">Fila de E-mail (Qtd)</option>
                                    </select>
                                    <select name="condition" class="fmh-form-control" style="flex:1;" required>
                                        <option value=">">Maior que (>)</option>
                                    </select>
                                    <input type="number" step="0.01" name="value" class="fmh-form-control" placeholder="Valor Limite" style="flex:1;" required>
                                </div>
                                <div style="display:flex; gap:10px; justify-content:space-between; align-items:center;">
                                    <div>
                                        <label style="font-size:12px; margin-right:10px;"><input type="checkbox" name="alert_channels[]" value="whatsapp" checked> WhatsApp</label>
                                        <label style="font-size:12px; margin-right:10px;"><input type="checkbox" name="alert_channels[]" value="telegram" checked> Telegram</label>
                                        <label style="font-size:12px;"><input type="checkbox" name="alert_channels[]" value="webhooks"> Webhooks</label>
                                    </div>
                                    <button type="submit" class="fmh-btn" style="background:#fff; color:#000;">Salvar Regra</button>
                                </div>
                            </form>
                        </div>
                        
                        <div class="fmh-table-responsive">
                            <table class="fmh-table">
                                <thead><tr><th>Métrica Alvo</th><th>Condição</th><th>Notificação Em</th><th>Ação</th></tr></thead>
                                <tbody>
                                    <?php if(empty($serverThresholds)): ?>
                                        <tr><td colspan="4" style="color:#6b7280;">Nenhuma regra configurada. O sistema não alertará sobre limites de carga.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($serverThresholds as $th): ?>
                                            <tr>
                                                <td><strong style="color:#f3f4f6;"><?php 
                                                    if($th->metric=='load_1') echo "Load Average (1m)";
                                                    elseif($th->metric=='mem_used_pct') echo "Uso Memória RAM (%)";
                                                    elseif($th->metric=='disk_used_pct') echo "Uso de Disco (%)";
                                                    elseif($th->metric=='inodes_used_pct') echo "Uso de Inodes (%)";
                                                    elseif($th->metric=='net_connections') echo "Conexões de Rede";
                                                    elseif($th->metric=='mail_queue') echo "Fila de E-mail";
                                                    else echo htmlspecialchars($th->metric);
                                                ?></strong></td>
                                                <td style="color:#9ca3af;"><?php echo $th->condition; ?> <strong style="color:#ef4444;"><?php echo $th->value; ?></strong></td>
                                                <td><span style="font-size:10px; background:#222; padding:2px 6px; border-radius:4px;"><?php echo str_replace(',', ' ', $th->alert_channels); ?></span></td>
                                                <td><a href="addonmodules.php?module=fmh_monitor&action=delete_server_threshold&id=<?php echo $th->id; ?>&server_id=<?php echo $serverDetails->id; ?>&token=<?php echo generate_token('plain'); ?>" style="color:#ef4444;"><i class="fa-solid fa-trash"></i></a></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="fmh-panel" style="margin-bottom: 40px; border-top: 3px solid #f87171;">
                    <h3 class="fmh-panel-title" style="color: #fca5a5;"><i class="fa-solid fa-fire"></i> Raio-X de Processos (Top 5 Consumidores de CPU/RAM)</h3>
                    <div class="fmh-table-responsive">
                        <table class="fmh-table">
                            <thead><tr><th>PID</th><th>Usuário</th><th>CPU %</th><th>RAM %</th><th>Comando (Processo)</th></tr></thead>
                            <tbody>
                                <?php 
                                if ($latest && isset($latest->top_processes) && $latest->top_processes) {
                                    $procs = explode('|', $latest->top_processes);
                                    foreach ($procs as $p) {
                                        $parts = preg_split('/\s+/', trim($p), 5);
                                        if (count($parts) >= 5) {
                                            echo "<tr>";
                                            echo "<td><code style='color:#9ca3af;'>".htmlspecialchars($parts[0])."</code></td>";
                                            echo "<td><strong style='color:#60a5fa;'>".htmlspecialchars($parts[1])."</strong></td>";
                                            echo "<td style='color:#f87171;'>".htmlspecialchars($parts[2])."%</td>";
                                            echo "<td style='color:#fcd34d;'>".htmlspecialchars($parts[3])."%</td>";
                                            echo "<td>".htmlspecialchars($parts[4])."</td>";
                                            echo "</tr>";
                                        }
                                    }
                                } else {
                                    echo "<tr><td colspan='5' style='color:#6b7280; text-align:center;'>Nenhum dado coletado ou servidor ocioso.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <!-- ==============================================
                 TAB: DASHBOARD (PADRÃO / PING)
                 ============================================== -->
            <?php elseif ($tab == 'dashboard'): ?>
                
                <div class="fmh-header-flex" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 35px;">
                    <div>
                        <h2 class="fmh-page-title">Monitoramento</h2>
                        <p class="fmh-page-subtitle">Acompanhe o status dos seus IPs e Portas em tempo real.</p>
                    </div>
                    <button class="fmh-btn fmh-btn-primary" onclick="document.getElementById('addForm').style.display='block'">
                        <i class="fa-solid fa-plus"></i> Novo Serviço
                    </button>
                </div>

                <div class="fmh-stats-grid">
                    <div class="fmh-stat-card">
                        <div>
                            <div class="fmh-stat-info"><h4>Total Monitorado</h4><h2><?php echo $total; ?></h2></div>
                        </div>
                        <div class="fmh-icon-box fmh-icon-total"><?php if(!empty($srv->last_pwd_alert)): ?><div style="margin-bottom:8px;"><span class="fmh-status-badge" style="background:rgba(239, 68, 68, 0.1); color:#ef4444; border-color:rgba(239,68,68,0.3); padding:2px 6px; font-size:10px;"><i class="fa-solid fa-triangle-exclamation" style="margin-right:4px;"></i>Senha Root Alterada!</span></div><?php endif; ?><i class="fa-solid fa-server"></i></div>
                    </div>
                    <div class="fmh-stat-card">
                        <div>
                            <div class="fmh-stat-info"><h4>Online</h4><h2><?php echo $upCount; ?></h2></div>
                        </div>
                        <div class="fmh-icon-box fmh-icon-up"><i class="fa-solid fa-bolt"></i></div>
                    </div>
                    <div class="fmh-stat-card">
                        <div>
                            <div class="fmh-stat-info"><h4>Offline</h4><h2><?php echo $downCount; ?></h2></div>
                        </div>
                        <div class="fmh-icon-box fmh-icon-down"><i class="fa-solid fa-triangle-exclamation"></i></div>
                    </div>
                </div>

                <!-- Box do Cron -->
                <div class="fmh-panel" style="background: rgba(59, 130, 246, 0.05); border-color: rgba(59, 130, 246, 0.2);">
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
                        <div style="flex: 1; min-width: 300px;">
                            <h3 style="margin: 0 0 12px 0; color: #60a5fa; font-size: 15px; display: flex; align-items: center; gap: 8px;">
                                <i class="fa-solid fa-terminal"></i> Comando Cron (Execução)
                            </h3>
                            <div style="background: #000; color: #ededed; padding: 12px 16px; border-radius: 8px; font-family: monospace; font-size: 13px; border: 1px solid #333;">
                                * * * * * <?php echo htmlspecialchars($cronCommand); ?>
                            </div>
                        </div>
                        <div style="text-align: right; background: #000; padding: 16px 24px; border-radius: 8px; border: 1px solid #333;">
                            <span style="font-size: 16px; font-weight: 700; color: <?php echo $cronColor; ?>; display: flex; align-items: center; gap: 8px; justify-content: flex-end;">
                                <span class="pulse-dot <?php echo $cronStatus == 'ATIVO E RODANDO' ? 'up' : 'down'; ?>"></span> <?php echo $cronStatus; ?>
                            </span>
                            <span style="display: block; font-size: 12px; color: #6b7280; margin-top: 8px;">Última verificação: <span style="color:#9ca3af;"><?php echo $lastCronRun ? date('d/m/Y H:i:s', strtotime($lastCronRun)) : 'Nunca'; ?></span></span>
                        </div>
                    </div>
                </div>

                <!-- Adicionar Serviço -->
                <div id="addForm" class="fmh-panel" style="display: none; animation: slideDown 0.3s ease-out;">
                    <h3 class="fmh-panel-title"><i class="fa-solid fa-network-wired"></i> Adicionar Nova Máquina/Serviço</h3>
                    <form action="addonmodules.php?module=fmh_monitor&action=add_service" method="POST">
                        <input type="hidden" name="token" value="<?php echo generate_token('plain'); ?>">
                        <div class="fmh-grid-6">
                            <div class="fmh-form-group"><label>Identificação</label><input type="text" name="name" class="fmh-form-control" placeholder="Ex: Servidor de Email" required></div>
                            <div class="fmh-form-group"><label>IP ou Host/URL</label><input type="text" name="ip" class="fmh-form-control" placeholder="192.168.0.1 ou https://..." required></div>
                            <div class="fmh-form-group"><label>Tipo</label>
                                <select name="type" class="fmh-form-control" style="appearance: none;">
                                    <option value="PORT">Porta (TCP)</option>
                                    <option value="PING">Ping (ICMP)</option>
                                    <option value="HTTP">Website (HTTP)</option>
                                </select>
                            </div>
                            <div class="fmh-form-group"><label>Porta</label><input type="number" name="port" class="fmh-form-control" placeholder="Ex: 80"></div>
                            <div class="fmh-form-group"><label>Intervalo</label>
                                <select name="interval_minutes" class="fmh-form-control" style="appearance: none;">
                                    <option value="1">1 Minuto</option>
                                    <option value="2">2 Minutos</option>
                                    <option value="3">3 Minutos</option>
                                    <option value="5">5 Minutos</option>
                                    <option value="10">10 Minutos</option>
                                    <option value="15">15 Minutos</option>
                                    <option value="30">30 Minutos</option>
                                    <option value="60">60 Minutos</option>
                                </select>
                            </div>
                            <div class="fmh-form-group"><label>ID Cliente WHMCS (Opcional)</label><select name="client_id" class="fmh-form-control fmh-client-select" style="width: 100%;"><option value=""></option></select></div>
                        </div>
                        <div class="fmh-form-group">
                            <label>Rotas de Notificação Ativas para este serviço:</label>
                            <div class="fmh-checkbox-group">
                                <label class="fmh-checkbox-label" style="border-color: #3b82f6; color: #60a5fa;"><input type="checkbox" name="notify_client" value="1"> Notificar Cliente</label>
                                <label class="fmh-checkbox-label"><input type="checkbox" name="channels[]" value="whatsapp" checked> WhatsApp</label>
                                <label class="fmh-checkbox-label"><input type="checkbox" name="channels[]" value="telegram" checked> Telegram</label>
                                <label class="fmh-checkbox-label"><input type="checkbox" name="channels[]" value="email" checked> E-mail</label>
                                <label class="fmh-checkbox-label"><input type="checkbox" name="channels[]" value="webhooks" checked> Webhooks</label>
                            </div>
                        </div>
                        <div style="margin-top: 25px;">
                            <button type="submit" class="fmh-btn fmh-btn-primary"><i class="fa-solid fa-satellite-dish"></i> Adicionar ao Monitoramento</button>
                            <button type="button" class="fmh-btn" style="background: transparent; color: #9ca3af;" onclick="document.getElementById('addForm').style.display='none'">Cancelar</button>
                        </div>
                    </form>
                </div>

                <!-- EDIT FORM (Hidden by default) -->
                <div id="editForm" class="fmh-panel" style="display: none; animation: slideDown 0.3s ease-out; border-top: 3px solid #3b82f6;">
                    <h3 class="fmh-panel-title"><i class="fa-solid fa-pen-to-square"></i> Editar Serviço</h3>
                    <form action="addonmodules.php?module=fmh_monitor&action=edit_service" method="POST">
                        <input type="hidden" name="token" value="<?php echo generate_token('plain'); ?>">
                        <input type="hidden" name="id" id="edit_id" value="">
                        <div class="fmh-grid-6">
                            <div class="fmh-form-group"><label>Identificação</label><input type="text" name="name" id="edit_name" class="fmh-form-control" required></div>
                            <div class="fmh-form-group"><label>IP ou Host/URL</label><input type="text" name="ip" id="edit_ip" class="fmh-form-control" required></div>
                            <div class="fmh-form-group"><label>Tipo</label>
                                <select name="type" id="edit_type" class="fmh-form-control" style="appearance: none;">
                                    <option value="PORT">Porta (TCP)</option>
                                    <option value="PING">Ping (ICMP)</option>
                                    <option value="HTTP">Website (HTTP)</option>
                                </select>
                            </div>
                            <div class="fmh-form-group"><label>Porta</label><input type="number" name="port" id="edit_port" class="fmh-form-control"></div>
                            <div class="fmh-form-group"><label>Intervalo</label>
                                <select name="interval_minutes" id="edit_interval" class="fmh-form-control" style="appearance: none;">
                                    <option value="1">1 Minuto</option>
                                    <option value="2">2 Minutos</option>
                                    <option value="3">3 Minutos</option>
                                    <option value="5">5 Minutos</option>
                                    <option value="10">10 Minutos</option>
                                    <option value="15">15 Minutos</option>
                                    <option value="30">30 Minutos</option>
                                    <option value="60">60 Minutos</option>
                                </select>
                            </div>
                            <div class="fmh-form-group"><label>ID Cliente WHMCS (Opcional)</label><select name="client_id" id="edit_client_id" class="fmh-form-control fmh-client-select" style="width: 100%;"><option value=""></option></select></div>
                        </div>
                        <div class="fmh-form-group">
                            <label>Rotas de Notificação Ativas para este serviço:</label>
                            <div style="display: flex; gap: 20px; align-items: center; margin-top: 10px; flex-wrap: wrap;">
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; color: #60a5fa; border-right: 1px solid #333; padding-right: 15px;"><input type="checkbox" name="notify_client" value="1" id="edit_notify_client"> <i class="fa-solid fa-user"></i> Notificar Cliente</label>
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; color: #d1d5db;"><input type="checkbox" name="channels[]" value="whatsapp" id="edit_ch_whatsapp"> <i class="fa-brands fa-whatsapp" style="color: #22c55e;"></i> WhatsApp</label>
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; color: #d1d5db;"><input type="checkbox" name="channels[]" value="telegram" id="edit_ch_telegram"> <i class="fa-brands fa-telegram" style="color: #3b82f6;"></i> Telegram</label>
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; color: #d1d5db;"><input type="checkbox" name="channels[]" value="email" id="edit_ch_email"> <i class="fa-solid fa-envelope" style="color: #f59e0b;"></i> Email</label>
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; color: #d1d5db;"><input type="checkbox" name="channels[]" value="webhooks" id="edit_ch_webhooks"> <i class="fa-solid fa-link" style="color: #a855f7;"></i> Webhooks</label>
                            </div>
                        </div>
                        <div style="margin-top: 15px;">
                            <button type="submit" class="fmh-btn fmh-btn-primary"><i class="fa-solid fa-save"></i> Salvar Alterações</button>
                            <button type="button" class="fmh-btn" style="background: transparent; color: #9ca3af;" onclick="document.getElementById('editForm').style.display='none'">Cancelar</button>
                        </div>
                    </form>
                </div>

                <script>
                function openEditForm(id, name, ip, type, port, interval, channels, client_id, notify_client) {
                    document.getElementById('addForm').style.display = 'none';
                    document.getElementById('edit_id').value = id;
                    document.getElementById('edit_name').value = name;
                    document.getElementById('edit_ip').value = ip;
                    document.getElementById('edit_type').value = type;
                    document.getElementById('edit_port').value = port;
                    document.getElementById('edit_interval').value = interval;
                    
                    var $editClient = $('#edit_client_id');
                    if (client_id > 0) {
                        var option = new Option('ID: ' + client_id + ' (Mantenha ou Busque Novo)', client_id, true, true);
                        $editClient.empty().append(option).trigger('change');
                    } else {
                        $editClient.empty().trigger('change');
                    }
                    
                    const chList = channels.split(',');
                    document.getElementById('edit_notify_client').checked = (notify_client == 1);
                    document.getElementById('edit_ch_whatsapp').checked = chList.includes('whatsapp');
                    document.getElementById('edit_ch_telegram').checked = chList.includes('telegram');
                    document.getElementById('edit_ch_email').checked = chList.includes('email');
                    document.getElementById('edit_ch_webhooks').checked = chList.includes('webhooks');
                    
                    document.getElementById('editForm').style.display = 'block';
                    window.scrollTo({ top: document.getElementById('editForm').offsetTop - 50, behavior: 'smooth' });
                }
                </script>

                <!-- Lista de Serviços -->
                <div class="fmh-panel" style="padding: 0; overflow: hidden;">
                    <div style="padding: 24px; border-bottom: 1px solid rgba(255,255,255,0.05);">
                        <h3 class="fmh-panel-title" style="margin:0;"><i class="fa-solid fa-list-check"></i> Serviços Monitorados</h3>
                    </div>
                    <div class="fmh-table-responsive">
                        <table class="fmh-table">
                            <thead>
                                <tr>
                                    <th style="padding-left: 24px;">Serviço</th>
                                    <th>Endereço</th>
                                    <th>Uptime (30d)</th>
                                    <th>Status</th>
                                    <th>Último Ping</th>
                                    <th style="text-align: right; padding-right: 24px;">Gerenciar</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($services)): ?>
                                    <tr><td colspan="6" style="text-align:center; padding: 40px; color:#6b7280;">Nenhum serviço configurado.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($services as $srv): ?>
                                        <tr>
                                            <td style="padding-left: 24px;">
                                                <a href="addonmodules.php?module=fmh_monitor&action=view&id=<?php echo $srv->id; ?>" style="color: #60a5fa; text-decoration: none; font-weight: 700; transition: color 0.2s;" onmouseover="this.style.color='#93c5fd'" onmouseout="this.style.color='#60a5fa'"><?php echo htmlspecialchars($srv->name); ?> <i class="fa-solid fa-arrow-up-right-from-square" style="font-size: 11px; margin-left: 4px;"></i></a>
                                                <?php if(!empty($srv->client_id)): ?>
                                                    <div style="margin-top: 5px; font-size: 11px;">
                                                        <a href="clientssummary.php?userid=<?php echo $srv->client_id; ?>" target="_blank" style="color: <?php echo $srv->notify_client ? '#60a5fa' : '#6b7280'; ?>; text-decoration: none; padding: 2px 6px; border-radius: 4px; background: rgba(255,255,255,0.05); display: inline-block;" title="ID Cliente: <?php echo $srv->client_id; ?>">
                                                            <i class="fa-solid <?php echo $srv->notify_client ? 'fa-user-check' : 'fa-user'; ?>"></i> <?php echo htmlspecialchars($srv->client_firstname . ' ' . $srv->client_lastname); ?>
                                                        </a>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td><code><?php echo htmlspecialchars($srv->ip); ?><?php echo $srv->port ? ':' . (int)$srv->port : ''; ?></code> <span style="font-size: 10px; background: #111; padding: 2px 6px; border-radius: 4px; margin-left: 5px; border: 1px solid #333;"><?php echo isset($srv->type) ? $srv->type : 'PORT'; ?></span> <span style="font-size: 10px; color: #a1a1aa; margin-left: 5px;"><i class="fa-solid fa-clock"></i> <?php echo isset($srv->interval_minutes) ? $srv->interval_minutes : 1; ?>m</span></td>
                                            <td>
                                                <?php $upColor = $srv->uptime_percentage > 99 ? 'green' : ($srv->uptime_percentage > 95 ? 'yellow' : 'red'); ?>
                                                <div style="display:flex; align-items:center; gap:8px;">
                                                    <div class="fmh-uptime-bar"><div class="fmh-uptime-bar-fill <?php echo $upColor; ?>" style="width:<?php echo min(100, max(0, $srv->uptime_percentage)); ?>%;"></div></div>
                                                    <strong style="color: <?php echo $upColor == 'green' ? 'var(--accent-emerald)' : ($upColor == 'yellow' ? 'var(--accent-amber)' : 'var(--accent-rose)'); ?>; font-size:13px;"><?php echo $srv->uptime_percentage; ?>%</strong>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($srv->status == 'UP'): ?>
                                                    <span class="fmh-status-badge fmh-badge-up"><span class="pulse-dot up"></span> ONLINE</span>
                                                <?php elseif ($srv->status == 'DOWN'): ?>
                                                    <span class="fmh-status-badge fmh-badge-down"><span class="pulse-dot down"></span> OFFLINE</span>
                                                <?php else: ?>
                                                    <span class="fmh-status-badge fmh-badge-paused"><i class="fa-solid fa-circle-pause" style="margin-right: 6px;"></i> PAUSADO</span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="color: #9ca3af; font-size: 13px;"><?php echo $srv->last_check ? date('d/m/Y H:i:s', strtotime($srv->last_check)) : 'Aguardando...'; ?></td>
                                            <td style="text-align: right; padding-right: 24px; min-width: 140px;">
                                                <a href="addonmodules.php?module=fmh_monitor&action=toggle_pause&id=<?php echo $srv->id; ?>&token=<?php echo generate_token('plain'); ?>" class="fmh-btn <?php echo $srv->status == 'PAUSED' ? 'fmh-btn-secondary' : 'fmh-btn-warning'; ?>" style="padding: 6px 12px; margin-right: 4px;" title="Pausar/Retomar">
                                                    <i class="fa-solid <?php echo $srv->status == 'PAUSED' ? 'fa-play' : 'fa-pause'; ?>"></i>
                                                </a>
                                                <button type="button" class="fmh-btn fmh-btn-secondary" style="padding: 6px 12px; margin-right: 4px;" title="Editar" onclick="openEditForm(<?php echo $srv->id; ?>, '<?php echo addslashes($srv->name); ?>', '<?php echo addslashes($srv->ip); ?>', '<?php echo $srv->type; ?>', '<?php echo $srv->port; ?>', <?php echo $srv->interval_minutes; ?>, '<?php echo $srv->channels; ?>', <?php echo (int)$srv->client_id; ?>, <?php echo (int)$srv->notify_client; ?>)">
                                                    <i class="fa-solid fa-pen"></i>
                                                </button>
                                                <a href="addonmodules.php?module=fmh_monitor&action=delete_service&id=<?php echo $srv->id; ?>&token=<?php echo generate_token('plain'); ?>" class="fmh-btn fmh-btn-danger" style="padding: 6px 12px;" onclick="return confirm('Tem certeza que deseja apagar este serviço e todo o histórico dele?')" title="Apagar">
                                                    <i class="fa-solid fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <!-- ==============================================
                 TAB: SETTINGS
                 ============================================== -->
            <?php elseif ($tab == 'settings'): ?>
                <div style="margin-bottom: 35px;">
                    <h2 class="fmh-page-title">Integrações</h2>
                    <p class="fmh-page-subtitle">Conecte as APIs para enviar os alertas em tempo real.</p>
                </div>
                
                <form action="addonmodules.php?module=fmh_monitor&action=save_settings" method="POST">
                    <input type="hidden" name="token" value="<?php echo generate_token('plain'); ?>">
                    <input type="hidden" name="current_tab" value="settings">
                    
                    <div class="fmh-panel">
                                                <h3 class="fmh-panel-title"><i class="fa-brands fa-whatsapp" style="color: #22c55e;"></i> Evolution API (WhatsApp)</h3>
                        <div class="fmh-form-group">
                            <label>Status da Integração</label>
                            <select name="enable_whatsapp" class="fmh-form-control" style="width: 100%; margin-bottom: 15px;">
                                <option value="1" <?php echo ($settings['enable_whatsapp'] ?? '1') == '1' ? 'selected' : ''; ?>>Ativo (Enviando Alertas)</option>
                                <option value="0" <?php echo ($settings['enable_whatsapp'] ?? '1') == '0' ? 'selected' : ''; ?>>Inativo (Ignorar)</option>
                            </select>
                        </div>
                        <div class="fmh-grid-2">
                            <div class="fmh-form-group">
                                <label>URL Base da API</label>
                                <input type="url" name="wa_api_url" class="fmh-form-control" placeholder="Ex: https://api.seudominio.com" value="<?php echo htmlspecialchars($settings['wa_api_url'] ?? ''); ?>">
                            </div>
                            <div class="fmh-form-group">
                                <label>Token da Instância (API Key)</label>
                                <input type="password" name="wa_api_token" class="fmh-form-control" placeholder="••••••••••••" value="<?php echo htmlspecialchars($settings['wa_api_token'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="fmh-form-group" style="margin-bottom: 0;">
                            <label>Números Destinatários (Com DDI, separados por vírgula)</label>
                            <input type="text" name="wa_numbers" class="fmh-form-control" placeholder="5511999999999, 5511888888888" value="<?php echo htmlspecialchars($settings['wa_numbers'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="fmh-grid-2">
                        <div class="fmh-panel">
                                                        <h3 class="fmh-panel-title"><i class="fa-brands fa-telegram" style="color: #38bdf8;"></i> Telegram</h3>
                            <div class="fmh-form-group">
                                <label>Status da Integração</label>
                                <select name="enable_telegram" class="fmh-form-control" style="width: 100%;">
                                    <option value="1" <?php echo ($settings['enable_telegram'] ?? '1') == '1' ? 'selected' : ''; ?>>Ativo (Enviando Alertas)</option>
                                    <option value="0" <?php echo ($settings['enable_telegram'] ?? '1') == '0' ? 'selected' : ''; ?>>Inativo (Ignorar)</option>
                                </select>
                            </div>
                            <div class="fmh-form-group">
                                <label>Bot Token</label>
                                <input type="text" name="telegram_token" class="fmh-form-control" placeholder="123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11" value="<?php echo htmlspecialchars($settings['telegram_token'] ?? ''); ?>">
                            </div>
                            <div class="fmh-form-group" style="margin-bottom: 0;">
                                <label>Chat ID / Grupo ID</label>
                                <input type="text" name="telegram_chat_id" class="fmh-form-control" placeholder="-1001234567890" value="<?php echo htmlspecialchars($settings['telegram_chat_id'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="fmh-panel">
                                                        <h3 class="fmh-panel-title"><i class="fa-solid fa-envelope" style="color: #f472b6;"></i> E-mail & Sistema</h3>
                            <div class="fmh-form-group">
                                <label>Status da Integração</label>
                                <select name="enable_email" class="fmh-form-control" style="width: 100%;">
                                    <option value="1" <?php echo ($settings['enable_email'] ?? '1') == '1' ? 'selected' : ''; ?>>Ativo (Enviando Alertas)</option>
                                    <option value="0" <?php echo ($settings['enable_email'] ?? '1') == '0' ? 'selected' : ''; ?>>Inativo (Ignorar)</option>
                                </select>
                            </div>
                            <div class="fmh-form-group">
                                <label>E-mails de Alerta (Separados por vírgula)</label>
                                <input type="text" name="notify_email" class="fmh-form-control" placeholder="noc@seudominio.com" value="<?php echo htmlspecialchars($settings['notify_email'] ?? ''); ?>">
                            </div>
                            <div class="fmh-form-group" style="margin-bottom: 0;">
                                <label>Timeout de Conexão (Segundos)</label>
                                <input type="number" name="timeout" class="fmh-form-control" value="<?php echo htmlspecialchars($settings['timeout'] ?? '5'); ?>">
                                <p style="font-size: 12px; color: #6b7280; margin-top: 8px;">Recomendado: 3 a 5 segundos.</p>
                            </div>
                        </div>
                    </div>

                    <div style="text-align: right;">
                        <button type="submit" class="fmh-btn fmh-btn-primary" style="padding: 14px 30px; font-size: 16px;"><i class="fa-solid fa-floppy-disk"></i> Gravar Configurações</button>
                    </div>
                </form>

            <!-- ==============================================
                 TAB: TEMPLATES
                 ============================================== -->
            <?php elseif ($tab == 'templates'): ?>
                <div style="margin-bottom: 35px;">
                    <h2 class="fmh-page-title">Templates de Mensagens</h2>
                    <p class="fmh-page-subtitle">Crie a estrutura exata do alerta usando as variáveis inteligentes.</p>
                </div>
                
                <div class="fmh-panel" style="background: rgba(139, 92, 246, 0.1); border-color: rgba(139, 92, 246, 0.2);">
                    <h3 class="fmh-panel-title" style="color: #c4b5fd; margin-bottom: 10px;"><i class="fa-solid fa-wand-magic-sparkles"></i> Variáveis Dinâmicas</h3>
                    <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                        <code style="background: rgba(0,0,0,0.3); padding: 6px 12px; border-radius: 6px; color: #ddd;">{nome_servico}</code>
                        <code style="background: rgba(0,0,0,0.3); padding: 6px 12px; border-radius: 6px; color: #ddd;">{ip}</code>
                        <code style="background: rgba(0,0,0,0.3); padding: 6px 12px; border-radius: 6px; color: #ddd;">{porta}</code>
                        <code style="background: rgba(0,0,0,0.3); padding: 6px 12px; border-radius: 6px; color: #ddd;">{hora}</code>
                        <code style="background: rgba(0,0,0,0.3); padding: 6px 12px; border-radius: 6px; color: #ddd;">{status}</code>
                        <code style="background: rgba(0,0,0,0.3); padding: 6px 12px; border-radius: 6px; color: #f43f5e;" title="Apenas para alertas de servidor">{valor}</code>
                        <code style="background: rgba(0,0,0,0.3); padding: 6px 12px; border-radius: 6px; color: #f43f5e;" title="Apenas para alertas de spam">{top_senders}</code>
                        <code style="background: rgba(0,0,0,0.3); padding: 6px 12px; border-radius: 6px; color: #f43f5e;" title="Apenas para alertas de Auto-Cura">{acao}</code>
                    </div>
                </div>

                <form action="addonmodules.php?module=fmh_monitor&action=save_settings" method="POST">
                    <input type="hidden" name="token" value="<?php echo generate_token('plain'); ?>">
                    <input type="hidden" name="current_tab" value="templates">
                    
                    <div class="fmh-grid-2">
                        <div class="fmh-panel" style="border-top: 3px solid #ef4444;">
                            <h3 class="fmh-panel-title" style="color: #fca5a5;"><i class="fa-solid fa-bell"></i> Alerta Crítico Genérico (Serviço Caiu)</h3>
                            <div class="fmh-form-group" style="margin-bottom: 0;">
                                <textarea name="template_down" class="fmh-form-control" style="border-color: rgba(239, 68, 68, 0.2);"><?php echo htmlspecialchars($settings['template_down'] ?? ''); ?></textarea>
                            </div>
                        </div>
                        
                        <div class="fmh-panel" style="border-top: 3px solid #10b981;">
                            <h3 class="fmh-panel-title" style="color: #6ee7b7;"><i class="fa-solid fa-heart-circle-check"></i> Alerta de Recuperação Genérico</h3>
                            <div class="fmh-form-group" style="margin-bottom: 0;">
                                <textarea name="template_up" class="fmh-form-control" style="border-color: rgba(16, 185, 129, 0.2);"><?php echo htmlspecialchars($settings['template_up'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <h2 class="fmh-page-title" style="margin-top: 40px; font-size: 20px;">Templates de Limite (Servidores)</h2>
                    <div class="fmh-grid-3">
                        <div class="fmh-panel" style="border-top: 3px solid #f59e0b;">
                            <h3 class="fmh-panel-title" style="color: #fcd34d;"><i class="fa-solid fa-microchip"></i> Alerta de Carga (CPU Load)</h3>
                            <div class="fmh-form-group" style="margin-bottom: 0;">
                                <textarea name="template_alert_cpu" class="fmh-form-control" style="border-color: rgba(245, 158, 11, 0.2); height: 120px;"><?php echo htmlspecialchars(!empty($settings['template_alert_cpu']) ? $settings['template_alert_cpu'] : "⚠️ *ALERTA DE CARGA* ⚠️\nServidor: {nome_servico}\nCPU Load (1m): {valor}\nStatus: {status}\nData/Hora: {hora}"); ?></textarea>
                            </div>
                        </div>
                        
                        <div class="fmh-panel" style="border-top: 3px solid #f59e0b;">
                            <h3 class="fmh-panel-title" style="color: #fcd34d;"><i class="fa-solid fa-memory"></i> Alerta de Memória RAM</h3>
                            <div class="fmh-form-group" style="margin-bottom: 0;">
                                <textarea name="template_alert_ram" class="fmh-form-control" style="border-color: rgba(245, 158, 11, 0.2); height: 120px;"><?php echo htmlspecialchars(!empty($settings['template_alert_ram']) ? $settings['template_alert_ram'] : "⚠️ *ALERTA DE MEMÓRIA* ⚠️\nServidor: {nome_servico}\nUso de RAM: {valor}%\nStatus: {status}\nData/Hora: {hora}"); ?></textarea>
                            </div>
                        </div>
                        
                        <div class="fmh-panel" style="border-top: 3px solid #f59e0b;">
                            <h3 class="fmh-panel-title" style="color: #fcd34d;"><i class="fa-solid fa-hard-drive"></i> Alerta de Disco / Inodes</h3>
                            <div class="fmh-form-group" style="margin-bottom: 0;">
                                <textarea name="template_alert_disk" class="fmh-form-control" style="border-color: rgba(245, 158, 11, 0.2); height: 120px;"><?php echo htmlspecialchars(!empty($settings['template_alert_disk']) ? $settings['template_alert_disk'] : "⚠️ *ALERTA DE DISCO/INODES* ⚠️\nServidor: {nome_servico}\nUso: {valor}%\nStatus: {status}\nData/Hora: {hora}"); ?></textarea>
                            </div>
                        </div>
                        
                        <div class="fmh-panel" style="border-top: 3px solid #eab308;">
                            <h3 class="fmh-panel-title" style="color: #fde047;"><i class="fa-solid fa-envelope-open-text"></i> Alerta Anti-Spam (Fila de E-mail)</h3>
                            <div class="fmh-form-group" style="margin-bottom: 0;">
                                <textarea name="template_alert_spam" class="fmh-form-control" style="border-color: rgba(234, 179, 8, 0.2); height: 120px;"><?php echo htmlspecialchars(!empty($settings['template_alert_spam']) ? $settings['template_alert_spam'] : "⚠️ *ALERTA DE SPAM* ⚠️\nServidor: {nome_servico}\nFila Total: {valor}\n\nTop Remetentes:\n{top_senders}"); ?></textarea>
                            </div>
                        </div>
                        
                                                <div class="fmh-panel" style="border-top: 3px solid #8b5cf6;">
                            <h3 class="fmh-panel-title" style="color: #c4b5fd;"><i class="fa-solid fa-shield-halved"></i> Alerta de Login SSH (Desconhecido)</h3>
                            <div class="fmh-form-group" style="margin-bottom: 0;">
                                <textarea name="template_alert_ssh" class="fmh-form-control" style="border-color: rgba(139, 92, 246, 0.2); height: 120px;"><?php echo htmlspecialchars(!empty($settings['template_alert_ssh']) ? $settings['template_alert_ssh'] : "🚨 *NOVO LOGIN SSH DETECTADO* 🚨\nServidor: {nome_servico}\nUsuário: {usuario}\nIP: {ip}\nLocalização: {localidade}\nData/Hora: {hora}"); ?></textarea>
                            </div>
                        </div>
                        <div class="fmh-panel" style="border-top: 3px solid #3b82f6;">
                            <h3 class="fmh-panel-title" style="color: #93c5fd;"><i class="fa-solid fa-wand-magic-sparkles"></i> Alerta de Auto-Cura Realizada</h3>
                            <div class="fmh-form-group" style="margin-bottom: 0;">
                                <textarea name="template_alert_remediation" class="fmh-form-control" style="border-color: rgba(59, 130, 246, 0.2); height: 120px;"><?php echo htmlspecialchars(!empty($settings['template_alert_remediation']) ? $settings['template_alert_remediation'] : "🪄 *AUTO-CURA EXECUTADA* 🪄\nServidor: {nome_servico}\nAção: {acao}\nDetalhe: {detalhe}\nStatus: RESOLVIDO ✅"); ?></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <h2 class="fmh-page-title" style="margin-top: 30px; font-size: 20px;">Templates de Segurança</h2>
                    <div class="fmh-grid-2">
                        <div class="fmh-panel" style="border-top: 3px solid #ef4444;">
                            <h3 class="fmh-panel-title" style="color: #fca5a5;"><i class="fa-solid fa-shield-halved"></i> Alerta de Brute-Force SSH</h3>
                            <div class="fmh-form-group" style="margin-bottom: 0;">
                                <textarea name="template_alert_bruteforce" class="fmh-form-control" style="border-color: rgba(239, 68, 68, 0.2); height: 120px;"><?php echo htmlspecialchars(!empty($settings['template_alert_bruteforce']) ? $settings['template_alert_bruteforce'] : "🚨 *ALERTA DE BRUTE-FORCE SSH* 🚨\nServidor: {nome_servico}\nIP Bloqueado: {ip}\nMotivo: {detalhe}\nData/Hora: {hora}"); ?></textarea>
                            </div>
                            <p style="font-size:11px; color:#9ca3af; margin:8px 0 0;">Variáveis: {nome_servico}, {ip}, {detalhe}, {hora}</p>
                        </div>
                        <div class="fmh-panel" style="border-top: 3px solid #3b82f6;">
                            <h3 class="fmh-panel-title" style="color: #93c5fd;"><i class="fa-solid fa-lock"></i> Alerta de Expiração SSL</h3>
                            <div class="fmh-form-group" style="margin-bottom: 0;">
                                <textarea name="template_alert_ssl" class="fmh-form-control" style="border-color: rgba(59, 130, 246, 0.2); height: 120px;"><?php echo htmlspecialchars(!empty($settings['template_alert_ssl']) ? $settings['template_alert_ssl'] : "🔒 *ALERTA DE CERTIFICADO SSL* 🔒\nServidor: {nome_servico}\nDominio: {dominio}\nDias Restantes: {dias}\nSeveridade: {severidade}\nData/Hora: {hora}"); ?></textarea>
                            </div>
                            <p style="font-size:11px; color:#9ca3af; margin:8px 0 0;">Variáveis: {nome_servico}, {dominio}, {dias}, {severidade}, {hora}</p>
                        </div>
                    </div>
                    
                    <div style="text-align: right; margin-top: 20px;">
                        <button type="submit" class="fmh-btn fmh-btn-primary" style="padding: 14px 30px; font-size: 16px;"><i class="fa-solid fa-floppy-disk"></i> Atualizar Layouts</button>
                    </div>
                </form>

            <!-- ==============================================
                 TAB: WEBHOOKS
                 ============================================== -->
            <?php elseif ($tab == 'webhooks'): ?>
                <div class="fmh-header-flex" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 35px;">
                    <div>
                        <h2 class="fmh-page-title">Webhooks</h2>
                        <p class="fmh-page-subtitle">Integre o monitoramento com ferramentas externas como n8n, Slack, Discord e Zapier.</p>
                    </div>
                    <button class="fmh-btn fmh-btn-primary" onclick="document.getElementById('addWebhookForm').style.display='block'">
                        <i class="fa-solid fa-plus"></i> Novo Webhook
                    </button>
                </div>

                <div id="addWebhookForm" class="fmh-panel" style="display: none; animation: slideDown 0.3s ease-out;">
                    <h3 class="fmh-panel-title"><i class="fa-solid fa-link"></i> Registrar Novo Webhook</h3>
                    <form action="addonmodules.php?module=fmh_monitor&action=add_webhook" method="POST">
                        <input type="hidden" name="token" value="<?php echo generate_token('plain'); ?>">
                        <div class="fmh-grid-webhook">
                            <div class="fmh-form-group"><label>Nome de Identificação</label><input type="text" name="wh_name" class="fmh-form-control" placeholder="Ex: Slack Channel NOC" required></div>
                            <div class="fmh-form-group"><label>URL Destino</label><input type="url" name="wh_url" class="fmh-form-control" placeholder="https://..." required></div>
                            <div class="fmh-form-group"><label>Método de Envio</label>
                                <select name="wh_method" class="fmh-form-control" style="appearance: none;">
                                    <option value="POST">POST (JSON Payload)</option>
                                    <option value="GET">GET (Query String)</option>
                                    <option value="PUT">PUT</option>
                                </select>
                            </div>
                        </div>
                        <div class="fmh-form-group">
                            <label>Headers Adicionais (Linha por linha)</label>
                            <textarea name="wh_headers" class="fmh-form-control" placeholder="Authorization: Bearer XXXXXX&#10;x-api-key: YYYYYY" style="min-height: 80px; font-family: monospace;"></textarea>
                        </div>
                        <div style="margin-top: 15px;">
                            <button type="submit" class="fmh-btn fmh-btn-primary"><i class="fa-solid fa-plug-circle-plus"></i> Ativar Webhook</button>
                            <button type="button" class="fmh-btn" style="background: transparent; color: #9ca3af;" onclick="document.getElementById('addWebhookForm').style.display='none'">Cancelar</button>
                        </div>
                    </form>
                </div>

                <div class="fmh-panel" style="padding: 0; overflow: hidden;">
                    <div class="fmh-table-responsive">
                        <table class="fmh-table">
                            <thead><tr><th style="padding-left: 24px;">Nome</th><th>Endpoint (URL)</th><th>Método</th><th>Status</th><th style="text-align: right; padding-right: 24px;">Excluir</th></tr></thead>
                            <tbody>
                                <?php if (empty($webhooks)): ?>
                                    <tr><td colspan="5" style="text-align:center; padding: 40px; color:#6b7280;">Nenhuma integração via Webhook ativa no momento.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($webhooks as $wh): ?>
                                        <tr>
                                            <td style="padding-left: 24px;"><strong style="color: #f3f4f6;"><?php echo htmlspecialchars($wh->name); ?></strong></td>
                                            <td><code style="font-size:12px; background:rgba(0,0,0,0.3); color:#93c5fd; padding:6px 10px; border-radius:6px;"><?php echo htmlspecialchars($wh->url); ?></code></td>
                                            <td><span class="fmh-status-badge" style="background: rgba(255,255,255,0.05); color: #d1d5db; border-color: rgba(255,255,255,0.1);"><?php echo $wh->method; ?></span></td>
                                            <td><span class="fmh-status-badge fmh-badge-up">ATIVO</span></td>
                                            <td style="text-align: right; padding-right: 24px;">
                                                <a href="addonmodules.php?module=fmh_monitor&action=delete_webhook&id=<?php echo $wh->id; ?>&token=<?php echo generate_token('plain'); ?>" class="fmh-btn fmh-btn-danger" style="padding: 6px 12px;" onclick="return confirm('Apagar webhook?')">
                                                    <i class="fa-solid fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <!-- ==============================================
                 TAB: LOGS
                 ============================================== -->
            <?php elseif ($tab == 'logs'): ?>
                <div style="margin-bottom: 35px;">
                    <h2 class="fmh-page-title">Logs de Auditoria</h2>
                    <p class="fmh-page-subtitle">Registros das últimas 50 ocorrências geradas pelo cronjob.</p>
                </div>
                
                <div class="fmh-panel" style="padding: 0; overflow: hidden;">
                    <div class="fmh-table-responsive">
                        <table class="fmh-table">
                            <thead><tr><th style="padding-left: 24px;">Data/Hora</th><th>Serviço Afetado</th><th>IP / Host</th><th>Ocorrência</th><th>Detalhe do Evento</th></tr></thead>
                            <tbody>
                                <?php if (empty($logs)): ?>
                                    <tr><td colspan="5" style="text-align:center; padding: 40px; color:#6b7280;">Os logs estão vazios. O sistema aguarda o primeiro evento.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($logs as $l): ?>
                                        <tr>
                                            <td style="padding-left: 24px; font-size: 13px; color: #9ca3af;"><?php echo date('d/m/Y H:i:s', strtotime($l->created_at)); ?></td>
                                            <td><strong style="color: #f3f4f6;"><?php echo htmlspecialchars($l->service_name); ?></strong></td>
                                            <td style="font-family: monospace; color: #9ca3af; font-size: 13px;"><?php echo htmlspecialchars($l->ip); ?></td>
                                            <td>
                                                <?php if ($l->event_type == 'UP'): ?>
                                                    <span class="fmh-status-badge fmh-badge-up"><i class="fa-solid fa-arrow-up" style="margin-right: 4px;"></i> RECUPERADO</span>
                                                <?php else: ?>
                                                    <span class="fmh-status-badge fmh-badge-down"><i class="fa-solid fa-arrow-down" style="margin-right: 4px;"></i> QUEDA</span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="font-size: 13px; color: #9ca3af;"><?php echo htmlspecialchars($l->details); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

<script>
function fmhToggleTheme() {
    const wrapper = document.querySelector('.fmh-monitor-wrapper');
    const icon = document.getElementById('theme-icon');
    const text = document.getElementById('theme-text');
    const current = wrapper.getAttribute('data-theme');
    if (current === 'light') {
        wrapper.removeAttribute('data-theme');
        icon.className = 'fa-solid fa-moon';
        text.textContent = 'Tema Escuro';
        localStorage.setItem('fmh-theme', 'dark');
    } else {
        wrapper.setAttribute('data-theme', 'light');
        icon.className = 'fa-solid fa-sun';
        text.textContent = 'Tema Claro';
        localStorage.setItem('fmh-theme', 'light');
    }
}
document.addEventListener('DOMContentLoaded', function() {
    const saved = localStorage.getItem('fmh-theme');
    if (saved === 'light') {
        document.querySelector('.fmh-monitor-wrapper').setAttribute('data-theme', 'light');
        document.getElementById('theme-icon').className = 'fa-solid fa-sun';
        document.getElementById('theme-text').textContent = 'Tema Claro';
    }
});
</script>

<script>
(function() {
    var liveEl = document.getElementById('fmh-live-indicator');
    // Only auto-refresh dashboard tab, NOT server view (has charts that break on innerHTML)
    var isDashboard = window.location.search.indexOf('tab=dashboard') > -1;
    if (isDashboard && liveEl) {
        liveEl.style.display = 'block';
        var refreshInterval = setInterval(function() {
            var xhr = new XMLHttpRequest();
            xhr.open('GET', window.location.href, true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.onload = function() {
                if (xhr.status === 200) {
                    var parser = new DOMParser();
                    var doc = parser.parseFromString(xhr.responseText, 'text/html');
                    var newContent = doc.querySelector('.fmh-content');
                    var currentContent = document.querySelector('.fmh-content');
                    if (newContent && currentContent) {
                        currentContent.innerHTML = newContent.innerHTML;
                    }
                }
            };
            xhr.send();
        }, 30000);
    }
})();
</script>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    document.querySelectorAll('.fmh-table').forEach(table => {
        let headers = Array.from(table.querySelectorAll('thead th')).map(th => th.innerText.trim());
        table.querySelectorAll('tbody tr').forEach(tr => {
            Array.from(tr.querySelectorAll('td')).forEach((td, i) => {
                if(headers[i]) td.setAttribute('data-label', headers[i]);
            });
        });
    });
});
</script>

<script>
$(document).ready(function() {
    if ($.fn.select2) {
        $('.fmh-client-select').select2({
            placeholder: "Busque por nome, email ou empresa...",
            allowClear: true,
            ajax: {
                url: "addonmodules.php?module=fmh_monitor&action=search_clients",
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return { q: params.term };
                },
                processResults: function (data) {
                    return { results: data.results };
                },
                cache: true
            }
        });
    }
});
</script>
