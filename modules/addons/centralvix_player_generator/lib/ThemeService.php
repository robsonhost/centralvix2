<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

class CentralVixPlayerThemeService
{
    public static function listThemes()
    {
        return Capsule::table('mod_centralvix_player_themes')->orderBy('name')->get();
    }

    public static function get($id)
    {
        return Capsule::table('mod_centralvix_player_themes')->where('id', (int) $id)->first();
    }

    public static function seedDefaults()
    {
        if (Capsule::table('mod_centralvix_player_themes')->count() > 0) {
            return;
        }
        $themes = [
            ['name' => 'CentralVix Dark', 'primary_color' => '#1e4fd8', 'secondary_color' => '#00bcd4', 'theme_mode' => 'dark', 'is_default' => 1],
            ['name' => 'CentralVix Light', 'primary_color' => '#1e4fd8', 'secondary_color' => '#0a1628', 'theme_mode' => 'light', 'is_default' => 0],
            ['name' => 'Neon Cyan', 'primary_color' => '#00bcd4', 'secondary_color' => '#0a1628', 'theme_mode' => 'dark', 'is_default' => 0],
        ];
        foreach ($themes as $t) {
            Capsule::table('mod_centralvix_player_themes')->insert(array_merge($t, [
                'created_at' => date('Y-m-d H:i:s'),
            ]));
        }
    }

    public static function applyToPlayer($player, $theme)
    {
        if (!$theme) {
            return $player;
        }
        if (empty($player->primary_color)) {
            $player->primary_color = $theme->primary_color;
        }
        if (empty($player->secondary_color)) {
            $player->secondary_color = $theme->secondary_color;
        }
        if (empty($player->theme_mode)) {
            $player->theme_mode = $theme->theme_mode;
        }
        return $player;
    }
}
