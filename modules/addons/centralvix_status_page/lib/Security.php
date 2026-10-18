<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

class CentralVixStatusSecurity
{
    public static function clientIp()
    {
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $parts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($parts[0]);
        }
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

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

    public static function validatePort($port)
    {
        $port = (int) $port;
        return $port >= 1 && $port <= 65535;
    }

    public static function validateUrl($url, $requireScheme = true)
    {
        $url = trim($url);
        if ($url === '') {
            return false;
        }
        if ($requireScheme && !preg_match('#^https?://#i', $url)) {
            return false;
        }
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }
        return self::isHostAllowed(parse_url($url, PHP_URL_HOST));
    }

    public static function validateHost($host)
    {
        $host = trim($host);
        if ($host === '') {
            return false;
        }
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return self::isIpAllowed($host);
        }
        if (!preg_match('/^[a-zA-Z0-9]([a-zA-Z0-9\-\.]{0,253}[a-zA-Z0-9])?$/', $host)) {
            return false;
        }
        return self::isHostAllowed($host);
    }

    public static function isHostAllowed($host)
    {
        if (!$host) {
            return false;
        }
        $host = strtolower($host);
        if (CentralVixStatusModule::setting('allow_private_targets', '') === 'on') {
            return true;
        }
        if (in_array($host, ['localhost', '127.0.0.1', '::1', '0.0.0.0'], true)) {
            return false;
        }
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return self::isIpAllowed($host);
        }
        $ip = gethostbyname($host);
        if ($ip && $ip !== $host) {
            return self::isIpAllowed($ip);
        }
        return true;
    }

    public static function isIpAllowed($ip)
    {
        if (CentralVixStatusModule::setting('allow_private_targets', '') === 'on') {
            return true;
        }
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return false;
        }
        if (strpos($ip, '169.254.') === 0) {
            return false;
        }
        return true;
    }

    public static function maskSecret($value)
    {
        $value = (string) $value;
        if (strlen($value) <= 4) {
            return '****';
        }
        return str_repeat('*', strlen($value) - 4) . substr($value, -4);
    }

    public static function parseDateTimeInput($value)
    {
        if ($value === '' || $value === null) {
            return date('Y-m-d H:i:s');
        }
        $value = str_replace('T', ' ', trim((string) $value));
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $value)) {
            $value .= ':00';
        }
        $ts = strtotime($value);
        return $ts ? date('Y-m-d H:i:s', $ts) : date('Y-m-d H:i:s');
    }
}
