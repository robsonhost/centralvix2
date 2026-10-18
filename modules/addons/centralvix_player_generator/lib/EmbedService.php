<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

class CentralVixPlayerEmbedService
{
    public static function publicUrl($player)
    {
        return CentralVixPlayerModule::publicPlayerUrl($player->public_hash);
    }

    public static function iframeCode($player, $width = 360, $height = 420)
    {
        $url = self::publicUrl($player);
        $w = (int) $width;
        $h = (int) $height;
        return '<iframe src="' . CentralVixPlayerSecurity::e($url) . '" width="' . $w . '" height="' . $h . '" frameborder="0" allow="autoplay" style="max-width:100%;border:0;border-radius:12px;" title="' . CentralVixPlayerSecurity::e($player->radio_name) . '"></iframe>';
    }

    public static function htmlEmbed($player)
    {
        return self::iframeCode($player) . "\n" . '<p style="font-size:12px;color:#64748b;">Player by CentralVix</p>';
    }
}
