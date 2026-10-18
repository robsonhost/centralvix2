<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

class CentralVixMigModule
{
    const MODULE = 'centralvix_migracao_hospedagem';
    const TOKEN_ADMIN = 'CentralVixMigAdmin';
    const TOKEN_CLIENT = 'CentralVixMigClient';
    const VERSION = '1.0.0';
    const AJAX_PARAM = 'cvix_mig_ajax';
    const DOWNLOAD_PARAM = 'cvix_mig_download';

    public static function path()
    {
        return dirname(__DIR__);
    }

    public static function autoload()
    {
        $files = [
            'Logger.php', 'CredentialVault.php', 'ChecklistService.php', 'FileService.php',
            'NotificationService.php', 'MigrationService.php',
            'providers/MigrationProviderInterface.php', 'providers/ManualMigrationProvider.php',
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
        $row = WHMCS\Database\Capsule::table('mod_centralvix_migration_settings')
            ->where('setting_key', $key)->first();
        return $row ? $row->setting_value : $default;
    }

    public static function setDbSetting($key, $value)
    {
        WHMCS\Database\Capsule::table('mod_centralvix_migration_settings')->updateOrInsert(
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

    public static function systemUrl()
    {
        return rtrim($GLOBALS['CONFIG']['SystemURL'] ?? '', '/');
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
        foreach (['uploads', 'logs'] as $dir) {
            $p = self::storagePath($dir);
            if (!is_dir($p)) {
                @mkdir($p, 0750, true);
            }
        }
    }

    public static function clientName($id)
    {
        $c = WHMCS\Database\Capsule::table('tblclients')->where('id', (int) $id)->first();
        return $c ? trim($c->firstname . ' ' . $c->lastname) : 'Cliente';
    }

    public static function panels()
    {
        return [
            'cpanel' => 'cPanel',
            'plesk' => 'Plesk',
            'directadmin' => 'DirectAdmin',
            'cyberpanel' => 'CyberPanel',
            'outro' => 'Outro',
        ];
    }

    public static function statuses()
    {
        return [
            'solicitada' => 'Solicitada',
            'em_analise' => 'Em análise',
            'aguardando_cliente' => 'Aguardando cliente',
            'agendada' => 'Agendada',
            'backup' => 'Backup',
            'migrando_arquivos' => 'Migrando arquivos',
            'migrando_banco' => 'Migrando banco',
            'migrando_emails' => 'Migrando e-mails',
            'testando' => 'Testando',
            'aguardando_dns' => 'Aguardando DNS',
            'monitorando' => 'Monitorando',
            'concluida' => 'Concluída',
            'falhou' => 'Falhou',
            'cancelada' => 'Cancelada',
        ];
    }

    public static function clientTimelineSteps()
    {
        return [
            ['key' => 'received', 'label' => 'Solicitação recebida', 'statuses' => ['solicitada']],
            ['key' => 'review', 'label' => 'Análise técnica', 'statuses' => ['em_analise', 'aguardando_cliente']],
            ['key' => 'scheduled', 'label' => 'Agendamento', 'statuses' => ['agendada']],
            ['key' => 'migration', 'label' => 'Migração em andamento', 'statuses' => ['backup', 'migrando_arquivos', 'migrando_banco', 'migrando_emails', 'testando']],
            ['key' => 'dns', 'label' => 'DNS e propagação', 'statuses' => ['aguardando_dns', 'monitorando']],
            ['key' => 'done', 'label' => 'Concluída', 'statuses' => ['concluida']],
        ];
    }

    public static function migrationProvider()
    {
        $class = self::dbSetting('migration_provider', 'manual');
        if ($class === 'manual' || $class === '') {
            return new CentralVixMigManualMigrationProvider();
        }
        return new CentralVixMigManualMigrationProvider();
    }
}

class CentralVixMigSecurity
{
    public static function e($str)
    {
        return htmlspecialchars((string) $str, ENT_QUOTES, 'UTF-8');
    }

    public static function sanitizeText($str, $max = 500)
    {
        return mb_substr(trim(strip_tags((string) $str)), 0, $max);
    }

    public static function sanitizeHtml($str, $max = 5000)
    {
        return mb_substr(trim(strip_tags((string) $str, '<p><br><ul><ol><li><strong><em>')), 0, $max);
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
        return (bool) preg_match('/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$/i', $domain);
    }

    public static function validatePanel($panel)
    {
        return array_key_exists($panel, CentralVixMigModule::panels()) ? $panel : 'outro';
    }

    public static function validateStatus($status)
    {
        return array_key_exists($status, CentralVixMigModule::statuses()) ? $status : null;
    }

    public static function redactSecrets($text)
    {
        $text = (string) $text;
        $text = preg_replace('/password["\']?\s*[:=]\s*["\']?[^\s"\']+/i', 'password=[REDACTED]', $text);
        $text = preg_replace('/senha["\']?\s*[:=]\s*["\']?[^\s"\']+/i', 'senha=[REDACTED]', $text);
        return $text;
    }

    public static function isAdminSession()
    {
        return !empty($_SESSION['adminid']);
    }

    public static function adminId()
    {
        return (int) ($_SESSION['adminid'] ?? 0);
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
}
