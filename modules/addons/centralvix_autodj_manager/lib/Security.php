<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

class CentralVixAutoDJModule
{
    const MODULE = 'centralvix_autodj_manager';
    const TOKEN_ADMIN = 'CentralVixAutoDJAdmin';
    const TOKEN_CLIENT = 'CentralVixAutoDJClient';
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
            'Security.php',
            'Logger.php',
            'AudioValidator.php',
            'StorageService.php',
            'StreamingConnector.php',
            'MediaLibrary.php',
            'PlaylistService.php',
            'ScheduleService.php',
            'AutoDJService.php',
        ];
        foreach ($files as $f) {
            $p = self::path() . '/lib/' . $f;
            if ($f !== 'Security.php' && is_file($p)) {
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
        $row = WHMCS\Database\Capsule::table('mod_centralvix_autodj_settings')->where('setting_key', $key)->first();
        return $row ? $row->setting_value : $default;
    }

    public static function setDbSetting($key, $value)
    {
        WHMCS\Database\Capsule::table('mod_centralvix_autodj_settings')->updateOrInsert(
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

    public static function requireAdmin()
    {
        if (empty($_SESSION['adminid'])) {
            self::json(['success' => false, 'message' => 'Acesso negado.'], 403);
        }
    }

    public static function requireClient()
    {
        if (empty($_SESSION['uid'])) {
            self::json(['success' => false, 'message' => 'Login necessário.'], 403);
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

    public static function ajaxUrl($action, $client = false)
    {
        if ($client) {
            return 'index.php?m=' . self::MODULE . '&cvix_autodj_ajax=' . urlencode($action);
        }
        return 'addonmodules.php?module=' . self::MODULE . '&cvix_autodj_ajax=' . urlencode($action);
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

    public static function providerTypes()
    {
        return [
            'none' => 'Somente local (sem sync remoto)',
            'azuracast' => 'AzuraCast',
            'centovacast' => 'Centova Cast',
            'sonicpanel' => 'SonicPanel',
        ];
    }

    public static function mediaTypes()
    {
        return [
            'music' => self::lang('type_music', 'Música'),
            'jingle' => self::lang('type_jingle', 'Vinheta'),
            'commercial' => self::lang('type_commercial', 'Bloco comercial'),
            'programete' => self::lang('type_programete', 'Programete'),
        ];
    }
}

class CentralVixAutoDJSecurity
{
    public static function e($str)
    {
        return htmlspecialchars((string) $str, ENT_QUOTES, 'UTF-8');
    }

    public static function sanitizeText($str, $max = 500)
    {
        $str = strip_tags((string) $str);
        $str = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $str);
        return mb_substr(trim($str), 0, $max);
    }

    public static function clientIp()
    {
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
        }
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    public static function sanitizeLog($text)
    {
        return CentralVixAutoDJSecurity::sanitizeText(preg_replace('/(password|token|secret)=[^\s&]+/i', '$1=[REDACTED]', (string) $text), 2000);
    }

    public static function safeFilename($name)
    {
        $name = basename((string) $name);
        $name = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $name);
        return mb_substr($name, 0, 200);
    }

    public static function rejectPathTraversal($path)
    {
        if (strpos($path, '..') !== false || strpos($path, chr(0)) !== false) {
            return false;
        }
        return true;
    }
}
