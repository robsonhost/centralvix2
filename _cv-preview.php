<?php
// QA PREVIEW TEMPORARIO — apagar apos validacao. Nao usa init.php nem banco.
define('WHMCS', true);
define('ROOTDIR', dirname(__FILE__));

$cvix = include ROOTDIR . '/includes/centralvix-public-config.php';
if (!is_array($cvix)) {
    $cvix = [];
}

require_once ROOTDIR . '/includes/centralvix-whmcs-catalog.php';
require_once ROOTDIR . '/includes/centralvix-landing-view.php';

$page = strtolower(trim((string) ($_GET['p'] ?? 'home')));
$allowed = array_merge(['home', 'login', 'recursos', 'planos', 'beneficios', 'sobre', 'contato', 'cadastro', '2via', 'catalogo'], array_keys(centralvix_catalog_pages()));
if (!in_array($page, $allowed, true)) {
    $page = 'home';
}

$urls = [
    'home' => '/', 'recursos' => '/recursos', 'planos' => '/planos', 'beneficios' => '/beneficios',
    'sobre' => '/sobre', 'contato' => '/contato', 'cadastro' => '/cadastro', 'invoices' => '/2via',
    'login' => '/dologin.php', 'login_page' => '/login.php', 'reset' => '/pwreset.php', 'register' => '/register.php',
    'cart' => '/cart.php', 'checkout' => '/painel/checkout.php', 'trial' => '/painel/checkout.php',
    'kb' => '/knowledgebase.php', 'ticket' => '/submitticket.php', 'status' => '/serverstatus.php',
    'downloads' => '/downloads.php', 'clientarea' => '/clientarea.php', 'catalogo' => '/catalogo',
    'hospedagem' => '/hospedagem', 'revenda' => '/revenda', 'vps' => '/vps', 'vps-brasil' => '/vps-brasil',
    'vps-canada' => '/vps-canada', 'streaming' => '/streaming', 'sites' => '/sites',
];

$catalog = centralvix_catalog_load(isset(centralvix_catalog_pages()[$page]) ? $page : null);

if (isset($_GET['demo'])) {
    $demoProducts = [];
    $mk = function ($id, $name, $m, $a, $feat, $extra = []) use (&$demoProducts) {
        $demoProducts[] = array_merge([
            'id' => $id, 'name' => $name, 'description' => '', 'type' => 'hostingaccount',
            'paytype' => 'recurring', 'group_id' => 1, 'group_name' => 'Hospedagem de Sites',
            'group_slug' => 'hospedagem', 'group_segment' => 'hospedagem', 'segment' => 'hospedagem',
            'monthly' => $m, 'annually' => $a, 'slogan' => '', 'badge' => '',
            'features' => $feat, 'location' => '', 'promotion_label' => '', 'compare_monthly' => null,
        ], $extra);
    };
    $f1 = [
        ['name' => 'Espaço em disco', 'value' => '10', 'unit' => 'GB'],
        ['name' => 'Transferência mensal', 'value' => 'Ilimitada', 'unit' => ''],
        ['name' => 'Contas de e-mail', 'value' => '50', 'unit' => ''],
        ['name' => 'Certificado SSL', 'value' => 'Grátis', 'unit' => ''],
        ['name' => 'cPanel incluso', 'value' => '', 'unit' => ''],
        ['name' => 'Backups diários', 'value' => '', 'unit' => ''],
    ];
    $mk(11, 'Hospedagem Start', 14.9, 149.0, array_slice($f1, 0, 4));
    $mk(12, 'Hospedagem Pro', 29.9, 299.0, $f1, ['badge' => 'MAIS VENDIDO', 'compare_monthly' => 39.9, 'promotion_label' => '-25%']);
    $mk(13, 'Hospedagem Business', 59.9, 599.0, array_merge($f1, [['name' => 'IP dedicado', 'value' => '', 'unit' => ''], ['name' => 'Suporte prioritário', 'value' => '24/7', 'unit' => '']]));
    $mk(21, 'VPS Brasil 1', 89.9, 899.0, [
        ['name' => 'vCPU', 'value' => '2', 'unit' => 'núcleos'],
        ['name' => 'Memória RAM', 'value' => '4', 'unit' => 'GB'],
        ['name' => 'SSD', 'value' => '60', 'unit' => 'GB'],
        ['name' => 'Latência nacional', 'value' => 'PING BAIXO', 'unit' => ''],
    ], ['group_id' => 2, 'group_name' => 'VPS Brasil', 'group_slug' => 'vps-brasil', 'group_segment' => 'vps-brasil', 'segment' => 'vps', 'location' => 'vps-brasil']);
    $catalog['products'] = $demoProducts;
    $catalog['groups'] = [
        ['id' => 1, 'name' => 'Hospedagem de Sites', 'slug' => 'hospedagem', 'icon' => '', 'headline' => '', 'tagline' => 'Performance para o seu site.', 'products' => array_slice($demoProducts, 0, 3)],
        ['id' => 2, 'name' => 'VPS Brasil', 'slug' => 'vps-brasil', 'icon' => '', 'headline' => '', 'tagline' => 'Servidores com ping baixo no Brasil.', 'products' => [array_slice($demoProducts, 3, 1)[0]]],
    ];
}

$html = centralvix_render_public_page($page, $cvix, [
    'urls' => $urls,
    'cvix' => $cvix,
    'token' => 'QA_TOKEN_STUB',
    'captchaHtml' => '',
    'year' => date('Y'),
    'loginError' => false,
    'customFields' => [],
    'products' => [],
    'catalog' => $catalog,
    'contactSent' => false,
    'contactError' => '',
]);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>QA CentralVix — <?php echo htmlspecialchars($page); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/fonts/css/fontawesome.min.css">
    <link rel="stylesheet" href="/assets/fonts/css/fontawesome-solid.min.css">
    <link rel="stylesheet" href="/assets/fonts/css/fontawesome-brands.min.css">
    <link rel="stylesheet" href="/assets/css/centralvix-landing.css?v=2.5.0">
</head>
<body class="cv-landing-page">
<?php echo $html; ?>
<script src="/assets/js/centralvix-landing.js?v=2.5.0"></script>
</body>
</html>
