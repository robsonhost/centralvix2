<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

class CentralVixLicModule
{
    const MODULE = 'centralvix_licenciamento';
    const TOKEN_ADMIN = 'CentralVixLicAdmin';
    const TOKEN_CLIENT = 'CentralVixLicClient';
    const VERSION = '1.0.0';

    public static function path()
    {
        return dirname(__DIR__);
    }

    public static function autoload()
    {
        $files = [
            'Logger.php', 'EncryptionService.php', 'NotificationService.php',
            'ProductService.php', 'LicenseGenerator.php', 'ValidationService.php',
            'ApiService.php', 'LicenseService.php',
        ];
        foreach ($files as $f) {
            $p = self::path() . '/lib/' . $f;
            if (is_file($p)) {
                require_once $p;
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
        $row = WHMCS\Database\Capsule::table('mod_centralvix_license_settings')->where('setting_key', $key)->first();
        return $row ? $row->setting_value : $default;
    }

    public static function setDbSetting($key, $value)
    {
        WHMCS\Database\Capsule::table('mod_centralvix_license_settings')->updateOrInsert(
            ['setting_key' => $key],
            ['setting_value' => $value, 'updated_at' => date('Y-m-d H:i:s')]
        );
    }

    public static function csrfToken($area = 'admin')
    {
        $key = $area === 'client' ? self::TOKEN_CLIENT : self::TOKEN_ADMIN;
        if (function_exists('generate_token')) {
            $t = generate_token('plain');
            $_SESSION[$key] = $t;
            return $t;
        }
        if (empty($_SESSION[$key])) {
            $_SESSION[$key] = bin2hex(random_bytes(32));
        }
        return $_SESSION[$key];
    }

    public static function validateCsrf($token, $area = 'admin')
    {
        $key = $area === 'client' ? self::TOKEN_CLIENT : self::TOKEN_ADMIN;
        if (empty($token) || empty($_SESSION[$key])) {
            return false;
        }
        return hash_equals((string) $_SESSION[$key], (string) $token);
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

    public static function renderAdmin($view, array $vars = [])
    {
        extract($vars);
        include self::path() . '/templates/admin_layout_header.php';
        include self::path() . '/admin/' . $view . '.php';
        include self::path() . '/templates/admin_layout_footer.php';
    }

    public static function renderClient($view, array $vars = [])
    {
        extract($vars);
        include self::path() . '/client/' . $view . '.php';
    }

    public static function clientName($id)
    {
        $c = WHMCS\Database\Capsule::table('tblclients')->where('id', (int) $id)->first();
        return $c ? trim($c->firstname . ' ' . $c->lastname) : 'Cliente';
    }

    public static function systemUrl()
    {
        return rtrim($GLOBALS['CONFIG']['SystemURL'] ?? '', '/');
    }

    public static function apiBaseUrl()
    {
        return self::systemUrl() . '/modules/addons/centralvix_licenciamento/api';
    }
}

class CentralVixLicSecurity
{
    public static function e($str)
    {
        return htmlspecialchars((string) $str, ENT_QUOTES, 'UTF-8');
    }

    public static function sanitizeText($str, $max = 500)
    {
        return mb_substr(trim(strip_tags((string) $str)), 0, $max);
    }

    public static function clientIp()
    {
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
        }
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    public static function validateDomain($domain)
    {
        $domain = strtolower(trim($domain));
        if ($domain === '') {
            return false;
        }
        return (bool) preg_match('/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$/i', $domain)
            || $domain === 'localhost';
    }

    public static function validateIp($ip)
    {
        return filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }

    public static function maskLicenseKey($key)
    {
        if (strlen($key) < 12) {
            return '****';
        }
        return substr($key, 0, 4) . '-****-****-' . substr($key, -4);
    }
}
