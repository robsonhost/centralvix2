<?php
if (!defined('WHMCS')) { die('This file cannot be accessed directly'); }
$player = $playerId ? CentralVixPlayerService::get($playerId, $clientId) : null;
$socials = $player ? CentralVixPlayerService::getSocials($player->id) : [];
$themes = CentralVixPlayerThemeService::listThemes();
$defaults = [
    'radio_name' => '',
    'stream_url' => '',
    'stream_type' => 'audio',
    'metadata_adapter' => 'auto',
    'primary_color' => '#1e4fd8',
    'secondary_color' => '#00bcd4',
    'custom_text' => '',
    'theme_mode' => 'dark',
    'show_play' => 1,
    'show_volume' => 1,
    'show_track' => 1,
    'show_artist' => 1,
    'show_cover' => 1,
    'show_listeners' => 1,
    'show_socials' => 1,
    'active' => 1,
    'theme_id' => 0,
];
if ($player) {
    foreach ($defaults as $k => $v) {
        if (isset($player->$k)) {
            $defaults[$k] = $player->$k;
        }
    }
}
$logoUrl = $player && $player->logo_path ? CentralVixPlayerService::logoUrl($player->logo_path) : '';
$bgUrl = $player && $player->bg_image_path ? CentralVixPlayerService::logoUrl($player->bg_image_path) : '';
$ajaxPreview = CentralVixPlayerModule::ajaxUrl('preview', true);
$ajaxSave = CentralVixPlayerModule::ajaxUrl('save', true);
?>
<div class="cvix-pg-builder">
<div class="cvix-pg-client-header">
<h2><?php echo $player ? 'Editar player' : 'Novo player'; ?></h2>
<a class="cvix-pg-btn outline" href="<?php echo CentralVixPlayerSecurity::e($modulelink); ?>">← Voltar</a>
</div>
<form id="cvix-pg-builder-form" class="cvix-pg-builder-grid" enctype="multipart/form-data">
<input type="hidden" name="csrf_token" value="<?php echo CentralVixPlayerSecurity::e($csrf); ?>">
<input type="hidden" name="player_id" value="<?php echo $player ? (int)$player->id : 0; ?>">
<div class="cvix-pg-builder-form">
<section class="cvix-pg-section">
<h3>Informações</h3>
<label>Nome da rádio<input type="text" name="radio_name" value="<?php echo CentralVixPlayerSecurity::e($defaults['radio_name']); ?>" required></label>
<label>URL do stream<input type="url" name="stream_url" value="<?php echo CentralVixPlayerSecurity::e($defaults['stream_url']); ?>" placeholder="https://..." required></label>
<label>Tipo de stream
<select name="stream_type">
<option value="audio" <?php echo $defaults['stream_type']==='audio'?'selected':''; ?>>Áudio (MP3/AAC)</option>
<option value="hls" <?php echo $defaults['stream_type']==='hls'?'selected':''; ?>>HLS (vídeo)</option>
<option value="video" <?php echo $defaults['stream_type']==='video'?'selected':''; ?>>Vídeo direto</option>
</select>
</label>
<label>Metadados
<select name="metadata_adapter">
<option value="auto" <?php echo $defaults['metadata_adapter']==='auto'?'selected':''; ?>>Automático</option>
<option value="icecast" <?php echo $defaults['metadata_adapter']==='icecast'?'selected':''; ?>>Icecast</option>
<option value="shoutcast" <?php echo $defaults['metadata_adapter']==='shoutcast'?'selected':''; ?>>Shoutcast</option>
<option value="azuracast" <?php echo $defaults['metadata_adapter']==='azuracast'?'selected':''; ?>>AzuraCast</option>
</select>
</label>
<label>Texto personalizado<input type="text" name="custom_text" value="<?php echo CentralVixPlayerSecurity::e($defaults['custom_text']); ?>" maxlength="300"></label>
<label>Tema
<select name="theme_id">
<option value="0">Personalizado</option>
<?php foreach ($themes as $t): ?>
<option value="<?php echo (int)$t->id; ?>" data-primary="<?php echo CentralVixPlayerSecurity::e($t->primary_color); ?>" data-secondary="<?php echo CentralVixPlayerSecurity::e($t->secondary_color); ?>" data-mode="<?php echo CentralVixPlayerSecurity::e($t->theme_mode); ?>" <?php echo (int)$defaults['theme_id']===(int)$t->id?'selected':''; ?>><?php echo CentralVixPlayerSecurity::e($t->name); ?></option>
<?php endforeach; ?>
</select>
</label>
</section>
<section class="cvix-pg-section">
<h3>Visual</h3>
<label>Logo<input type="file" name="logo" accept="image/jpeg,image/png,image/webp,image/gif"><?php if ($logoUrl): ?><img src="<?php echo CentralVixPlayerSecurity::e($logoUrl); ?>" class="cvix-pg-thumb" alt=""><?php endif; ?></label>
<label>Imagem de fundo<input type="file" name="background" accept="image/jpeg,image/png,image/webp,image/gif"><?php if ($bgUrl): ?><img src="<?php echo CentralVixPlayerSecurity::e($bgUrl); ?>" class="cvix-pg-thumb" alt=""><?php endif; ?></label>
<label>Cor principal<input type="color" name="primary_color" value="<?php echo CentralVixPlayerSecurity::e($defaults['primary_color']); ?>"></label>
<label>Cor secundária<input type="color" name="secondary_color" value="<?php echo CentralVixPlayerSecurity::e($defaults['secondary_color']); ?>"></label>
<label>Modo
<select name="theme_mode">
<option value="dark" <?php echo $defaults['theme_mode']==='dark'?'selected':''; ?>>Escuro</option>
<option value="light" <?php echo $defaults['theme_mode']==='light'?'selected':''; ?>>Claro</option>
</select>
</label>
</section>
<section class="cvix-pg-section">
<h3>Elementos</h3>
<div class="cvix-pg-checks">
<label class="cvix-pg-check"><input type="checkbox" name="show_play" value="1" <?php echo $defaults['show_play']?'checked':''; ?>> Botão play</label>
<label class="cvix-pg-check"><input type="checkbox" name="show_volume" value="1" <?php echo $defaults['show_volume']?'checked':''; ?>> Volume</label>
<label class="cvix-pg-check"><input type="checkbox" name="show_track" value="1" <?php echo $defaults['show_track']?'checked':''; ?>> Nome música</label>
<label class="cvix-pg-check"><input type="checkbox" name="show_artist" value="1" <?php echo $defaults['show_artist']?'checked':''; ?>> Artista</label>
<label class="cvix-pg-check"><input type="checkbox" name="show_cover" value="1" <?php echo $defaults['show_cover']?'checked':''; ?>> Capa</label>
<label class="cvix-pg-check"><input type="checkbox" name="show_listeners" value="1" <?php echo $defaults['show_listeners']?'checked':''; ?>> Ouvintes</label>
<label class="cvix-pg-check"><input type="checkbox" name="show_socials" value="1" <?php echo $defaults['show_socials']?'checked':''; ?>> Redes sociais</label>
<label class="cvix-pg-check"><input type="checkbox" name="active" value="1" <?php echo $defaults['active']?'checked':''; ?>> Player ativo</label>
</div>
</section>
<section class="cvix-pg-section">
<h3>Redes sociais</h3>
<?php
$labels = ['instagram'=>'Instagram','facebook'=>'Facebook','youtube'=>'YouTube','tiktok'=>'TikTok','whatsapp'=>'WhatsApp','website'=>'Site'];
foreach ($labels as $key => $label):
?>
<label><?php echo CentralVixPlayerSecurity::e($label); ?><input type="url" name="socials[<?php echo $key; ?>]" value="<?php echo CentralVixPlayerSecurity::e($socials[$key] ?? ''); ?>" placeholder="https://..."></label>
<?php endforeach; ?>
</section>
<div class="cvix-pg-builder-actions">
<button type="button" id="cvix-pg-save" class="cvix-pg-btn">Salvar player</button>
<span id="cvix-pg-save-msg"></span>
</div>
</div>
<div class="cvix-pg-builder-preview-wrap">
<h3>Preview em tempo real</h3>
<div id="cvix-pg-preview" class="cvix-pg-preview-frame"></div>
</div>
</form>
</div>
<script>
window.CVIX_PG_BUILDER = {
    previewUrl: <?php echo json_encode($ajaxPreview); ?>,
    saveUrl: <?php echo json_encode($ajaxSave); ?>,
    logoUrl: <?php echo json_encode($logoUrl); ?>,
    bgUrl: <?php echo json_encode($bgUrl); ?>
};
</script>
