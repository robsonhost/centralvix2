<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

class CentralVixMigManualMigrationProvider implements CentralVixMigMigrationProviderInterface
{
    public function getName()
    {
        return 'Manual (equipe CentralVix)';
    }

    public function isAutomated()
    {
        return false;
    }

    public function onScheduled($request)
    {
        CentralVixMigLogger::log($request->id, 'provider_scheduled', 'Migração agendada — modo manual');
    }

    public function onMigrationStart($request)
    {
        CentralVixMigLogger::log($request->id, 'provider_start', 'Migração iniciada — execução manual pela equipe');
    }

    public function validateConfiguration()
    {
        return ['success' => true, 'message' => 'Provider manual sempre disponível.'];
    }
}
