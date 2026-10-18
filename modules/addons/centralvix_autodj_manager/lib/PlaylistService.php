<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

class CentralVixAutoDJPlaylistService
{
    public static function listPlaylists($accountId, $clientId)
    {
        CentralVixAutoDJService::assertAccountOwner($accountId, $clientId, !empty($_SESSION['adminid']));
        return Capsule::table('mod_centralvix_autodj_playlists')
            ->where('account_id', (int) $accountId)
            ->where('client_id', (int) $clientId)
            ->orderBy('name')
            ->get();
    }

    public static function get($playlistId, $clientId = 0, $admin = false)
    {
        $q = Capsule::table('mod_centralvix_autodj_playlists')->where('id', (int) $playlistId);
        if (!$admin && $clientId) {
            $q->where('client_id', (int) $clientId);
        }
        return $q->first();
    }

    public static function save(array $data, $clientId, $id = 0)
    {
        $accountId = (int) ($data['account_id'] ?? 0);
        CentralVixAutoDJService::assertAccountOwner($accountId, $clientId, !empty($_SESSION['adminid']));
        $row = [
            'account_id' => $accountId,
            'client_id' => (int) $clientId,
            'name' => CentralVixAutoDJSecurity::sanitizeText($data['name'] ?? '', 150),
            'description' => CentralVixAutoDJSecurity::sanitizeText($data['description'] ?? '', 500),
            'playlist_type' => preg_replace('/[^a-z_]/', '', $data['playlist_type'] ?? 'general'),
            'random_mode' => !empty($data['random_mode']) ? 1 : 0,
            'active' => !empty($data['active']) ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if ($id) {
            $pl = self::get($id, $clientId, !empty($_SESSION['adminid']));
            if (!$pl || (int) $pl->client_id !== (int) $clientId && empty($_SESSION['adminid'])) {
                return ['success' => false, 'message' => 'Playlist não encontrada.'];
            }
            Capsule::table('mod_centralvix_autodj_playlists')->where('id', (int) $id)->update($row);
            $playlistId = (int) $id;
        } else {
            $row['created_at'] = date('Y-m-d H:i:s');
            $playlistId = (int) Capsule::table('mod_centralvix_autodj_playlists')->insertGetId($row);
        }
        if (isset($data['media_ids']) && is_array($data['media_ids'])) {
            Capsule::table('mod_centralvix_autodj_playlist_items')->where('playlist_id', $playlistId)->delete();
            $order = 0;
            foreach ($data['media_ids'] as $mid) {
                $mid = (int) $mid;
                $media = CentralVixAutoDJMediaLibrary::getMedia($mid, $clientId, !empty($_SESSION['adminid']));
                if ($media && (int) $media->account_id === $accountId) {
                    Capsule::table('mod_centralvix_autodj_playlist_items')->insert([
                        'playlist_id' => $playlistId,
                        'media_id' => $mid,
                        'sort_order' => $order++,
                    ]);
                }
            }
        }
        self::syncProvider($playlistId);
        return ['success' => true, 'id' => $playlistId, 'message' => 'Playlist salva.'];
    }

    public static function getItems($playlistId, $clientId)
    {
        $pl = self::get($playlistId, $clientId, !empty($_SESSION['adminid']));
        if (!$pl) {
            return [];
        }
        return Capsule::table('mod_centralvix_autodj_playlist_items as i')
            ->join('mod_centralvix_autodj_media as m', 'i.media_id', '=', 'm.id')
            ->where('i.playlist_id', (int) $playlistId)
            ->where('m.client_id', (int) $clientId)
            ->orderBy('i.sort_order')
            ->select('i.*', 'm.original_name', 'm.duration_seconds', 'm.media_type')
            ->get();
    }

    public static function reorder($playlistId, array $mediaOrder, $clientId)
    {
        $pl = self::get($playlistId, $clientId, !empty($_SESSION['adminid']));
        if (!$pl) {
            return ['success' => false, 'message' => 'Playlist não encontrada.'];
        }
        foreach ($mediaOrder as $order => $mediaId) {
            Capsule::table('mod_centralvix_autodj_playlist_items')
                ->where('playlist_id', (int) $playlistId)
                ->where('media_id', (int) $mediaId)
                ->update(['sort_order' => (int) $order]);
        }
        self::syncProvider($playlistId);
        return ['success' => true, 'message' => 'Ordem atualizada.'];
    }

    public static function syncProvider($playlistId)
    {
        $pl = Capsule::table('mod_centralvix_autodj_playlists')->where('id', (int) $playlistId)->first();
        if (!$pl) {
            return;
        }
        $account = CentralVixAutoDJService::getAccount($pl->account_id);
        $items = Capsule::table('mod_centralvix_autodj_playlist_items')->where('playlist_id', (int) $playlistId)->orderBy('sort_order')->get();
        $provider = CentralVixAutoDJStreamingConnector::provider($account);
        if ($provider) {
            $provider->syncPlaylist($account, $pl, $items->all());
        }
    }
}
