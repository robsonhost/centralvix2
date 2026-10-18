<?php

define('CLIENTAREA', true);
require __DIR__ . DIRECTORY_SEPARATOR . 'init.php';

$loggedIn = false;
if (function_exists('isLoggedIn')) {
    $loggedIn = (bool) isLoggedIn();
} elseif (!empty($_SESSION['uid'])) {
    $loggedIn = true;
}

if ($loggedIn) {
    $dest = 'clientarea.php';
    if (!empty($_GET['goto']) && preg_match('#^clientarea\.php(?:\?.*)?$#', (string) $_GET['goto'])) {
        $dest = (string) $_GET['goto'];
    }
    header('Location: ' . $dest);
    exit;
}

$configFile = __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'centralvix-public-config.php';
$cvix = is_file($configFile) ? include $configFile : [];
if (!is_array($cvix)) {
    $cvix = [];
}

try {
    if (empty($cvix['email']) && class_exists('\WHMCS\Config\Setting')) {
        $email = (string) \WHMCS\Config\Setting::getValue('Email');
        if ($email !== '') {
            $cvix['email'] = $email;
        }
    }
} catch (\Throwable $e) {
}

require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'centralvix-whmcs-catalog.php';

$allowedPages = ['home', 'login', 'recursos', 'planos', 'beneficios', 'sobre', 'contato', 'cadastro', '2via', 'catalogo'];
$catalogPages = array_keys(centralvix_catalog_pages());
$allowedPages = array_values(array_unique(array_merge($allowedPages, $catalogPages)));
$page = strtolower(trim((string) ($_GET['p'] ?? 'home')));
if (!in_array($page, $allowedPages, true)) {
    $page = 'home';
}

$token = '';
if (function_exists('generate_token')) {
    $token = generate_token('plain');
}

$showCaptcha = false;
$captchaHtml = '';
try {
    if (class_exists('\WHMCS\Config\Setting')) {
        $captchaSetting = strtolower((string) \WHMCS\Config\Setting::getValue('CaptchaSetting'));
        $showCaptcha = !in_array($captchaSetting, ['', 'off', 'disabled', 'false', '0'], true);
        $recaptcha = trim((string) \WHMCS\Config\Setting::getValue('ReCAPTCHAPublicKey'));
        if ($recaptcha !== '') {
            $showCaptcha = false;
        }
    }
} catch (\Throwable $e) {
}

if ($showCaptcha) {
    $captchaHtml = '<div class="cv-landing-captcha">'
        . '<p>Digite o código da imagem</p>'
        . '<div class="cv-landing-captcha-row">'
        . '<img src="/includes/verifyimage.php" alt="Captcha" class="cv-landing-captcha-img" width="148" height="48">'
        . '<input type="text" name="code" maxlength="6" autocomplete="off" inputmode="text" aria-label="Código de verificação">'
        . '</div></div>';
}

$customFields = [];
$products = [];
try {
    if (class_exists('\WHMCS\Database\Capsule')) {
        $customFields = \WHMCS\Database\Capsule::table('tblcustomfields')
            ->where('type', 'client')
            ->where(function ($q) {
                $q->where('adminonly', '')->orWhereNull('adminonly')->orWhere('adminonly', '0');
            })
            ->orderBy('sortorder')
            ->get()
            ->all();

        $productRows = \WHMCS\Database\Capsule::table('tblproducts')
            ->where('hidden', 0)
            ->where('retired', 0)
            ->orderBy('order')
            ->limit(9)
            ->get();
        foreach ($productRows as $row) {
            $products[] = [
                'id' => (int) $row->id,
                'name' => (string) $row->name,
                'description' => trim(strip_tags((string) ($row->description ?? ''))),
            ];
        }
    }
} catch (\Throwable $e) {
}

$contactSent = false;
$contactError = '';
if ($page === 'contato' && $_SERVER['REQUEST_METHOD'] === 'POST' && (string) ($_POST['cvix_contact'] ?? '') === '1') {
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $subject = trim((string) ($_POST['subject'] ?? ''));
    $message = trim((string) ($_POST['message'] ?? ''));
    if ($name === '' || $email === '' || $subject === '' || $message === '') {
        $contactError = 'Preencha nome, e-mail, assunto e mensagem.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $contactError = 'Informe um e-mail válido.';
    } else {
        $to = trim((string) ($cvix['email'] ?? ''));
        if ($to === '') {
            $to = 'contato@centralvix.com.br';
        }
        $body = "Nome: {$name}\nE-mail: {$email}\nAssunto: {$subject}\n\n{$message}\n";
        $headers = 'From: ' . $to . "\r\n"
            . 'Reply-To: ' . $email . "\r\n"
            . 'Content-Type: text/plain; charset=UTF-8';
        $sent = @mail($to, '[CENTRALVIX] ' . $subject, $body, $headers);
        if ($sent) {
            $contactSent = true;
        } else {
            $contactError = 'Não foi possível enviar agora. Tente novamente ou use o e-mail de contato.';
        }
    }
}

