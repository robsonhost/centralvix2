<?php

if (!defined('WHMCS')) {
    die('This hook should not be run directly');
}

function centralvix_public_config()
{
    static $config = null;
    if ($config !== null) {
        return $config;
    }
    $file = ROOTDIR . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'centralvix-public-config.php';
    $config = is_file($file) ? include $file : [];
    return is_array($config) ? $config : [];
}

function centralvix_public_url($name, $fallback)
{
    if (function_exists('routePath')) {
        try {
            return routePath($name);
        } catch (\Throwable $e) {
            return $fallback;
        }
    }
    return $fallback;
}

add_hook('ClientAreaPage', 1, function ($vars) {
    $cvix = centralvix_public_config();
    try {
        if (empty($cvix['email']) && class_exists('\WHMCS\Config\Setting')) {
            $email = (string) \WHMCS\Config\Setting::getValue('Email');
            if ($email !== '') {
                $cvix['email'] = $email;
            }
        }
    } catch (\Throwable $e) {
    }
    return ['cvix' => $cvix];
});

add_hook('ClientAreaHeadOutput', 1, function ($vars) {
    $file = (string) ($vars['templatefile'] ?? '');
    if (!in_array($file, ['login', 'homepage'], true)) {
        return '';
    }

    return '<link rel="preconnect" href="https://fonts.googleapis.com">'
        . '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>'
        . '<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">'
        . '<link rel="stylesheet" href="/assets/css/centralvix-landing.css?v=2.4.0">'
        . '<style id="cv-landing-adapter">body.cv-page-login,body.cv-landing-page{background:#06152E!important} .cv-login-page,.cv-auth-shell{display:none!important}</style>'
        . '<script src="/assets/js/centralvix-landing.js?v=2.4.0" defer></script>';
});

add_hook('ClientAreaHeaderOutput', 1, function ($vars) {
    $file = (string) ($vars['templatefile'] ?? '');
    if (!in_array($file, ['login', 'homepage'], true)) {
        return '';
    }
    if (!empty($vars['loggedin'])) {
        return '';
    }

    $view = ROOTDIR . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'centralvix-landing-view.php';
    if (!is_file($view)) {
        return '';
    }
    require_once $view;

    $web = rtrim((string) ($vars['WEB_ROOT'] ?? $vars['systemurl'] ?? ''), '/');
    if ($web === '') {
        $web = '';
    }

    $urls = [
        'home' => $web . '/index.php',
        'cart' => $web . '/cart.php',
        'contact' => $web . '/contact.php',
        'login' => centralvix_public_url('login-validate', $web . '/dologin.php'),
        'login_page' => $web . '/login.php',
        'reset' => centralvix_public_url('password-reset-begin', $web . '/pwreset.php'),
        'register' => centralvix_public_url('register', $web . '/register.php'),
        'invoices' => $web . '/clientarea.php?action=invoices',
        'kb' => $web . '/knowledgebase.php',
        'ticket' => $web . '/submitticket.php',
        'status' => $web . '/serverstatus.php',
        'downloads' => $web . '/downloads.php',
    ];

    $captchaHtml = '';
    $captchaBtnClass = '';
    $captcha = $vars['captcha'] ?? null;
    if (is_object($captcha) && method_exists($captcha, 'isEnabled') && $captcha->isEnabled()) {
        $captchaHtml = '<div class="cv-landing-field"><p style="color:#94a3b8;font-size:.82rem;margin:0 0 .5rem">Digite os caracteres da imagem</p>'
            . '<div class="cv-landing-input"><img src="' . htmlspecialchars($web . '/includes/verifyimage.php', ENT_QUOTES, 'UTF-8') . '" alt="Captcha" width="120" height="40" style="border-radius:8px">'
            . '<input type="text" name="code" maxlength="6" class="form-control" placeholder="Código" autocomplete="off" aria-label="Código de verificação" style="margin-top:.5rem;padding-left:.85rem"></div></div>';
        if (method_exists($captcha, 'getButtonClass')) {
            $captchaBtnClass = (string) $captcha->getButtonClass($vars['captchaForm'] ?? 'login');
        }
        if (is_object($captcha->recaptcha ?? null) && method_exists($captcha->recaptcha, 'isEnabled') && $captcha->recaptcha->isEnabled()) {
            $captchaHtml = '<div class="cv-landing-field recaptcha-container" id="google-recaptcha-domainchecker"></div>';
        }
    }

    $cvix = $vars['cvix'] ?? centralvix_public_config();
    $token = (string) ($vars['token'] ?? '');
    $year = (string) ($vars['date_year'] ?? date('Y'));

    return centralvix_render_public_landing($cvix, $urls, $token, $captchaHtml, $captchaBtnClass, $year);
});
