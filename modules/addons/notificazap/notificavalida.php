<?php
/*
 * Validação de licença CentralVix (compartilhada via painel/sdk/CvxModuleLicense.php)
 */
function NOTIFICAZAP_load_cvx_sdk()
{
    if (class_exists('CvxModuleLicense', false)) {
        return true;
    }
    $candidates = [
        dirname(__DIR__, 3) . '/painel/sdk/CvxModuleLicense.php',
        dirname(__DIR__, 2) . '/painel/sdk/CvxModuleLicense.php',
        __DIR__ . '/../../../painel/sdk/CvxModuleLicense.php',
    ];
    if (defined('ROOTDIR')) {
        array_unshift($candidates, ROOTDIR . '/painel/sdk/CvxModuleLicense.php');
    }
    foreach ($candidates as $sdk) {
        if (is_file($sdk)) {
            require_once $sdk;
            break;
        }
    }
    return class_exists('CvxModuleLicense', false);
}

function NOTIFICAZAP_check_license($licensekey, $force = true)
{
    $licensekey = trim((string) $licensekey);
    $checkout = 'https://centralvix.com.br/painel/checkout.php' . ($licensekey !== '' ? '?key=' . rawurlencode($licensekey) : '');
    $base = __DIR__ . '/license.txt';
    if ($licensekey === '' && is_file($base)) {
        $lines = @file($base, FILE_IGNORE_NEW_LINES);
        if (is_array($lines) && isset($lines[0])) {
            $licensekey = trim((string) $lines[0]);
        }
    }

    if (NOTIFICAZAP_load_cvx_sdk()) {
        $result = CvxModuleLicense::validate($licensekey, [
            'module_dir' => __DIR__,
            'force' => (bool) $force,
            'cache_file' => __DIR__ . '/cvx_license_cache.dat',
            'cache_secret' => 'NOTIFICA@ZAP#MPAY',
            'cache_days' => 5,
        ]);
        if (($result['status'] ?? '') === 'Active' && $licensekey !== '') {
            @file_put_contents($base, $licensekey . "\n", LOCK_EX);
        }
        $result['checkouturl'] = $result['checkouturl'] ?? $checkout;
        return $result;
    }

    return [
        'status' => 'Invalid',
        'description' => 'SDK CentralVix ausente (painel/sdk/CvxModuleLicense.php).',
        'message' => 'SDK CentralVix ausente (painel/sdk/CvxModuleLicense.php).',
        'erro' => 'SDK CentralVix ausente (painel/sdk/CvxModuleLicense.php).',
        'checkouturl' => $checkout,
        'source' => 'error',
        'valid' => false,
    ];
}

