<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

class CentralVixLicLicenseGenerator
{
    public static function generate()
    {
        $parts = [];
        for ($i = 0; $i < 4; $i++) {
            $parts[] = strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
        }
        return 'CVIX-' . implode('-', $parts);
    }

    public static function generateUnique()
    {
        do {
            $key = self::generate();
            $exists = WHMCS\Database\Capsule::table('mod_centralvix_licenses')
                ->where('license_key_hash', CentralVixLicEncryptionService::hashLicenseKeyForLookup($key))
                ->exists();
        } while ($exists);
        return $key;
    }
}
