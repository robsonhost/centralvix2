<?php
if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

class CentralVixAdminUi
{
    public const THEME_VERSION = '1.0.0';

    public static function cssUrl($version = null)
    {
        $v = $version ?: self::THEME_VERSION;

        return '../modules/addons/centralvix_shared/assets/css/admin-theme.css?v=' . rawurlencode((string) $v);
    }

    public static function e($str)
    {
        return htmlspecialchars((string) $str, ENT_QUOTES, 'UTF-8');
    }

    public static function renderCssLink($version = null)
    {
        echo '<link rel="stylesheet" href="' . self::e(self::cssUrl($version)) . '">';
    }

    /**
     * @param array $config title, subtitle, modulelink, tab, nav[], success, error, extra_html (optional)
     */
    public static function renderHeader(array $config)
    {
        $title = $config['title'] ?? 'CentralVix';
        $subtitle = $config['subtitle'] ?? '';
        $modulelink = $config['modulelink'] ?? '';
        $tab = $config['tab'] ?? 'dashboard';
        $nav = $config['nav'] ?? [];
        $success = $config['success'] ?? '';
        $error = $config['error'] ?? '';
        $extraHtml = $config['extra_html'] ?? '';

        include __DIR__ . '/../templates/admin_shell_header.php';
    }

    public static function renderFooter($moduleLabel, $version = null)
    {
        $footerLabel = $moduleLabel;
        $footerVersion = $version ?: self::THEME_VERSION;

        include __DIR__ . '/../templates/admin_shell_footer.php';
    }

    public static function isNavActive($tab, array $item)
    {
        $current = (string) $tab;
        if (!empty($item['active_when']) && is_array($item['active_when'])) {
            return in_array($current, $item['active_when'], true);
        }

        return $current === (string) ($item['tab'] ?? '');
    }
}
