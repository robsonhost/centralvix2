<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

class CentralVixStreamingModule
{
    const MODULE = 'centralvix_streaming_manager';
    const TOKEN = 'CentralVixStreamingManager';
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
            'StreamingServer.php',
            'ShoutcastProvider.php',
            'IcecastProvider.php',
            'VideoProvider.php',
            'StatisticsService.php',
            'StreamingService.php',
            'ProvisioningService.php',
            'NotificationService.php',
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
        $row = WHMCS\Database\Capsule::table('mod_centralvix_stream_settings')->where('setting_key', $key)->first();
        return $row ? $row->setting_value : $default;
    }

    public static function setDbSetting($key, $value)
    {
        WHMCS\Database\Capsule::table('mod_centralvix_stream_settings')->updateOrInsert(
            ['setting_key' => $key],
            ['setting_value' => $value, 'updated_at' => date('Y-m-d H:i:s')]
        );
    }

    public static function encryptionKey()
    {
        $seed = self::setting('encryption_seed', '');
        if ($seed === '' && !empty($GLOBALS['CONFIG']['cc_encryption_hash'])) {
            $seed = $GLOBALS['CONFIG']['cc_encryption_hash'];
        }
        if ($seed === '') {
            $seed = 'centralvix-stream-fallback-change-me';
        }
        return hash('sha256', $seed, true);
    }

    public static function encryptSecret($plain)
    {
        if ($plain === '' || $plain === null) {
            return '';
        }
        $iv = random_bytes(16);
        $enc = openssl_encrypt((string) $plain, 'AES-256-CBC', self::encryptionKey(), OPENSSL_RAW_DATA, $iv);
        return base64_encode($iv . $enc);
    }

    public static function decryptSecret($encrypted)
    {
        if ($encrypted === '' || $encrypted === null) {
            return '';
        }
        $raw = base64_decode($encrypted, true);
        if ($raw === false || strlen($raw) < 17) {
            return '';
        }
        $iv = substr($raw, 0, 16);
        $data = substr($raw, 16);
        $dec = openssl_decrypt($data, 'AES-256-CBC', self::encryptionKey(), OPENSSL_RAW_DATA, $iv);
        return $dec === false ? '' : $dec;
    }

    public static function csrfToken($area = 'admin')
    {
        $key = self::TOKEN . '_' . $area;
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
        $key = self::TOKEN . '_' . $area;
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
            return 'index.php?m=' . self::MODULE . '&cvix_stream_ajax=' . urlencode($action);
        }
        return 'addonmodules.php?module=' . self::MODULE . '&cvix_stream_ajax=' . urlencode($action);
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

    public static function renderClient($view, array $vars = [])
    {
        extract($vars);
        $viewFile = self::path() . '/client/' . $view . '.php';
        if (file_exists($viewFile)) {
            include $viewFile;
        }
    }

    public static function serverTypes()
    {
        return [
            'shoutcast' => 'SHOUTcast',
            'icecast' => 'Icecast',
            'video' => 'Vídeo (em breve)',
        ];
    }

    public static function accountStatuses()
    {
        return [
            'active' => self::lang('status_active', 'Ativo'),
            'suspended' => self::lang('status_suspended', 'Suspenso'),
            'cancelled' => self::lang('status_cancelled', 'Cancelado'),
            'pending' => self::lang('status_pending', 'Pendente'),
        ];
    }
}

class CentralVixStreamingSecurity
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

    public static function validateHostname($host)
    {
        $host = trim($host);
        if ($host === '') {
            return false;
        }
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false
                || filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6);
        }
        return (bool) preg_match('/^[a-zA-Z0-9]([a-zA-Z0-9\-\.]{0,253}[a-zA-Z0-9])?$/', $host);
    }

    public static function validatePort($port)
    {
        $port = (int) $port;
        return $port >= 1 && $port <= 65535;
    }

    public static function clientIp()
    {
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $parts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($parts[0]);
        }
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    public static function sanitizeLog($text)
    {
        $text = (string) $text;
        $text = preg_replace('/"(password|pass|token|secret)"\s*:\s*"[^"]+"/i', '"$1":"[REDACTED]"', $text);
        $text = preg_replace('/pass=\S+/i', 'pass=[REDACTED]', $text);
        return self::sanitizeText($text, 2000);
    }
}
