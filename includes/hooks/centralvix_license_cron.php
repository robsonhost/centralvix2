<?php
/**
 * Roda o bloqueio diário de licenças junto com o cron do WHMCS.
 */
if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

add_hook('DailyCronJob', 1, static function (): void {
    $boot = ROOTDIR . '/painel/includes/bootstrap.php';
    if (!is_file($boot)) {
        return;
    }
    try {
        require $boot;
        if (class_exists(\Cvx\Panel\BillingService::class)) {
            \Cvx\Panel\BillingService::expireOverdue();
        }
    } catch (Throwable $e) {
        if (function_exists('logActivity')) {
            logActivity('CentralVix License cron: ' . $e->getMessage());
        }
    }
});
