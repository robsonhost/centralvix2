<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

CentralVixImportadorHelper::requireAdmin();

CentralVixImportadorHelper::jsonResponse([
    'success' => true,
    'message' => 'Endpoint AJAX operacional.',
    'php' => PHP_VERSION,
    'admin' => !empty($_SESSION['adminid']),
    'zip' => extension_loaded('zip'),
    'xml' => extension_loaded('xml') || extension_loaded('libxml'),
    'vendor_present' => CentralVixImportadorHelper::isSpreadsheetLibraryPresent(),
    'vendor_compatible' => CentralVixImportadorHelper::isVendorPlatformCompatible(),
    'spreadsheet' => CentralVixImportadorHelper::isSpreadsheetAvailable(),
    'storage_writable' => CentralVixImportadorHelper::isStorageWritable(),
    'ajax_endpoint' => 'addonmodules.php?module=centralvix_importador&cvix_ajax=upload',
]);
