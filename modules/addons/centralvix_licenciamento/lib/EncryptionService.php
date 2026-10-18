<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

class CentralVixLicEncryptionService
{
    public static function ensurePepper()
    {
        $pepper = CentralVixLicModule::dbSetting('server_pepper', '');
        if ($pepper === '') {
            $pepper = bin2hex(random_bytes(32));
            CentralVixLicModule::setDbSetting('server_pepper', $pepper);
        }
        return $pepper;
    }

    public static function ensureApiSecret()
    {
        $secret = CentralVixLicModule::dbSetting('api_secret', '');
        if ($secret === '') {
            $secret = bin2hex(random_bytes(32));
            CentralVixLicModule::setDbSetting('api_secret', $secret);
        }
        return $secret;
    }

    public static function responseSigningKey($licenseKey)
    {
        return hash('sha256', 'cvix-response-v1|' . $licenseKey);
    }

    public static function signResponseBody($body, $licenseKey)
    {
        return hash_hmac('sha256', $body, self::responseSigningKey($licenseKey));
    }

    public static function verifyResponseSignature($body, $licenseKey, $signature)
    {
        if ($signature === '' || $licenseKey === '') {
            return false;
        }
        $expected = self::signResponseBody($body, $licenseKey);
        return hash_equals($expected, $signature);
    }

    public static function hashLicenseKeyForLookup($licenseKey)
    {
        return hash('sha256', $licenseKey . '|' . self::ensurePepper());
    }
}
