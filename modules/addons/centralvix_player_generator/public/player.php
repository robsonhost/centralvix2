<?php

define('CLIENTAREA', true);
define('FORCESSL', true);

$whmcsPath = dirname(__DIR__, 4);
if (!is_file($whmcsPath . '/init.php')) {
    $whmcsPath = dirname(__DIR__, 3);
}
require_once $whmcsPath . '/init.php';

require_once dirname(__DIR__) . '/lib/Security.php';
CentralVixPlayerModule::autoload();

$hash = preg_replace('/[^a-f0-9]/', '', $_GET['h'] ?? '');
if (strlen($hash) < 16) {
    http_response_code(404);
    echo 'Player não encontrado.';
    exit;
}

$player = CentralVixPlayerService::getByHash($hash);
if (!$player) {
    http_response_code(404);
    echo 'Player não encontrado ou inativo.';
    exit;
}

CentralVixPlayerService::incrementViews($player->id);

$socials = CentralVixPlayerService::getSocials($player->id);
$logoUrl = $player->logo_path ? CentralVixPlayerService::logoUrl($player->logo_path) : '';
$bgUrl = $player->bg_image_path ? CentralVixPlayerService::logoUrl($player->bg_image_path) : '';
$metaUrl = CentralVixPlayerModule::systemUrl() . '/modules/addons/centralvix_player_generator/public/metadata.php?stream_url=' . urlencode($player->stream_url) . '&adapter=' . urlencode($player->metadata_adapter);

$data = [
    'radio_name' => $player->radio_name,
    'stream_url' => $player->stream_url,
    'stream_type' => $player->stream_type,
    'metadata_adapter' => $player->metadata_adapter,
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
    'logo_url' => $logoUrl,
    'bg_url' => $bgUrl,
    'socials' => $socials,
];

?><!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo CentralVixPlayerSecurity::e($player->radio_name); ?></title>
<link rel="stylesheet" href="../assets/css/player.css?v=1.0.0">
<?php if ($player->stream_type === 'hls'): ?>
<script src="https://cdn.jsdelivr.net/npm/hls.js@1.5.7/dist/hls.min.js"></script>
<?php endif; ?>
</head>
<body class="cvix-player-page">
<?php echo CentralVixPlayerService::renderPreviewHtml($data, false); ?>
<script>
window.CVIX_PLAYER = {
    metaUrl: <?php echo json_encode($metaUrl); ?>,
    pollInterval: 15000
};
</script>
<script src="../assets/js/player.js?v=1.0.0"></script>
</body>
</html>
