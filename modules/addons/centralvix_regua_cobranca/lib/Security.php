<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

class CentralVixReguaModule
{
    const MODULE = 'centralvix_regua_cobranca';
    const TOKEN = 'CentralVixReguaCobranca';
    const VERSION = '1.0.0';

    public static function path()
    {
        return dirname(__DIR__);
    }

    public static function autoload()
    {
        $files = [
            'Security.php',
            'Logger.php',
            'TemplateService.php',
            'InvoiceService.php',
            'BillingRuleService.php',
            'WhatsAppService.php',
            'NotificationService.php',
            'ExecutionService.php',
        ];
        foreach ($files as $f) {
            $path = self::path() . '/lib/' . $f;
            if ($f !== 'Security.php' && is_file($path)) {
                require_once $path;
            }
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
        $row = WHMCS\Database\Capsule::table('mod_centralvix_regua_settings')->where('setting_key', $key)->first();
        return $row ? $row->setting_value : $default;
    }

    public static function setDbSetting($key, $value)
    {
        WHMCS\Database\Capsule::table('mod_centralvix_regua_settings')->updateOrInsert(
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
        return 'addonmodules.php?module=' . self::MODULE . '&cvix_regua_ajax=' . urlencode($action);
    }

    public static function systemUrl()
    {
        $url = self::setting('system_url', '');
        if ($url !== '') {
            return rtrim($url, '/');
        }
        if (!empty($GLOBALS['CONFIG']['SystemURL'])) {
            return rtrim($GLOBALS['CONFIG']['SystemURL'], '/');
        }
        return '';
    }

    public static function renderAdmin($view, array $vars = [])
    {
        extract($vars);
        include self::path() . '/templates/admin_layout_header.php';
        $viewFile = self::path() . '/admin/' . $view . '.php';
        if (file_exists($viewFile)) {
            include $viewFile;
        }
        include self::path() . '/templates/admin_layout_footer.php';
    }

    public static function isWithinTimeWindow($from, $to)
    {
        $now = date('H:i');
        $from = substr($from ?: '08:00', 0, 5);
        $to = substr($to ?: '20:00', 0, 5);
        if ($from <= $to) {
            return $now >= $from && $now <= $to;
        }
        return $now >= $from || $now <= $to;
    }

    public static function idempotencyKey($invoiceId, $ruleId, $date = null)
    {
        $date = $date ?: date('Y-m-d');
        return $invoiceId . '_' . $ruleId . '_' . $date;
    }
}

class CentralVixReguaSecurity
{
    public static function e($str)
    {
        return htmlspecialchars((string) $str, ENT_QUOTES, 'UTF-8');
    }

    public static function sanitizeText($str, $max = 5000)
    {
        $str = strip_tags((string) $str);
        $str = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $str);
        return mb_substr(trim($str), 0, $max);
    }

    public static function sanitizeHtml($str, $max = 10000)
    {
        $allowed = '<p><br><b><strong><i><em><u><a><ul><ol><li><span>';
        $str = strip_tags((string) $str, $allowed);
        return mb_substr(trim($str), 0, $max);
    }

    public static function sanitizeJsonIds($value)
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                $value = $decoded;
            }
        }
        if (!is_array($value)) {
            return [];
        }
        return array_values(array_unique(array_filter(array_map('intval', $value))));
    }

    public static function maskSecret($value)
    {
        $value = (string) $value;
        if (strlen($value) <= 4) {
            return '****';
        }
        return str_repeat('*', strlen($value) - 4) . substr($value, -4);
    }

    public static function sanitizeLogResponse($response)
    {
        $response = (string) $response;
        $response = preg_replace('/Bearer\s+\S+/i', 'Bearer [REDACTED]', $response);
        $response = preg_replace('/"token"\s*:\s*"[^"]+"/i', '"token":"[REDACTED]"', $response);
        return self::sanitizeText($response, 2000);
    }
}
