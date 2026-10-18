<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

class CentralVixAutoDJMediaLibrary
{
    public static function listMedia($accountId, $clientId, $type = null)
    {
        CentralVixAutoDJService::assertAccountOwner($accountId, $clientId, !empty($_SESSION['adminid']));
        $q = Capsule::table('mod_centralvix_autodj_media')
            ->where('account_id', (int) $accountId)
            ->where('client_id', (int) $clientId)
            ->orderBy('created_at', 'desc');
        if ($type) {
            $q->where('media_type', preg_replace('/[^a-z_]/', '', $type));
        }
        return $q->get();
    }

    public static function getMedia($mediaId, $clientId = 0, $admin = false)
    {
        $q = Capsule::table('mod_centralvix_autodj_media')->where('id', (int) $mediaId);
        if (!$admin && $clientId) {
            $q->where('client_id', (int) $clientId);
        }
        return $q->first();
    }

    public static function countByAccount($accountId)
    {
        return (int) Capsule::table('mod_centralvix_autodj_media')->where('account_id', (int) $accountId)->count();
    }

    public static function syncToProvider($accountId, $mediaId)
    {
        $account = CentralVixAutoDJService::getAccount($accountId);
        $media = Capsule::table('mod_centralvix_autodj_media')->where('id', (int) $mediaId)->first();
        if (!$account || !$media) {
            return;
        }
        $provider = CentralVixAutoDJStreamingConnector::provider($account);
        $path = CentralVixAutoDJStorageService::absolutePath($media);
        if ($provider && $path) {
            $r = $provider->uploadMedia($account, $media, $path);
            if (!$r['success']) {
                CentralVixAutoDJLogger::log('provider_upload', 'skipped', $accountId, $media->client_id, $r['message']);
            }
        }
    }

    public static function deleteFromProvider($accountId, $media)
    {
        $account = CentralVixAutoDJService::getAccount($accountId);
        if (!$account || !$media) {
            return;
        }
        $provider = CentralVixAutoDJStreamingConnector::provider($account);
        if ($provider) {
            $provider->deleteMedia($account, $media);
        }
    }

    public static function recentHistory($accountId, $clientId, $limit = 20)
    {
        return self::listMedia($accountId, $clientId, null)->take($limit);
    }
}