function NOTIFICAZAP_status_da_licenca($status, $licensekey = '', $checkout = '')
{
    $payload = [
        'status' => is_array($status) ? ($status['status'] ?? '') : $status,
        'erro' => is_array($status) ? ($status['erro'] ?? ($status['description'] ?? '')) : '',
        'description' => is_array($status) ? ($status['description'] ?? '') : '',
        'checkouturl' => $checkout ?: (is_array($status) ? ($status['checkouturl'] ?? '') : ''),
        'productname' => is_array($status) ? ($status['productname'] ?? '') : '',
        'source' => is_array($status) ? ($status['source'] ?? '') : '',
    ];
    if ($payload['checkouturl'] === '') {
        $payload['checkouturl'] = 'https://centralvix.com.br/painel/checkout.php' . ($licensekey !== '' ? '?key=' . rawurlencode($licensekey) : '');
    }

    $html = '';
    if (NOTIFICAZAP_load_cvx_sdk()) {
        $html = CvxModuleLicense::statusHtml($payload, (string) $licensekey);
        $painel = CvxModuleLicense::probeApi();
        $html .= CvxModuleLicense::healthHtml($payload, $painel, null);
    } else {
        $st = trim((string) $payload['status']);
        if ($st === 'Active') {
            $html = '<div class="alert alert-success"><h4 class="alert-heading">Licença ativa</h4></div>';
        } else {
            $html = '<div class="alert alert-warning"><h4 class="alert-heading">Licença: ' . htmlspecialchars($st ?: 'desconhecido', ENT_QUOTES, 'UTF-8') . '</h4>'
                . '<p>' . htmlspecialchars((string) $payload['erro'], ENT_QUOTES, 'UTF-8') . '</p></div>';
        }
    }

    if (($payload['status'] ?? '') !== 'Active') {
        $title = ($payload['status'] ?? '') === 'Expired' ? 'Licença vencida' : 'Módulo suspenso';
        $html .= '<div id="cvx-license-overlay" style="position:fixed;inset:0;background:rgba(11,28,36,.62);z-index:99999;display:flex;align-items:center;justify-content:center;padding:18px">'
            . '<div style="width:min(460px,100%);background:#fff;border-radius:18px;padding:24px;box-shadow:0 24px 70px rgba(0,0,0,.28)">'
            . '<h2 style="margin:0 0 8px">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h2>'
            . '<p style="margin:0 0 18px;color:#5d6f7c">Contrate um plano para reativar o módulo. O Pix libera a licença automaticamente.</p>'
            . '<a href="' . htmlspecialchars((string) $payload['checkouturl'], ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener" style="display:block;text-align:center;background:#0f766e;color:#fff;text-decoration:none;font-weight:800;border-radius:12px;padding:12px 16px">Contratar agora</a>'
            . '</div></div>';
    }
    return $html;
}
function NOTIFICAZAP_verify_Table()
{
    if (!WHMCS\Database\Capsule::schema()->hasTable("mod_notificazap_logs")) {
        WHMCS\Database\Capsule::schema()->create("mod_notificazap_logs", function ($table) {
            $table->increments("id");
            $table->binary("message");
            $table->integer("clientid");
            $table->enum("type", ["sendmessage", "addcontact"])->default("sendmessage");
            $table->text("created_at");
            $table->text("updated_at");
        });
    }
    if (!WHMCS\Database\Capsule::schema()->hasTable("mod_notificazap_logs_falhas")) {
        WHMCS\Database\Capsule::schema()->create("mod_notificazap_logs_falhas", function ($table) {
            $table->increments("id");
            $table->integer("clientid");
            $table->integer("invoiceid");
            $table->binary("message");
            $table->text("created_at");
            $table->text("updated_at");
        });
    }
    if (!WHMCS\Database\Capsule::schema()->hasTable("mod_notificazap_cron")) {
        WHMCS\Database\Capsule::schema()->create("mod_notificazap_cron", function ($table) {
            $table->increments("id");
            $table->text("nome");
            $table->text("idclientes");
            $table->text("grupo");
            $table->text("produto");
            $table->enum("status", ["todos", "Active", "Inactive", "Closed"])->default("Active");
            $table->enum("cron", ["minutos", "diario"])->default("diario");
            $table->binary("mensagem");
            $table->enum("type", ["texto", "textoimagem"])->default("texto");
            $table->text("urlimagem");
            $table->text("data");
            $table->enum("enviado", ["yes", "no"])->default("no");
            $table->enum("hojeenviado", ["yes", "no"])->default("no");
        });
    }
    if (!WHMCS\Database\Capsule::schema()->hasTable("mod_notificazap_registry")) {
        WHMCS\Database\Capsule::schema()->create("mod_notificazap_registry", function ($table) {
            $table->increments("id");
            $table->enum("level", ["DEBUG", "INFO", "WARNING", "ERROR", "CRITICAL"])->default("INFO");
            $table->text("message");
            $table->text("context")->nullable();
            $table->integer("client_id")->nullable();
            $table->integer("user_id")->nullable();
            $table->string("ip_address", 45)->nullable();
            $table->text("user_agent")->nullable();
            $table->timestamp("created_at")->useCurrent();
            $table->timestamp("updated_at")->useCurrent()->useCurrentOnUpdate();
            $table->index("level", "idx_level");
            $table->index("client_id", "idx_client_id");
            $table->index("created_at", "idx_created_at");
            $table->index("user_id", "idx_user_id");
        });
    }
    if (!WHMCS\Database\Capsule::schema()->hasTable("mod_notificazap_templates")) {
        WHMCS\Database\Capsule::schema()->create("mod_notificazap_templates", function ($table) {
            $table->increments("id");
            $table->text("type");
            $table->binary("message");
            $table->enum("status", ["active", "deactive"])->default("active");
            $table->text("created_at");
            $table->text("updated_at");
        });
        WHMCS\Database\Capsule::connection()->transaction(function ($handler) {
            $handler->table("mod_notificazap_templates")->insert(["type" => "createdinvoice", "message" => "Olá %name%! Informamos que a fatura N° %invoiceid%, com vencimento em %duedate%, tem o valor total de R\$ %value%, já encontra-se disponível em sua área do cliente para pagamento. Evite transtornos e efetue o pagamento até a data de vencimento.", "status" => "active", "created_at" => date("Y-m-d H:i:s"), "updated_at" => date("Y-m-d H:i:s")]);
        });
        WHMCS\Database\Capsule::connection()->transaction(function ($handler) {
            $handler->table("mod_notificazap_templates")->insert(["type" => "invoicereminder", "message" => "Olá %name%! Informamos que sua fatura N° %invoiceid% está prestes vencer no dia %duedate%, com o valor total de R\$ %value%. Efetue o pagamento até a data de vencimento.", "status" => "active", "created_at" => date("Y-m-d H:i:s"), "updated_at" => date("Y-m-d H:i:s")]);
        });
        WHMCS\Database\Capsule::connection()->transaction(function ($handler) {
            $handler->table("mod_notificazap_templates")->insert(["type" => "invoicepaid", "message" => "Olá %name%! Informamos que o pagamento de sua fatura N° %invoiceid%, foi confirmado com sucesso, agradecemos a sua confiança.", "status" => "active", "created_at" => date("Y-m-d H:i:s"), "updated_at" => date("Y-m-d H:i:s")]);
        });
        WHMCS\Database\Capsule::connection()->transaction(function ($handler) {
            $handler->table("mod_notificazap_templates")->insert(["type" => "ticketcreated", "message" => "Olá %name%! Recebemos o seu ticket de N° %ticket% em %date% as %hour% em nosso sistema. Aguarde, em breve um de nossos atendentes irá lhe atender.", "status" => "active", "created_at" => date("Y-m-d H:i:s"), "updated_at" => date("Y-m-d H:i:s")]);
        });
        WHMCS\Database\Capsule::connection()->transaction(function ($handler) {
            $handler->table("mod_notificazap_templates")->insert(["type" => "ticketreplied", "message" => "Olá %name%! O seu ticket N° %ticket% foi respondido por nossa equipe neste exato momento (%date% - %hour%). Visite sua área de cliente para conferir maiores informações.", "status" => "active", "created_at" => date("Y-m-d H:i:s"), "updated_at" => date("Y-m-d H:i:s")]);
        });
        WHMCS\Database\Capsule::connection()->transaction(function ($handler) {
            $handler->table("mod_notificazap_templates")->insert(["type" => "servicecreated", "message" => "Olá %name%! O seu produto %product% foi ativado! Para sua segurança os dados de acesso foram encaminhados ao seu e-mail neste momento. Caso não encontre entre em contato conosco.", "status" => "active", "created_at" => date("Y-m-d H:i:s"), "updated_at" => date("Y-m-d H:i:s")]);
        });
        WHMCS\Database\Capsule::connection()->transaction(function ($handler) {
            $handler->table("mod_notificazap_templates")->insert(["type" => "servicesuspended", "message" => "Olá %name%! Informamos que o produto %product% foi suspenso, para mais informações acesse a área do cliente.", "status" => "active", "created_at" => date("Y-m-d H:i:s"), "updated_at" => date("Y-m-d H:i:s")]);
        });
        WHMCS\Database\Capsule::connection()->transaction(function ($handler) {
            $handler->table("mod_notificazap_templates")->insert(["type" => "servicealertsuspended", "message" => "Olá %name%! Informamos que o produto %product% será suspenso nas próximas horas. Para mais informações acesse a área do cliente.", "status" => "active", "created_at" => date("Y-m-d H:i:s"), "updated_at" => date("Y-m-d H:i:s")]);
        });
        WHMCS\Database\Capsule::connection()->transaction(function ($handler) {
            $handler->table("mod_notificazap_templates")->insert(["type" => "servicereactived", "message" => "Olá %name%! Informamos que o produto %product% foi reativado, para mais informações acesse a área do cliente.", "status" => "active", "created_at" => date("Y-m-d H:i:s"), "updated_at" => date("Y-m-d H:i:s")]);
        });
        WHMCS\Database\Capsule::connection()->transaction(function ($handler) {
            $handler->table("mod_notificazap_templates")->insert(["type" => "servicecancelled", "message" => "Olá %name%! Informamos que o produto %product% foi cancelado, para mais informações acesse a área do cliente.", "status" => "active", "created_at" => date("Y-m-d H:i:s"), "updated_at" => date("Y-m-d H:i:s")]);
        });
        WHMCS\Database\Capsule::connection()->transaction(function ($handler) {
            $handler->table("mod_notificazap_templates")->insert(["type" => "servicealertcancelled", "message" => "Olá %name%! Informamos que o produto %product% será em breve cancelado. Para mais informações acesse a área do cliente.", "status" => "active", "created_at" => date("Y-m-d H:i:s"), "updated_at" => date("Y-m-d H:i:s")]);
        });
        WHMCS\Database\Capsule::connection()->transaction(function ($handler) {
            $handler->table("mod_notificazap_templates")->insert(["type" => "welcome", "message" => "Olá %name%! Seja bem-vindo a %company%! Agradecemos a preferência em nossa plataforma, ficamos contentes com a sua chegada.", "status" => "active", "created_at" => date("Y-m-d H:i:s"), "updated_at" => date("Y-m-d H:i:s")]);
        });
        WHMCS\Database\Capsule::connection()->transaction(function ($handler) {
            $handler->table("mod_notificazap_templates")->insert(["type" => "clientlogin", "message" => "Olá %name%! Identificamos um acesso à sua conta originado do endereço de IP %ipaddr%. Caso desconheça este IP recomendamos alterar sua senha imediatamente", "status" => "active", "created_at" => date("Y-m-d H:i:s"), "updated_at" => date("Y-m-d H:i:s")]);
        });
        WHMCS\Database\Capsule::connection()->transaction(function ($handler) {
            $handler->table("mod_notificazap_templates")->insert(["type" => "failedaccess", "message" => "Olá %name%! Identificamos uma falha de login originada deste IP %ipaddr% em %date% as %hour% sua conta: %email%. Caso você desconheça este IP ou tenha certeza que não foi você recomendamos alterar sua senha imediatamente", "status" => "active", "created_at" => date("Y-m-d H:i:s"), "updated_at" => date("Y-m-d H:i:s")]);
        });
        WHMCS\Database\Capsule::connection()->transaction(function ($handler) {
            $handler->table("mod_notificazap_templates")->insert(["type" => "invoicecancelled", "message" => "Olá %name%! Informamos que a fatura N° %invoiceid%, com vencimento em %duedate% e o valor total de R\$ %value% foi cancelada em sua conta. Para mais detalhes entre em contato com o nosso suporte via ticket em sua área do cliente", "status" => "active", "created_at" => date("Y-m-d H:i:s"), "updated_at" => date("Y-m-d H:i:s")]);
        });
        WHMCS\Database\Capsule::connection()->transaction(function ($handler) {
            $handler->table("mod_notificazap_templates")->insert(["type" => "clientchangepwd", "message" => "Olá %name%! Informamos que a senha da sua conta (%email%) foi alterada neste momento e por isso achamos prudente te notificar. IP: %ipaddr%, Data da alteração: %date%, Hora: %hour%. Se você desconhece essa ação recomendamos recuperar sua senha neste momento e em seguida entre em contato com nosso suporte via ticket em sua área do cliente", "status" => "active", "created_at" => date("Y-m-d H:i:s"), "updated_at" => date("Y-m-d H:i:s")]);
        });
        WHMCS\Database\Capsule::connection()->transaction(function ($handler) {
            $handler->table("mod_notificazap_templates")->insert(["type" => "serviceready", "message" => "Olá %name%! Informamos o serviço %product% encontra-se pronto para uso a partir de agora. Veja alguns detalhes sobre: Domínio: %domain%, IP: %ip%, Valor: R\$ %value%, Vencimento: %duedate%. Outros detalhes como: nome de usuário e senha do serviço foram encaminhados ao seu e-mail por uma questão de segurança. Caso não tenha recebido entre em contato com nosso suporte via ticket em sua área do cliente", "status" => "active", "created_at" => date("Y-m-d H:i:s"), "updated_at" => date("Y-m-d H:i:s")]);
        });
        WHMCS\Database\Capsule::connection()->transaction(function ($handler) {
            $handler->table("mod_notificazap_templates")->insert(["type" => "servicealerta", "message" => "Olá %name%! Informamos que a fatura #%invoiceid% venceu em %duedate%, com o valor total de R\$ %value%. Efetue o pagamento o mais breve possível. Evite a interrupção do serviço. Caso já tenha efetuado o pagamento, envie-nos o comprovante para comprovação e quitação.", "status" => "active", "created_at" => date("Y-m-d H:i:s"), "updated_at" => date("Y-m-d H:i:s")]);
        });
        WHMCS\Database\Capsule::connection()->transaction(function ($handler) {
            $handler->table("mod_notificazap_templates")->insert(["type" => "faturavencendohoje", "message" => "Olá %name%! Informamos que a fatura #%invoiceid% está vencendo no dia de hoje em %duedate%, com o valor total de R\$ %value%. Efetue o pagamento o mais breve possível. Evite a interrupção do serviço. Caso já tenha efetuado o pagamento, envie-nos o comprovante para comprovação e quitação.", "status" => "active", "created_at" => date("Y-m-d H:i:s"), "updated_at" => date("Y-m-d H:i:s")]);
        });
        WHMCS\Database\Capsule::connection()->transaction(function ($handler) {
            $handler->table("mod_notificazap_templates")->insert(["type" => "alteracaosenha", "message" => "Olá %name%! Sua senha acabou de ser alterada! Caso não tenha sido você que tenha alterado a senha, pedimos que entre em contato conosco o mais rápido possível! Nova senha: %NovaSenha%.", "status" => "active", "created_at" => date("Y-m-d H:i:s"), "updated_at" => date("Y-m-d H:i:s")]);
        });
        WHMCS\Database\Capsule::connection()->transaction(function ($handler) {
            $handler->table("mod_notificazap_templates")->insert(["type" => "solicitacancelamento", "message" => "Olá %name%, como vai? Recebemos um pedido de cancelamento do serviço de ID #%id%, gostaríamos de entender melhor o motivo desta solicitação. Segue detalhamento da solicitação: ID Serviço: *%id%* Tipo: *%tipo%* Razão: *%razao%*.", "status" => "active", "created_at" => date("Y-m-d H:i:s"), "updated_at" => date("Y-m-d H:i:s")]);
        });
        WHMCS\Database\Capsule::connection()->transaction(function ($handler) {
            $handler->table("mod_notificazap_templates")->insert(["type" => "enviarcotacao", "message" => "Olá %name%, como vai? Conforme solicitado, fazemos uso do presente para encaminhar o link do orçamento dos produtos (ou serviços) requisitados. Orçamento de número: %idcotacao%. Link do Orçamento: %dominiowhmcs%/viewquote.php?id=%idcotacao% Nos colocamos à disposição para qualquer esclarecimento que se fizer necessário e esperamos poder concretizar o pedido brevemente.", "status" => "active", "created_at" => date("Y-m-d H:i:s"), "updated_at" => date("Y-m-d H:i:s")]);
        });
        WHMCS\Database\Capsule::connection()->transaction(function ($handler) {
            $handler->table("mod_notificazap_templates")->insert(["type" => "alterarstatuscotacao", "message" => "Olá %name%, como vai? A cotação de número %idcotacao% teve seu status alterado para %statuscotacao%, use o link %dominiowhmcs%/viewquote.php?id=%idcotacao% para visualizar esta cotação.", "status" => "active", "created_at" => date("Y-m-d H:i:s"), "updated_at" => date("Y-m-d H:i:s")]);
        });
        WHMCS\Database\Capsule::connection()->transaction(function ($handler) {
            $handler->table("mod_notificazap_templates")->insert(["type" => "cotacaoaceita", "message" => "Olá %name%, como vai? Notamos que você acabou de aceitar a cotação de número *#%idcotacao%*! Nossa equipe comercial já esta providenciando e organizando seu pedido, em breve você recebera um contato de nossa equipe. Gratos pela confiabilidade e solicitação de nossos produtos.", "status" => "active", "created_at" => date("Y-m-d H:i:s"), "updated_at" => date("Y-m-d H:i:s")]);
        });
        WHMCS\Database\Capsule::connection()->transaction(function ($handler) {
            $handler->table("mod_notificazap_templates")->insert(["type" => "ticketclose", "message" => "Olá %name%, tudo bem? O chamado abaixo acabou de ser marcado como resolvido! ID do Chamado: #%ticket% Titulo do Chamado: %tituloticket% Para visualizar este chamado acesse %dominiowhmcs%/viewticket.php?tid=%ticket%&c=%keychamado%.", "status" => "active", "created_at" => date("Y-m-d H:i:s"), "updated_at" => date("Y-m-d H:i:s")]);
        });
        WHMCS\Database\Capsule::connection()->transaction(function ($handler) {
            $handler->table("mod_notificazap_templates")->insert(["type" => "happybirthday", "message" => "Olá %name%, é neste dia especial que lhe desejamos um Feliz Aniversário para seus %idade% anos, que seus sonhos se tornem realidade.", "status" => "active", "created_at" => date("Y-m-d H:i:s"), "updated_at" => date("Y-m-d H:i:s")]);
        });
        WHMCS\Database\Capsule::connection()->transaction(function ($handler) {
            $handler->table("mod_notificazap_templates")->insert(["type" => "notafiscalasaas", "message" => "Olá %name%, tudo bem? Estamos enviando a Nota Fiscal de número %numeronf%, em %nfdataemissao%.", "status" => "active", "created_at" => date("Y-m-d H:i:s"), "updated_at" => date("Y-m-d H:i:s")]);
        });
    }
    if (WHMCS\Database\Capsule::schema()->hasTable("mod_notificazap_templates")) {
        $CAMPOEXISTE_NOTAFISCAL = WHMCS\Database\Capsule::table("mod_notificazap_templates")->WHERE("type", "=", "notafiscalasaas")->count();
        if ($CAMPOEXISTE_NOTAFISCAL == 0) {
            WHMCS\Database\Capsule::connection()->transaction(function ($handler) {
                $handler->table("mod_notificazap_templates")->insert(["type" => "notafiscalasaas", "message" => "Olá %name%, tudo bem? Estamos enviando a Nota Fiscal de número %numeronf%, em %nfdataemissao%.", "status" => "active", "created_at" => date("Y-m-d H:i:s"), "updated_at" => date("Y-m-d H:i:s")]);
            });
        }
        $CAMPOEXISTE_ALERTASUSPENSAO = WHMCS\Database\Capsule::table("mod_notificazap_templates")->WHERE("type", "=", "servicealertsuspended")->count();
        if ($CAMPOEXISTE_ALERTASUSPENSAO == 0) {
            WHMCS\Database\Capsule::connection()->transaction(function ($handler) {
                $handler->table("mod_notificazap_templates")->insert(["type" => "servicealertsuspended", "message" => "Olá %name%! Informamos que o produto %product% será suspenso nas próximas horas. Para mais informações acesse a área do cliente.", "status" => "active", "created_at" => date("Y-m-d H:i:s"), "updated_at" => date("Y-m-d H:i:s")]);
            });
        }
        $CAMPOEXISTE_FATURAVENCEHOJE = WHMCS\Database\Capsule::table("mod_notificazap_templates")->WHERE("type", "=", "faturavencendohoje")->count();
        if ($CAMPOEXISTE_FATURAVENCEHOJE == 0) {
            WHMCS\Database\Capsule::connection()->transaction(function ($handler) {
                $handler->table("mod_notificazap_templates")->insert(["type" => "faturavencendohoje", "message" => "Olá %name%! Informamos que a fatura #%invoiceid% está vencendo no dia de hoje em %duedate%, com o valor total de R\$ %value%. Efetue o pagamento o mais breve possível. Evite a interrupção do serviço. Caso já tenha efetuado o pagamento, envie-nos o comprovante para comprovação e quitação.", "status" => "active", "created_at" => date("Y-m-d H:i:s"), "updated_at" => date("Y-m-d H:i:s")]);
            });
        }
        $CAMPOEXISTE_ALERTASERVICOCANCELADO = WHMCS\Database\Capsule::table("mod_notificazap_templates")->WHERE("type", "=", "servicealertcancelled")->count();
        if ($CAMPOEXISTE_ALERTASERVICOCANCELADO == 0) {
            WHMCS\Database\Capsule::connection()->transaction(function ($handler) {
                $handler->table("mod_notificazap_templates")->insert(["type" => "servicealertcancelled", "message" => "Olá %name%! Informamos que o produto %product% será em breve cancelado. Para mais informações acesse a área do cliente.", "status" => "active", "created_at" => date("Y-m-d H:i:s"), "updated_at" => date("Y-m-d H:i:s")]);
            });
        }
    }
    if (!WHMCS\Database\Capsule::schema()->hasTable("mod_efizap")) {
        WHMCS\Database\Capsule::schema()->create("mod_efizap", function ($table) {
            $table->increments("id");
            $table->integer("iduser");
            $table->integer("idinvoice");
            $table->text("total");
            $table->text("linhadigitavel");
            $table->text("qrcode");
            $table->text("qrcodeimagem");
            $table->text("boleto");
            $table->text("chargeid");
            $table->text("status");
            $table->text("payment");
            $table->text("data");
        });
    }
    if (WHMCS\Database\Capsule::schema()->hasTable("mod_nfasaasmpay") && !WHMCS\Database\Capsule::schema()->hasColumn("mod_nfasaasmpay", "data_envionf")) {
        WHMCS\Database\Capsule::schema()->table("mod_nfasaasmpay", function ($table) {
            $table->date("data_envionf")->default("0000-00-00");
        });
    }
    if (WHMCS\Database\Capsule::schema()->hasTable("tblnotafacil") && !WHMCS\Database\Capsule::schema()->hasColumn("tblnotafacil", "data_envionf")) {
        WHMCS\Database\Capsule::schema()->table("tblnotafacil", function ($table) {
            $table->date("data_envionf")->default("0000-00-00");
        });
    }
    if (WHMCS\Database\Capsule::schema()->hasTable("mod_cobrancaasaasmpay") && !WHMCS\Database\Capsule::schema()->hasColumn("mod_cobrancaasaasmpay", "notenviado")) {
        WHMCS\Database\Capsule::schema()->table("mod_cobrancaasaasmpay", function ($table) {
            $table->enum("notenviado", ["N", "S"])->default("N");
        });
    }
    if (WHMCS\Database\Capsule::schema()->hasTable("mod_cobrancaasaasmpay") && !WHMCS\Database\Capsule::schema()->hasColumn("mod_cobrancaasaasmpay", "hojeenviado")) {
        WHMCS\Database\Capsule::schema()->table("mod_cobrancaasaasmpay", function ($table) {
            $table->enum("hojeenviado", ["N", "S"])->default("N");
        });
    }
    if (WHMCS\Database\Capsule::schema()->hasTable("mod_efimpay") && !WHMCS\Database\Capsule::schema()->hasColumn("mod_efimpay", "notenviado")) {
        WHMCS\Database\Capsule::schema()->table("mod_efimpay", function ($table) {
            $table->enum("notenviado", ["N", "S"])->default("N");
        });
    }
    if (WHMCS\Database\Capsule::schema()->hasTable("mod_efimpay") && !WHMCS\Database\Capsule::schema()->hasColumn("mod_efimpay", "hojeenviado")) {
        WHMCS\Database\Capsule::schema()->table("mod_efimpay", function ($table) {
            $table->enum("hojeenviado", ["N", "S"])->default("N");
        });
    }
    if (WHMCS\Database\Capsule::schema()->hasTable("mod_paghiper") && !WHMCS\Database\Capsule::schema()->hasColumn("mod_paghiper", "notenviado")) {
        WHMCS\Database\Capsule::schema()->table("mod_paghiper", function ($table) {
            $table->enum("notenviado", ["N", "S"])->default("N");
        });
    }
    if (WHMCS\Database\Capsule::schema()->hasTable("mod_paghiper") && !WHMCS\Database\Capsule::schema()->hasColumn("mod_paghiper", "hojeenviado")) {
        WHMCS\Database\Capsule::schema()->table("mod_paghiper", function ($table) {
            $table->enum("hojeenviado", ["N", "S"])->default("N");
        });
    }
    if (WHMCS\Database\Capsule::schema()->hasTable("mod_mpagopixmpay") && !WHMCS\Database\Capsule::schema()->hasColumn("mod_mpagopixmpay", "notenviado")) {
        WHMCS\Database\Capsule::schema()->table("mod_mpagopixmpay", function ($table) {
            $table->enum("notenviado", ["N", "S"])->default("N");
        });
    }
    if (WHMCS\Database\Capsule::schema()->hasTable("mod_mpagopixmpay") && !WHMCS\Database\Capsule::schema()->hasColumn("mod_mpagopixmpay", "hojeenviado")) {
        WHMCS\Database\Capsule::schema()->table("mod_mpagopixmpay", function ($table) {
            $table->enum("hojeenviado", ["N", "S"])->default("N");
        });
    }
    if (WHMCS\Database\Capsule::schema()->hasTable("mod_notificazap_cron") && !WHMCS\Database\Capsule::schema()->hasColumn("mod_notificazap_cron", "cron")) {
        WHMCS\Database\Capsule::schema()->table("mod_notificazap_cron", function ($table) {
            $table->enum("cron", ["minutos", "diario"])->default("diario");
        });
    }
    if (!WHMCS\Database\Capsule::schema()->hasTable("mod_notificazap_disparos")) {
        WHMCS\Database\Capsule::schema()->create("mod_notificazap_disparos", function ($table) {
            $table->increments("id");
            $table->string("nome_campanha");
            $table->text("arquivo");
            $table->enum("tipo", ["texto", "imagem"])->default("texto");
            $table->text("imagem")->nullable();
            $table->text("mensagem");
            $table->enum("status", ["pending", "sent", "processing"])->default("pending");
            $table->timestamp("created_at")->useCurrent();
            $table->timestamp("updated_at")->useCurrent()->useCurrentOnUpdate();
        });
    }
    if (!WHMCS\Database\Capsule::schema()->hasTable("mod_notificazap_sent_messages")) {
        WHMCS\Database\Capsule::schema()->create("mod_notificazap_sent_messages", function ($table) {
            $table->increments("id");
            $table->string("phone");
            $table->string("message_hash");
            $table->timestamp("sent_at")->useCurrent();
            $table->index(["phone", "message_hash"]);
        });
    }
    if (!WHMCS\Database\Capsule::schema()->hasTable("mod_notificazap_controle_envios")) {
        WHMCS\Database\Capsule::schema()->create("mod_notificazap_controle_envios", function ($table) {
            $table->increments("id");
            $table->timestamp("data")->useCurrent();
            $table->integer("total_enviado");
        });
    }
    if (WHMCS\Database\Capsule::schema()->hasTable("mod_notificazap_logs") && !WHMCS\Database\Capsule::schema()->hasColumn("mod_notificazap_logs", "tipo_mensagem")) {
        WHMCS\Database\Capsule::schema()->table("mod_notificazap_logs", function ($table) {
            $table->string("tipo_mensagem");
        });
    }
    if (WHMCS\Database\Capsule::schema()->hasTable("mod_notificazap_logs") && !WHMCS\Database\Capsule::schema()->hasColumn("mod_notificazap_logs", "invoiceid")) {
        WHMCS\Database\Capsule::schema()->table("mod_notificazap_logs", function ($table) {
            $table->integer("invoiceid");
        });
    }
}
function NOTIFICAZAP_logGatewayAction($action, $data = [])
{
    $logDirBase = __DIR__ . "/logs";
    $currentDate = date("Y-m-d");
    $logDir = $logDirBase . "/" . $currentDate;
    $logFile = $logDir . "/logs.log";
    if (!file_exists($logDirBase)) {
        mkdir($logDirBase, 493, true);
    }
    if (!file_exists($logDir)) {
        mkdir($logDir, 493, true);
    }
    $timestamp = date("Y-m-d H:i:s");
    $logMessage = "[" . $timestamp . "] Ação: " . $action;
    if (!empty($data)) {
        $logMessage .= " | Dados: " . json_encode($data, JSON_PRETTY_PRINT);
    }
    $logMessage .= PHP_EOL;
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

?>