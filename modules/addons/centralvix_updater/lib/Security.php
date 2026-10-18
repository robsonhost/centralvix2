<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

class CentralVixUpdModule
{
    const MODULE = 'centralvix_updater';
    const TOKEN_ADMIN = 'CentralVixUpdAdmin';
    const VERSION = '1.0.0';

    public static function path()
    {
        return dirname(__DIR__);
    }

    public static function autoload()
    {
        $files = [
            'Logger.php', 'SignatureService.php', 'ProductService.php', 'VersionService.php',
            'PackageService.php', 'LicenseConnector.php', 'DownloadService.php',
            'UpdateService.php',
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
        $row = WHMCS\Database\Capsule::table('mod_centralvix_updater_settings')
            ->where('setting_key', $key)->first();
        return $row ? $row->setting_value : $default;
    }

    public static function setDbSetting($key, $value)
    {
        WHMCS\Database\Capsule::table('mod_centralvix_updater_settings')->updateOrInsert(
            ['setting_key' => $key],
            ['setting_value' => $value, 'updated_at' => date('Y-m-d H:i:s')]
        );
    }

    public static function csrfToken()
    {
        if (function_exists('generate_token')) {
            $t = generate_token('plain');
            $_SESSION[self::TOKEN_ADMIN] = $t;
            return $t;
        }
        if (empty($_SESSION[self::TOKEN_ADMIN])) {
            $_SESSION[self::TOKEN_ADMIN] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::TOKEN_ADMIN];
    }

    public static function validateCsrf($token)
    {
        if (empty($token) || empty($_SESSION[self::TOKEN_ADMIN])) {
            return false;
        }
        return hash_equals((string) $_SESSION[self::TOKEN_ADMIN], (string) $token);
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

    public static function systemUrl()
    {
        return rtrim($GLOBALS['CONFIG']['SystemURL'] ?? '', '/');
    }

    public static function apiBaseUrl()
    {
        return self::systemUrl() . '/modules/addons/centralvix_updater/api';
    }

    public static function storagePath($sub = '')
    {
        $base = self::path() . '/storage';
        if ($sub !== '') {
            $sub = str_replace(['..', '\\'], ['', '/'], $sub);
            $base .= '/' . ltrim($sub, '/');
        }
        return $base;
    }

    public static function ensureStorageDirs()
    {
        foreach (['packages', 'cache', 'logs'] as $dir) {
            $p = self::storagePath($dir);
            if (!is_dir($p)) {
                @mkdir($p, 0750, true);
            }
        }
    }
}

class CentralVixUpdSecurity
{
    public static function e($str)
    {
        return htmlspecialchars((string) $str, ENT_QUOTES, 'UTF-8');
    }

    public static function sanitizeText($str, $max = 500)
    {
        return mb_substr(trim(strip_tags((string) $str)), 0, $max);
    }

    public static function sanitizeSlug($slug)
    {
        $slug = strtolower(trim((string) $slug));
        $slug = preg_replace('/[^a-z0-9\-_]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        return trim($slug, '-');
    }

    public static function sanitizeVersion($version)
    {
        $version = trim((string) $version);
        if (!preg_match('/^[0-9]+(\.[0-9]+)*(-[a-zA-Z0-9.]+)?$/', $version)) {
            return '';
        }
        return $version;
    }

    public static function clientIp()
    {
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
        }
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    public static function validateChannel($channel)
    {
        return in_array($channel, ['stable', 'beta'], true) ? $channel : 'stable';
    }

    public static function safeBasename($name)
    {
        $name = basename(str_replace('\\', '/', (string) $name));
        return preg_replace('/[^a-zA-Z0-9._\-]/', '_', $name);
    }

    public static function isPathInside($path, $base)
    {
        $realBase = realpath($base);
        $realPath = realpath($path);
        if ($realBase === false || $realPath === false) {
            return false;
        }
        return strpos($realPath, $realBase) === 0;
    }

    public static function scanZipForSlip($zipPath)
    {
        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return ['ok' => false, 'message' => 'Arquivo ZIP inválido.'];
        }
        $tmpExtract = CentralVixUpdModule::storagePath('cache/zipscan_' . bin2hex(random_bytes(8)));
        @mkdir($tmpExtract, 0750, true);
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = $zip->getNameIndex($i);
            if ($entry === false) {
                continue;
            }
            if (strpos($entry, '../') !== false || strpos($entry, '..\\') !== false || $entry[0] === '/') {
                $zip->close();
                self::removeDir($tmpExtract);
                return ['ok' => false, 'message' => 'ZIP contém caminhos inseguros (ZIP Slip).'];
            }
        }
        $zip->close();
        self::removeDir($tmpExtract);
        return ['ok' => true];
    }

    public static function removeDir($dir)
    {
        if (!is_dir($dir)) {
            return;
        }
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                self::removeDir($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }
}
