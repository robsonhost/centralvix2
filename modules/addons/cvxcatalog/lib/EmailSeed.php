<?php

namespace Cvx\Catalog;

use WHMCS\Database\Capsule;

final class EmailSeed
{
    public static function ensure(): array
    {
        $created = [];
        $templates = [
            'CentralVix — Hospedagem Ativada' => 'Seu serviço de hospedagem $service_product_name está pronto',
            'CentralVix — Revenda Ativada' => 'Sua revenda $service_product_name está pronta',
            'CentralVix — VPS Ativado' => 'Seu VPS $service_product_name está ativo',
            'CentralVix — Streaming Ativado' => 'Seu streaming $service_product_name está no ar',
            'CentralVix — Web Rádio Ativada' => 'Sua web rádio $service_product_name foi ativada',
            'CentralVix — Aplicativo Contratado' => 'Recebemos a contratação do aplicativo $service_product_name',
            'CentralVix — Site Contratado' => 'Recebemos a contratação do site $service_product_name',
            'CentralVix — Sistema Contratado' => 'Recebemos a contratação do sistema $service_product_name',
        ];
        $body = "Olá \$client_name,\n\n"
            . "Seu serviço \$service_product_name foi registrado com sucesso.\n\n"
            . "Produto: \$service_product_name\n"
            . "Domínio: \$service_domain\n"
            . "Acesso: \$whmcs_link\n\n"
            . "Atenciosamente,\n\$company_name\n\$signature";

        foreach ($templates as $name => $subject) {
            $exists = Capsule::table('tblemailtemplates')->where('name', $name)->where('type', 'product')->count();
            if ($exists) {
                continue;
            }
            $row = [
                'type' => 'product',
                'name' => $name,
                'subject' => $subject,
                'message' => nl2br($body),
                'fromname' => '',
                'fromemail' => '',
                'disabled' => 0,
                'custom' => 1,
                'language' => '',
                'plaintext' => 0,
            ];
            try {
                Capsule::table('tblemailtemplates')->insert($row);
                $created[] = $name;
            } catch (\Throwable $e) {
            }
        }
        return $created;
    }

    public static function mapForSegment(string $segment): string
    {
        return match ($segment) {
            'hospedagem' => 'CentralVix — Hospedagem Ativada',
            'revenda' => 'CentralVix — Revenda Ativada',
            'vps-brasil', 'vps-internacional', 'dedicado' => 'CentralVix — VPS Ativado',
            'streaming', 'revenda-streaming' => 'CentralVix — Streaming Ativado',
            'web-radio' => 'CentralVix — Web Rádio Ativada',
            'app-radio', 'app-android', 'app-ios', 'dev-app' => 'CentralVix — Aplicativo Contratado',
            'site', 'landing', 'loja' => 'CentralVix — Site Contratado',
            'sistema', 'automacao' => 'CentralVix — Sistema Contratado',
            default => 'Other Product/Service Welcome Email',
        };
    }
}
