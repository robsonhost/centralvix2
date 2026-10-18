<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

class CentralVixUpdSignatureService
{
    public static function ensureSigningSecret()
    {
        $secret = CentralVixUpdModule::dbSetting('signing_secret');
        if ($secret === '') {
            $secret = bin2hex(random_bytes(32));
            CentralVixUpdModule::setDbSetting('signing_secret', $secret);
        }
        return $secret;
    }

    public static function signHash($sha256Hex)
    {
        $secret = self::ensureSigningSecret();
        return hash_hmac('sha256', strtolower($sha256Hex), $secret);
    }

    public static function verifyHashSignature($sha256Hex, $signature)
    {
        if ($sha256Hex === '' || $signature === '') {
            return false;
        }
        $expected = self::signHash($sha256Hex);
        return hash_equals($expected, $signature);
    }

    public static function signApiResponse($body, $licenseKey = null)
    {
        if ($licenseKey) {
            $key = hash('sha256', 'cvix-upd-response-v1|' . $licenseKey);
            return hash_hmac('sha256', $body, $key);
        }
        $secret = self::ensureSigningSecret();
        return hash_hmac('sha256', $body, hash('sha256', 'cvix-upd-public|' . $secret));
    }
}
