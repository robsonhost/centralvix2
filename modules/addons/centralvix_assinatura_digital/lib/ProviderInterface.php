<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

interface CentralVixSignProviderInterface
{
    public function getName();

    public function isAvailable();

    /**
     * Integração futura com provedor certificado (ex.: ICP-Brasil).
     * Retorna array com status e metadados do provedor ou null se indisponível.
     */
    public function signDocument($documentPath, array $signerData, array $evidence);
}

class CentralVixSignNullProvider implements CentralVixSignProviderInterface
{
    public function getName()
    {
        return 'Evidência técnica interna (sem certificado ICP)';
    }

    public function isAvailable()
    {
        return true;
    }

    public function signDocument($documentPath, array $signerData, array $evidence)
    {
        return [
            'provider' => 'internal',
            'available' => false,
            'message' => 'Assinatura baseada em evidências técnicas registradas pelo sistema.',
        ];
    }
}
