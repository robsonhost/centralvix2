<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

class CentralVixSignHashService
{
    public static function fileHash($path)
    {
        if (!is_file($path)) {
            return '';
        }
        return hash_file('sha256', $path);
    }

    public static function stringHash($data)
    {
        return hash('sha256', (string) $data);
    }

    public static function verifyFile($path, $expectedHash)
    {
        if ($expectedHash === '') {
            return false;
        }
        return hash_equals($expectedHash, self::fileHash($path));
    }
}
