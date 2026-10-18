<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

return [
    'module_name' => 'CentralVix Régua de Cobrança',
    'module_desc' => 'Automação de lembretes de cobrança por e-mail e WhatsApp',
    'default_company' => 'CentralVix',
    'tab_dashboard' => 'Dashboard',
    'tab_rules' => 'Réguas',
    'tab_templates' => 'Templates',
    'tab_history' => 'Histórico',
    'tab_settings' => 'Configurações',
    'timing_before' => 'Antes do vencimento',
    'timing_on' => 'No vencimento',
    'timing_after' => 'Após o vencimento',
    'channel_email' => 'E-mail',
    'channel_whatsapp' => 'WhatsApp',
    'status_success' => 'Sucesso',
    'status_failed' => 'Falha',
    'saved' => 'Salvo com sucesso',
    'csrf_invalid' => 'Token CSRF inválido',
    'access_denied' => 'Acesso negado',
];
