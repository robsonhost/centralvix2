<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

class CentralVixPlayerService
{
    const SOCIAL_NETWORKS = ['instagram', 'facebook', 'youtube', 'tiktok', 'whatsapp', 'website'];

    public static function ensureStorage()
    {
        $root = CentralVixPlayerModule::storagePath();
        if (!is_dir($root)) {
            @mkdir($root, 0755, true);
        }
        $ht = $root . '/.htaccess';
        if (!is_file($ht)) {
            @file_put_contents($ht, "Options -Indexes\n<FilesMatch \"\\.(php|phtml|php5|php7|phar)$\">\nDeny from all\n</FilesMatch>\n");
        }
    }

    public static function stats()
    {
        $total = Capsule::table('mod_centralvix_players')->count();
        $active = Capsule::table('mod_centralvix_players')->where('active', 1)->count();
        $clients = Capsule::table('mod_centralvix_players')->distinct()->count('client_id');
        $views = (int) Capsule::table('mod_centralvix_players')->sum('views_count');
        return compact('total', 'active', 'clients', 'views');
    }

    public static function listAll($limit = 100)
    {
        return Capsule::table('mod_centralvix_players as p')
            ->leftJoin('tblclients as c', 'c.id', '=', 'p.client_id')
            ->select('p.*', Capsule::raw("CONCAT(c.firstname, ' ', c.lastname) as client_name"))
            ->orderBy('p.id', 'desc')
            ->limit($limit)
            ->get();
    }

    public static function listByClient($clientId)
    {
        return Capsule::table('mod_centralvix_players')
            ->where('client_id', (int) $clientId)
            ->orderBy('id', 'desc')
            ->get();
    }

    public static function get($id, $clientId = null)
    {
        $q = Capsule::table('mod_centralvix_players')->where('id', (int) $id);
        if ($clientId !== null) {
            $q->where('client_id', (int) $clientId);
        }
        return $q->first();
    }

    public static function getByHash($hash)
    {
        return Capsule::table('mod_centralvix_players')
            ->where('public_hash', $hash)
            ->where('active', 1)
            ->first();
    }

    public static function getSocials($playerId)
    {
        $rows = Capsule::table('mod_centralvix_player_socials')
            ->where('player_id', (int) $playerId)
            ->get();
        $out = [];
        foreach (self::SOCIAL_NETWORKS as $n) {
            $out[$n] = '';
        }
        foreach ($rows as $r) {
            $out[$r->network] = $r->url;
        }
        return $out;
    }

