<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

interface CentralVixMigMigrationProviderInterface
{
    public function getName();

    public function isAutomated();

    public function onScheduled($request);

    public function onMigrationStart($request);

    public function validateConfiguration();
}
