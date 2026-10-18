<?php

if (!defined('WHMCS')) {
    die('This hook should not be run directly');
}

add_hook('AdminAreaPrimaryNavbar', 1, function ($primaryNavbar) {
    if (!is_object($primaryNavbar)) {
        return;
    }
    try {
        if (!\WHMCS\Database\Capsule::table('tbladdonmodules')->where('module', 'cvxcatalog')->exists()) {
            return;
        }
    } catch (\Throwable $e) {
        return;
    }
    $menu = $primaryNavbar->addChild('centralvix_catalog', [
        'label' => 'CentralVix',
        'uri' => 'addonmodules.php?module=cvxcatalog',
        'order' => 20,
    ]);
    if ($menu) {
        $menu->addChild('catalog_generator', [
            'label' => 'Gerador de Catálogo',
            'uri' => 'addonmodules.php?module=cvxcatalog',
        ]);
        $menu->addChild('catalog_bulk', [
            'label' => 'Criar em Massa',
            'uri' => 'addonmodules.php?module=cvxcatalog&cvx=wizard',
        ]);
    }
});
