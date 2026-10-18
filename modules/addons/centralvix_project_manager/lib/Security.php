<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

class CentralVixPmModule
{
    const MODULE = 'centralvix_project_manager';
    const TOKEN_ADMIN = 'CentralVixPmAdmin';
    const TOKEN_CLIENT = 'CentralVixPmClient';
    const VERSION = '1.0.0';

    public static function path()
    {
        return dirname(__DIR__);
    }

    public static function storagePath()
    {
        return self::path() . '/storage';
    }

    public static function autoload()
    {
        $files = [
            'Logger.php', 'NotificationService.php', 'CommentService.php',
            'FileService.php', 'TimeService.php', 'MilestoneService.php',
            'TaskService.php', 'ProjectService.php',
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
        $row = WHMCS\Database\Capsule::table('mod_centralvix_pm_settings')->where('setting_key', $key)->first();
        return $row ? $row->setting_value : $default;
    }

    public static function setDbSetting($key, $value)
    {
        WHMCS\Database\Capsule::table('mod_centralvix_pm_settings')->updateOrInsert(
            ['setting_key' => $key],
            ['setting_value' => is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value, 'updated_at' => date('Y-m-d H:i:s')]
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

    public static function ajaxUrl($action, $client = false)
    {
        if ($client) {
            return 'index.php?m=' . self::MODULE . '&cvix_pm_ajax=' . urlencode($action);
        }
        return 'addonmodules.php?module=' . self::MODULE . '&cvix_pm_ajax=' . urlencode($action);
    }

    public static function downloadUrl($fileId, $client = false)
    {
        if ($client) {
            return 'index.php?m=' . self::MODULE . '&cvix_pm_download=' . (int) $fileId;
        }
        return 'addonmodules.php?module=' . self::MODULE . '&cvix_pm_download=' . (int) $fileId;
    }

    public static function renderAdmin($view, array $vars = [])
    {
        extract($vars);
        include self::path() . '/templates/admin_layout_header.php';
        $f = self::path() . '/admin/' . $view . '.php';
        if (is_file($f)) {
            include $f;
        }
        include self::path() . '/templates/admin_layout_footer.php';
    }

    public static function renderClient($view, array $vars = [])
    {
        extract($vars);
        $f = self::path() . '/client/' . $view . '.php';
        if (is_file($f)) {
            include $f;
        }
    }

    public static function adminName($id = 0)
    {
        if (!$id) {
            return 'Sistema';
        }
        $a = WHMCS\Database\Capsule::table('tbladmins')->where('id', (int) $id)->first();
        return $a ? trim($a->firstname . ' ' . $a->lastname) : 'Admin';
    }

    public static function clientName($id)
    {
        $c = WHMCS\Database\Capsule::table('tblclients')->where('id', (int) $id)->first();
        return $c ? trim($c->firstname . ' ' . $c->lastname) : 'Cliente';
    }

    public static function isAdmin()
    {
        return !empty($_SESSION['adminid']);
    }

    public static function isClient()
    {
        return !empty($_SESSION['uid']) && empty($_SESSION['adminid']);
    }
}

class CentralVixPmSecurity
{
    public static function e($str)
    {
        return htmlspecialchars((string) $str, ENT_QUOTES, 'UTF-8');
    }

    public static function sanitizeText($str, $max = 5000)
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

    public static function safeFilename($name)
    {
        return preg_replace('/[^a-zA-Z0-9._-]/', '_', basename((string) $name));
    }
}