    public static function saveSocials($playerId, array $socials)
    {
        Capsule::table('mod_centralvix_player_socials')->where('player_id', (int) $playerId)->delete();
        foreach (self::SOCIAL_NETWORKS as $network) {
            $url = trim($socials[$network] ?? '');
            if ($url === '') {
                continue;
            }
            if (!CentralVixPlayerSecurity::validateSocialUrl($url)) {
                continue;
            }
            Capsule::table('mod_centralvix_player_socials')->insert([
                'player_id' => (int) $playerId,
                'network' => $network,
                'url' => $url,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    public static function playerToArray($player, $includeSocials = true)
    {
        if (!$player) {
            return null;
        }
        $data = [
            'id' => (int) $player->id,
            'client_id' => (int) $player->client_id,
            'public_hash' => $player->public_hash,
            'radio_name' => $player->radio_name,
            'stream_url' => $player->stream_url,
            'stream_type' => $player->stream_type,
            'metadata_adapter' => $player->metadata_adapter,
            'logo_path' => $player->logo_path,
            'bg_image_path' => $player->bg_image_path,
            'primary_color' => $player->primary_color,
            'secondary_color' => $player->secondary_color,
            'custom_text' => $player->custom_text,
            'theme_mode' => $player->theme_mode,
            'show_play' => (bool) $player->show_play,
            'show_volume' => (bool) $player->show_volume,
            'show_track' => (bool) $player->show_track,
            'show_artist' => (bool) $player->show_artist,
            'show_cover' => (bool) $player->show_cover,
            'show_listeners' => (bool) $player->show_listeners,
            'show_socials' => (bool) $player->show_socials,
            'active' => (bool) $player->active,
            'theme_id' => (int) $player->theme_id,
            'public_url' => CentralVixPlayerEmbedService::publicUrl($player),
            'iframe' => CentralVixPlayerEmbedService::iframeCode($player),
        ];
        if ($includeSocials) {
            $data['socials'] = self::getSocials($player->id);
        }
        return $data;
    }

    public static function logoUrl($path)
    {
        if (!$path) {
            return '';
        }
        return CentralVixPlayerModule::systemUrl() . '/modules/addons/centralvix_player_generator/storage/serve.php?f=' . urlencode(basename($path));
    }

    public static function saveFromRequest($clientId, array $data, $playerId = 0)
    {
        $radioName = CentralVixPlayerSecurity::sanitizeText($data['radio_name'] ?? '', 150);
        $streamUrl = trim($data['stream_url'] ?? '');
        if ($radioName === '') {
            return ['success' => false, 'message' => 'Informe o nome da rádio.'];
        }
        if (!CentralVixPlayerSecurity::validateStreamUrl($streamUrl)) {
            return ['success' => false, 'message' => 'URL de stream inválida.'];
        }

        $streamType = in_array($data['stream_type'] ?? 'audio', ['audio', 'video', 'hls'], true) ? $data['stream_type'] : 'audio';
        $adapter = in_array($data['metadata_adapter'] ?? 'auto', ['auto', 'icecast', 'shoutcast', 'azuracast'], true)
            ? $data['metadata_adapter'] : 'auto';

        $row = [
            'radio_name' => $radioName,
            'stream_url' => $streamUrl,
            'stream_type' => $streamType,
            'metadata_adapter' => $adapter,
            'primary_color' => self::sanitizeColor($data['primary_color'] ?? '#1e4fd8'),
            'secondary_color' => self::sanitizeColor($data['secondary_color'] ?? '#00bcd4'),
            'custom_text' => CentralVixPlayerSecurity::sanitizeText($data['custom_text'] ?? '', 300),
            'theme_mode' => ($data['theme_mode'] ?? 'dark') === 'light' ? 'light' : 'dark',
            'show_play' => !empty($data['show_play']) ? 1 : 0,
            'show_volume' => !empty($data['show_volume']) ? 1 : 0,
            'show_track' => !empty($data['show_track']) ? 1 : 0,
            'show_artist' => !empty($data['show_artist']) ? 1 : 0,
            'show_cover' => !empty($data['show_cover']) ? 1 : 0,
            'show_listeners' => !empty($data['show_listeners']) ? 1 : 0,
            'show_socials' => !empty($data['show_socials']) ? 1 : 0,
            'active' => !empty($data['active']) ? 1 : 0,
            'theme_id' => (int) ($data['theme_id'] ?? 0),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($playerId > 0) {
            $existing = self::get($playerId, $clientId);
            if (!$existing) {
                return ['success' => false, 'message' => 'Player não encontrado.'];
            }
            Capsule::table('mod_centralvix_players')->where('id', $playerId)->update($row);
            $id = $playerId;
        } else {
            $row['client_id'] = (int) $clientId;
            $row['public_hash'] = CentralVixPlayerSecurity::generatePublicHash();
            $row['views_count'] = 0;
            $row['created_at'] = date('Y-m-d H:i:s');
            $id = Capsule::table('mod_centralvix_players')->insertGetId($row);
        }

        self::saveSocials($id, $data['socials'] ?? []);
        $player = self::get($id, $clientId);
        return ['success' => true, 'player' => self::playerToArray($player), 'id' => $id];
    }

    public static function sanitizeColor($color)
    {
        $color = trim($color);
        if (preg_match('/^#[0-9a-fA-F]{3,8}$/', $color)) {
            return $color;
        }
        return '#1e4fd8';
    }

    public static function duplicate($id, $clientId)
    {
        $player = self::get($id, $clientId);
        if (!$player) {
            return ['success' => false, 'message' => 'Player não encontrado.'];
        }
        $newId = Capsule::table('mod_centralvix_players')->insertGetId([
            'client_id' => $clientId,
            'public_hash' => CentralVixPlayerSecurity::generatePublicHash(),
            'radio_name' => $player->radio_name . ' (cópia)',
            'stream_url' => $player->stream_url,
            'stream_type' => $player->stream_type,
            'metadata_adapter' => $player->metadata_adapter,
            'logo_path' => $player->logo_path,
            'bg_image_path' => $player->bg_image_path,
            'primary_color' => $player->primary_color,
            'secondary_color' => $player->secondary_color,
            'custom_text' => $player->custom_text,
            'theme_mode' => $player->theme_mode,
            'show_play' => $player->show_play,
            'show_volume' => $player->show_volume,
            'show_track' => $player->show_track,
            'show_artist' => $player->show_artist,
            'show_cover' => $player->show_cover,
            'show_listeners' => $player->show_listeners,
            'show_socials' => $player->show_socials,
            'active' => 0,
            'theme_id' => $player->theme_id,
            'views_count' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $socials = self::getSocials($id);
        self::saveSocials($newId, $socials);
        return ['success' => true, 'id' => $newId];
    }

    public static function delete($id, $clientId)
    {
        $player = self::get($id, $clientId);
        if (!$player) {
            return ['success' => false, 'message' => 'Player não encontrado.'];
        }
        Capsule::table('mod_centralvix_player_socials')->where('player_id', $id)->delete();
        Capsule::table('mod_centralvix_players')->where('id', $id)->where('client_id', $clientId)->delete();
        if ($player->logo_path && is_file($player->logo_path)) {
            @unlink($player->logo_path);
        }
        if ($player->bg_image_path && is_file($player->bg_image_path)) {
            @unlink($player->bg_image_path);
        }
        return ['success' => true];
    }

    public static function toggleActive($id, $clientId, $active)
    {
        $player = self::get($id, $clientId);
        if (!$player) {
            return ['success' => false, 'message' => 'Player não encontrado.'];
        }
        Capsule::table('mod_centralvix_players')->where('id', $id)->update([
            'active' => $active ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        return ['success' => true];
    }

    public static function incrementViews($playerId)
    {
        if (CentralVixPlayerModule::dbSetting('track_views', '1') !== '1') {
            return;
        }
        Capsule::table('mod_centralvix_players')->where('id', (int) $playerId)->increment('views_count');
    }

    public static function handleUpload($clientId, $field, $existingPath = '')
    {
        if (empty($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
            return ['success' => true, 'path' => $existingPath];
        }
        $file = $_FILES[$field];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'Falha no upload.'];
        }
        $maxMb = (int) CentralVixPlayerModule::setting('max_image_mb', 2);
        if ($maxMb < 1) {
            $maxMb = 2;
        }
        if ($file['size'] > $maxMb * 1024 * 1024) {
            return ['success' => false, 'message' => 'Imagem excede o limite de ' . $maxMb . ' MB.'];
        }
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        if (!isset($allowed[$mime])) {
            return ['success' => false, 'message' => 'Formato de imagem não permitido.'];
        }
        self::ensureStorage();
        $dir = CentralVixPlayerModule::storagePath() . '/c' . (int) $clientId;
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $name = 'img_' . bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
        $dest = $dir . '/' . $name;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            return ['success' => false, 'message' => 'Não foi possível salvar a imagem.'];
        }
        if ($existingPath && is_file($existingPath) && strpos($existingPath, CentralVixPlayerModule::storagePath()) === 0) {
            @unlink($existingPath);
        }
        return ['success' => true, 'path' => $dest];
    }

    public static function normalizeConfig(array $data, $logoUrl = '', $bgUrl = '')
    {
        return [
            'radio_name' => CentralVixPlayerSecurity::sanitizeText($data['radio_name'] ?? 'Minha Rádio', 150),
            'stream_url' => trim($data['stream_url'] ?? ''),
            'stream_type' => in_array($data['stream_type'] ?? 'audio', ['audio', 'video', 'hls'], true) ? $data['stream_type'] : 'audio',
            'metadata_adapter' => in_array($data['metadata_adapter'] ?? 'auto', ['auto', 'icecast', 'shoutcast', 'azuracast'], true)
                ? $data['metadata_adapter'] : 'auto',
            'primary_color' => self::sanitizeColor($data['primary_color'] ?? '#1e4fd8'),
            'secondary_color' => self::sanitizeColor($data['secondary_color'] ?? '#00bcd4'),
            'custom_text' => CentralVixPlayerSecurity::sanitizeText($data['custom_text'] ?? '', 300),
            'theme_mode' => ($data['theme_mode'] ?? 'dark') === 'light' ? 'light' : 'dark',
            'show_play' => !empty($data['show_play']),
            'show_volume' => !empty($data['show_volume']),
            'show_track' => !empty($data['show_track']),
            'show_artist' => !empty($data['show_artist']),
            'show_cover' => !empty($data['show_cover']),
            'show_listeners' => !empty($data['show_listeners']),
            'show_socials' => !empty($data['show_socials']),
            'logo_url' => $logoUrl,
            'bg_url' => $bgUrl,
            'socials' => is_array($data['socials'] ?? null) ? $data['socials'] : [],
            'preview' => !empty($data['preview']),
        ];
    }

    public static function renderPreviewHtml(array $data, $previewMode = false)
    {
        $logoUrl = $data['logo_url'] ?? '';
        $bgUrl = $data['bg_url'] ?? '';
        $cfg = self::normalizeConfig($data, $logoUrl, $bgUrl);
        $isDark = $cfg['theme_mode'] === 'dark';
        $bgStyle = $cfg['bg_url'] ? 'background-image:url(' . CentralVixPlayerSecurity::e($cfg['bg_url']) . ');' : '';
        $socialIcons = [
            'instagram' => 'IG', 'facebook' => 'FB', 'youtube' => 'YT',
            'tiktok' => 'TK', 'whatsapp' => 'WA', 'website' => 'WWW',
        ];
        ob_start();
        ?>
<div class="cvix-player cvix-player--<?php echo $isDark ? 'dark' : 'light'; ?>" style="--cvix-primary:<?php echo CentralVixPlayerSecurity::e($cfg['primary_color']); ?>;--cvix-secondary:<?php echo CentralVixPlayerSecurity::e($cfg['secondary_color']); ?>;<?php echo $bgStyle; ?>" data-stream="<?php echo CentralVixPlayerSecurity::e($cfg['stream_url']); ?>" data-type="<?php echo CentralVixPlayerSecurity::e($cfg['stream_type']); ?>" data-adapter="<?php echo CentralVixPlayerSecurity::e($cfg['metadata_adapter']); ?>" data-preview="<?php echo $previewMode ? '1' : '0'; ?>">
<div class="cvix-player__overlay"></div>
<div class="cvix-player__inner">
<?php if ($cfg['logo_url']): ?><img class="cvix-player__logo" src="<?php echo CentralVixPlayerSecurity::e($cfg['logo_url']); ?>" alt=""><?php endif; ?>
<h2 class="cvix-player__name"><?php echo CentralVixPlayerSecurity::e($cfg['radio_name']); ?></h2>
<?php if ($cfg['custom_text']): ?><p class="cvix-player__text"><?php echo CentralVixPlayerSecurity::e($cfg['custom_text']); ?></p><?php endif; ?>
<?php if ($cfg['show_cover']): ?><div class="cvix-player__cover"><img src="" alt="" class="cvix-player__cover-img" hidden></div><?php endif; ?>
<div class="cvix-player__meta">
<?php if ($cfg['show_track']): ?><div class="cvix-player__track">—</div><?php endif; ?>
<?php if ($cfg['show_artist']): ?><div class="cvix-player__artist">—</div><?php endif; ?>
<?php if ($cfg['show_listeners']): ?><div class="cvix-player__listeners"><span class="cvix-player__listeners-n">0</span> ouvintes</div><?php endif; ?>
</div>
<div class="cvix-player__controls">
<?php if ($cfg['show_play']): ?><button type="button" class="cvix-player__play" aria-label="Play/Pause"><span class="cvix-player__play-icon">▶</span></button><?php endif; ?>
<?php if ($cfg['show_volume']): ?><input type="range" class="cvix-player__volume" min="0" max="1" step="0.01" value="0.8" aria-label="Volume"><?php endif; ?>
</div>
<audio class="cvix-player__audio" preload="none" playsinline></audio>
<video class="cvix-player__video" preload="none" playsinline hidden></video>
<?php if ($cfg['show_socials']): ?>
<div class="cvix-player__socials">
<?php foreach ($socialIcons as $net => $icon):
    $url = trim($cfg['socials'][$net] ?? '');
    if ($url === '' || !CentralVixPlayerSecurity::validateSocialUrl($url)) { continue; }
?>
<a href="<?php echo CentralVixPlayerSecurity::e($url); ?>" target="_blank" rel="noopener noreferrer" class="cvix-player__social cvix-player__social--<?php echo $net; ?>" title="<?php echo CentralVixPlayerSecurity::e($net); ?>"><?php echo $icon; ?></a>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div>
</div>
        <?php
        return ob_get_clean();
    }
}
