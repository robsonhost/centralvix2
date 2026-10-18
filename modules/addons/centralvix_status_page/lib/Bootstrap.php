<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

class CentralVixStatusModule
{
    const MODULE = 'centralvix_status_page';
    const TOKEN = 'CentralVixStatusPage';
    const VERSION = '1.0.0';

    public static function path()
    {
        return dirname(__DIR__);
    }

    public static function autoload()
    {
        $files = [
            'Security.php',
            'StatusService.php',
            'UptimeService.php',
            'MonitorService.php',
            'IncidentService.php',
            'MaintenanceService.php',
            'NotificationService.php',
        ];
        foreach ($files as $f) {
            require_once self::path() . '/lib/' . $f;
        }
    }

    public static function lang($key, $default = '')
    {
        static $lang = null;
        if ($lang === null) {
            $file = self::path() . '/lang/portuguese-br.php';
            $lang = file_exists($file) ? include $file : [];
        }
        return isset($lang[$key]) ? $lang[$key] : ($default ?: $key);
    }

    public static function getStatuses()
    {
        return [
            'operational' => self::lang('status_operational', 'Operacional'),
            'degraded' => self::lang('status_degraded', 'Degradação de desempenho'),
            'instability' => self::lang('status_instability', 'Instabilidade'),
            'partial_outage' => self::lang('status_partial', 'Interrupção parcial'),
            'major_outage' => self::lang('status_major', 'Interrupção total'),
            'maintenance' => self::lang('status_maintenance', 'Manutenção programada'),
            'investigating' => self::lang('status_investigating', 'Investigando'),
            'monitoring' => self::lang('status_monitoring', 'Monitorando'),
            'resolved' => self::lang('status_resolved', 'Resolvido'),
        ];
    }

    public static function getCategories()
    {
        return [
            'hosting' => self::lang('cat_hosting', 'Hospedagem'),
            'vps' => self::lang('cat_vps', 'VPS'),
            'dedicated' => self::lang('cat_dedicated', 'Servidores Dedicados'),
            'streaming_audio' => self::lang('cat_streaming_audio', 'Streaming de Áudio'),
            'streaming_video' => self::lang('cat_streaming_video', 'Streaming de Vídeo'),
            'sites' => self::lang('cat_sites', 'Sites'),
            'api' => self::lang('cat_api', 'APIs'),
            'payment' => self::lang('cat_payment', 'Pagamentos'),
            'whatsapp' => self::lang('cat_whatsapp', 'WhatsApp / Comunicações'),
            'dns' => self::lang('cat_dns', 'DNS'),
            'database' => self::lang('cat_database', 'Banco de Dados'),
            'custom' => self::lang('cat_custom', 'Personalizados'),
        ];
    }

    public static function getMonitorTypes()
    {
        return [
            'http' => 'HTTP',
            'https' => 'HTTPS',
            'tcp' => 'TCP',
            'ping' => 'PING',
            'port' => 'Porta TCP',
            'icecast' => 'Icecast',
            'shoutcast' => 'Shoutcast',
            'api_json' => 'API JSON',
            'custom_url' => 'URL Personalizada',
        ];
    }

    public static function moduleSettings()
    {
        $s = [];
        foreach (WHMCS\Database\Capsule::table('tbladdonmodules')->where('module', self::MODULE)->get() as $r) {
            $s[$r->setting] = $r->value;
        }
        return $s;
    }

    public static function setting($key, $default = '')
    {
        $s = self::moduleSettings();
        return isset($s[$key]) ? $s[$key] : $default;
    }

    public static function dbSetting($key, $default = '')
    {
        $row = WHMCS\Database\Capsule::table('mod_centralvix_status_settings')->where('setting_key', $key)->first();
        return $row ? $row->setting_value : $default;
    }

    public static function setDbSetting($key, $value)
    {
        WHMCS\Database\Capsule::table('mod_centralvix_status_settings')->updateOrInsert(
            ['setting_key' => $key],
            ['setting_value' => $value, 'updated_at' => date('Y-m-d H:i:s')]
        );
    }

    public static function csrfToken()
    {
        if (function_exists('generate_token')) {
            $t = generate_token('plain');
            $_SESSION[self::TOKEN] = $t;
            return $t;
        }
        if (empty($_SESSION[self::TOKEN])) {
            $_SESSION[self::TOKEN] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::TOKEN];
    }

    public static function validateCsrf($token)
    {
        if (empty($token) || empty($_SESSION[self::TOKEN])) {
            return false;
        }
        return hash_equals((string) $_SESSION[self::TOKEN], (string) $token);
    }

    public static function requireAdmin()
    {
        if (empty($_SESSION['adminid'])) {
            self::json(['success' => false, 'message' => 'Acesso negado.'], 403);
        }
    }

    public static function json(array $data, $code = 200)
    {
        while (ob_get_level() > 0) {
            @ob_end_clean();
        }
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function ajaxUrl($action)
    {
        return 'addonmodules.php?module=' . self::MODULE . '&cvix_status_ajax=' . urlencode($action);
    }

    public static function publicStatusUrl()
    {
        return '../modules/addons/centralvix_status_page/public/status.php';
    }

    public static function logAction($action, $serviceId, $result, $details = '')
    {
        WHMCS\Database\Capsule::table('mod_centralvix_status_history')->insert([
            'admin_id' => (int) ($_SESSION['adminid'] ?? 0),
            'action' => substr($action, 0, 100),
            'service_id' => (int) $serviceId,
            'ip_address' => substr(CentralVixStatusSecurity::clientIp(), 0, 45),
            'result' => substr($result, 0, 50),
            'details' => substr($details, 0, 1000),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function renderAdmin($view, array $vars = [])
    {
        extract($vars);
        $lang = include self::path() . '/lang/portuguese-br.php';
        include self::path() . '/templates/admin_layout_header.php';
        $viewFile = self::path() . '/admin/' . $view . '.php';
        if (file_exists($viewFile)) {
            include $viewFile;
        }
        include self::path() . '/templates/admin_layout_footer.php';
    }

    public static function statusBadgeClass($status)
    {
        $map = [
            'operational' => 'cvix-sp-ok',
            'degraded' => 'cvix-sp-warn',
            'instability' => 'cvix-sp-warn',
            'partial_outage' => 'cvix-sp-danger',
            'major_outage' => 'cvix-sp-danger',
            'maintenance' => 'cvix-sp-info',
            'investigating' => 'cvix-sp-warn',
            'monitoring' => 'cvix-sp-info',
            'resolved' => 'cvix-sp-ok',
        ];
        return isset($map[$status]) ? $map[$status] : 'cvix-sp-muted';
    }
}