$urls = [
    'home' => '/',
    'recursos' => '/recursos',
    'planos' => '/planos',
    'beneficios' => '/beneficios',
    'sobre' => '/sobre',
    'contato' => '/contato',
    'cadastro' => '/cadastro',
    'invoices' => '/2via',
    'login' => '/dologin.php',
    'login_page' => '/login.php',
    'reset' => '/pwreset.php',
    'register' => '/register.php',
    'cart' => '/cart.php',
    'checkout' => '/painel/checkout.php',
    'trial' => '/painel/checkout.php',
    'kb' => '/knowledgebase.php',
    'ticket' => '/submitticket.php',
    'status' => '/serverstatus.php',
    'downloads' => '/downloads.php',
    'clientarea' => '/clientarea.php',
    'catalogo' => '/catalogo',
    'hospedagem' => '/hospedagem',
    'revenda' => '/revenda',
    'vps' => '/vps',
    'vps-brasil' => '/vps-brasil',
    'vps-canada' => '/vps-canada',
    'streaming' => '/streaming',
    'sites' => '/sites',
];

$catalog = centralvix_catalog_load(isset(centralvix_catalog_pages()[$page]) ? $page : null);

$loginError = isset($_GET['incorrect']) && $_GET['incorrect'] !== 'false' && $_GET['incorrect'] !== '0';

$view = __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'centralvix-landing-view.php';
require_once $view;

$html = centralvix_render_public_page($page, $cvix, [
    'urls' => $urls,
    'cvix' => $cvix,
    'token' => $token,
    'captchaHtml' => $captchaHtml,
    'year' => date('Y'),
    'loginError' => $loginError,
    'customFields' => $customFields,
    'products' => $products,
    'catalog' => $catalog,
    'contactSent' => $contactSent,
    'contactError' => $contactError,
]);

$company = htmlspecialchars((string) ($cvix['brand'] ?? 'CENTRALVIX'), ENT_QUOTES, 'UTF-8');
$titles = [
    'home' => $company . ' — Área do Cliente',
    'login' => 'Entrar — ' . $company,
    'recursos' => 'Recursos — ' . $company,
    'planos' => 'Planos — ' . $company,
    'beneficios' => 'Benefícios — ' . $company,
    'sobre' => 'Sobre — ' . $company,
    'contato' => 'Contato — ' . $company,
    'cadastro' => 'Criar conta — ' . $company,
    '2via' => '2ª via de fatura — ' . $company,
    'catalogo' => 'Catálogo — ' . $company,
    'hospedagem' => 'Hospedagem de Sites — ' . $company,
    'revenda' => 'Revenda WHM/cPanel — ' . $company,
    'revenda-hospedagem' => 'Revenda WHM/cPanel — ' . $company,
    'vps' => 'VPS — ' . $company,
    'vps-brasil' => 'VPS Brasil — ' . $company,
    'vps-canada' => 'VPS Canadá — ' . $company,
    'streaming' => 'Streaming — ' . $company,
    'revenda-streaming' => 'Revenda de Streaming — ' . $company,
    'web-radio' => 'Web Rádio — ' . $company,
    'aplicativos' => 'Aplicativos — ' . $company,
    'sites' => 'Sites — ' . $company,
    'desenvolvimento' => 'Desenvolvimento — ' . $company,
];

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?php echo $titles[$page] ?? $company; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/fonts/css/fontawesome.min.css">
    <link rel="stylesheet" href="/assets/fonts/css/fontawesome-solid.min.css">
    <link rel="stylesheet" href="/assets/fonts/css/fontawesome-brands.min.css">
    <link rel="stylesheet" href="/assets/css/centralvix-landing.css?v=2.3.0">
</head>
<body class="cv-landing-page">
<?php echo $html; ?>
<script src="/assets/js/centralvix-landing.js?v=2.3.0"></script>
</body>
</html>
