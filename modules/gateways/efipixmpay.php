<?php
/*
 * @ https://EasyToYou.eu - IonCube v14 Decoder Online
 * @ PHP 7.4
 * @ Decoder version: 1.0.2
 * @ Release: 10/08/2022
 */

// Decoded file for php version 74.
date_default_timezone_set("America/Sao_Paulo");
if (!defined("WHMCS")) {
    exit("This file cannot be accessed directly");
}
if (!defined("ROOTDIR")) {
    define("ROOTDIR", dirname(__DIR__, 2));
}
include_once ROOTDIR . "/modules/gateways/efipixmpay/functions.php";
include_once ROOTDIR . "/modules/gateways/efipixmpay/config.php";
function efipixmpay_MetaData()
{
    return ["DisplayName" => "EFI PAY PIX - Modules Pay", "APIVersion" => efipixmpay_Set_Versao_Atual_Modulo(), "DisableLocalCredtCardInput" => false, "TokenisedStorage" => true];
}
function efipixmpay_config($params = NULL)
{
    try {
        if (!is_array($params)) {
            $params = [];
        }
        $pixefi = new efipixmpay(trim((string) ($params["efipixmpay_licensa"] ?? "")));
        $versao_atual_modulo = efipixmpay_Set_Versao_Atual_Modulo();
        $linkdownloaddomodulo = efipixmpay_Set_Link_Download_Modulo();
        $linktutorial = efipixmpay_Set_Link_Download_Tutorial();
        $logs = efipixmpay_Set_Logs_Modulo();
        $systemUrl = (string) ($params["systemurl"] ?? "");
        $versaoRemota = $versao_atual_modulo;
        try {
            $versao_do_modulo = $pixefi->pegaVersaoModulo(efipixmpay_Set_Versao_do_Modulo());
            if (is_object($versao_do_modulo) && isset($versao_do_modulo->versao)) {
                $versaoRemota = (string) $versao_do_modulo->versao;
            }
        } catch (Throwable $e) {
            $versaoRemota = $versao_atual_modulo;
        }
        if (version_compare($versao_atual_modulo, $versaoRemota, "<")) {
            $linkdonwload = "<td width='30%'>Versão Atual<h2 style='font-weight: bold; margin-top: 0px; font-size: 300%;'>" . htmlspecialchars($versaoRemota, ENT_QUOTES, "UTF-8") . "</h2></td><td width='40%'>Atualize a sua versão<h2 style='font-weight: bold; margin-top: 0px; font-size: 100%;'><a href='" . htmlspecialchars($linkdownloaddomodulo, ENT_QUOTES, "UTF-8") . "' target='_blank'>Download do Módulo</a></h2></td><br><a class='btn btn-success btn-xs' href='" . htmlspecialchars($logs, ENT_QUOTES, "UTF-8") . "' target='_blank'>Logs de Atualizações</a>";
        } else {
            $linkdonwload = "<h2 style='font-weight: bold; margin-top: 0px; font-size: 100%;'>Sua Versão está Atualizada.</h2>";
        }
        $camposPersonalizados_CPF = $pixefi->obterCamposPersonalizados();
        if (!is_array($camposPersonalizados_CPF) || empty($camposPersonalizados_CPF)) {
            $camposPersonalizados_CPF = ["0" => "Nenhum campo personalizado encontrado"];
        }
        $certsDir = ROOTDIR . "/modules/gateways/efipixmpay/certs";
        $arquivosLista = $pixefi->listar_arquivos_diretorio($certsDir);
        if (!is_array($arquivosLista)) {
            $arquivosLista = [];
        }
        $arquivosdocertificado = !empty($arquivosLista) ? implode(",", $arquivosLista) : "nenhum_certificado_enviado";
        $imagempadrao = $systemUrl . "modules/gateways/efipixmpay/images/gerar_pix.png";
        $imagemdobotao = $pixefi->exibe_botao($params["efipixmpay_imagem_botao"] ?? "", $imagempadrao);

        // Validação forçada no painel/API ao abrir/salvar (sem cache).
        $statuslicenca = $pixefi->checkLicense(true);
        if (!is_array($statuslicenca)) {
            $statuslicenca = ["status" => "Invalid", "erro" => "Falha ao validar licença.", "checkouturl" => "", "source" => "error"];
        }
        $statusLicencaValor = trim((string) ($statuslicenca["status"] ?? ""));
        $erroLicencaValor = (string) ($statuslicenca["erro"] ?? "");
        $checkoutLicenca = (string) ($statuslicenca["checkouturl"] ?? "");

        $painelProbe = $pixefi->probePainelApi();
        $efiProbe = $pixefi->probeEfiApi(
            trim((string) ($params["efipixmpay_clientIdProd"] ?? "")),
            trim((string) ($params["efipixmpay_clientSecretProd"] ?? "")),
            trim((string) ($params["efipixmpay_certificado"] ?? ""))
        );
        $healthHtml = $pixefi->getSystemHealthHtml($statuslicenca, $painelProbe, $efiProbe);
        $licenseHtml = $pixefi->getLicenseMessage($statusLicencaValor, $erroLicencaValor, $checkoutLicenca) . $healthHtml;

        if (version_compare(PHP_VERSION, "7.4") < 0) {
            $errorMsg = "A versão do PHP do servidor onde o WHMCS está hospedado não é compatível. Atualize o PHP para uma versão igual ou superior à versão 7.4";
            logTransaction("efipixmpay", $errorMsg, "Erro de Versão do PHP");
            $licenseHtml = "<div class=\"alert alert-danger\">" . htmlspecialchars($errorMsg, ENT_QUOTES, "UTF-8") . "</div>" . $licenseHtml;
        } else {
            try {
                $pixefi->verify_Table();
            } catch (Throwable $e) {
                logActivity("[EFI PIX MPAY] verify_Table: " . $e->getMessage());
            }
        }

        $configarray = [
            "FriendlyName" => ["Type" => "System", "Value" => "EFI PAY PIX - Modules Pay"],
            "efipixmpay_nota" => [
                "FriendlyName" => "Versão <span class='badge'>1</span>",
                "Description" => "<table><tbody><tr><td width='30%'>Versão Atual <h2 style='font-weight: bold; margin-top: 0px; font-size: 300%;'>" . htmlspecialchars($versao_atual_modulo, ENT_QUOTES, "UTF-8") . "</h2></td>" . $linkdonwload . "</tr></tbody></table>",
            ],
            "efipixmpay_licensa" => [
                "FriendlyName" => "<i data-toggle='tooltip' data-placement='top' title='Obrigatório' class='test_toggle fas'></i> Chave de Licença - License Key <span class='badge'>2</span>",
                "Type" => "text",
                "Size" => "80",
                "Description" => "<i style='color:#ff1c29;'>* (Obrigatório)</i><br><br>Coloque a chave de Licença do Módulo (gere em <a href='https://centralvix.com.br/painel' target='_blank' rel='noopener'>centralvix.com.br/painel</a>)" . $licenseHtml,
            ],
            "efipixmpay_clientIdProd" => ["FriendlyName" => "Client_Id (Produção) <span class=\"badge\">3</span>", "Type" => "text", "Size" => "250", "Default" => "", "Description" => "<i style='color:#ff1c29;'>* (Obrigatório)</i>"],
            "efipixmpay_clientSecretProd" => ["FriendlyName" => "Client_Secret (Produção) <span class=\"badge\">4</span>", "Type" => "text", "Size" => "250", "Default" => "", "Description" => "<i style='color:#ff1c29;'>* (Obrigatório)</i>"],
            "efipixmpay_chavepix" => ["FriendlyName" => "Chave Pix da Conta <span class=\"badge\">5</span>", "Type" => "text", "Size" => "250", "Default" => "", "Description" => "Informe a chave pix<i style='color:#ff1c29;'>* (Obrigatório)</i>"],
            "efipixmpay_certificado" => ["FriendlyName" => "Certificado (Produção) <span class='badge'>6</span>", "Type" => "dropdown", "Options" => $arquivosdocertificado, "Description" => "<br>Selecione o <strong>.pem</strong> gerado pela EFI Pay e enviado em <code>modules/gateways/efipixmpay/certs</code>. Se aparecer erro PEM/OpenSSL, baixe o certificado novamente na EFI (produção) e substitua o arquivo. <a target=\"_blank\" href=\"https://dev.efipay.com.br/docs/api-pix/credenciais/\">Saiba Mais</a>"],
            "efipixmpay_tempoexpiracao" => ["FriendlyName" => "Tempo de Expiração (dias) <span class=\"badge\">7</span>", "Type" => "dropdown", "Options" => "1,2,5,10,15,20,25,30,60", "Default" => "5", "Description" => "Quantidade de (dias) em que a cobrança poderá ser paga após o vencimento. <i style='color:#ff1c29;'>* (Obrigatório)</i>"],
            "efipixmpay_companynome" => ["FriendlyName" => "Nome da Empresa <span class='badge'>8</span>", "Type" => "text", "Size" => "20", "Description" => "<br>Nome que irá aparecer para identificar nas Faturas. Ex.: MODULES PAY."],
            "efipixmpay_valor_minimo" => ["FriendlyName" => "Valor Mínimo para receber através deste gateway <span class='badge'>9</span>", "Type" => "text", "Size" => "4", "Description" => "<br>Exemplo:  10.00 (dez reais). Obs: Use o ponto (.) como delimitador de casas decimais. Deixe em branco para aceitar qualquer valor."],
            "efipixmpay_taxafixa" => ["FriendlyName" => "Taxa Fixa (R\$) <span class='badge'>10</span>", "Type" => "text", "Size" => "4", "Description" => "<br>Taxa Fixa cobrada a mais do cliente por utilizar este gateway de pagamento. Exemplo:  1.00 (um real). Obs: Use o ponto (.) como delimitador de casas decimais. Deixe em branco para não cobrar nenhuma taxa."],
            "efipixmpay_taxapercentual" => ["FriendlyName" => "Taxa (porcentagem %) <span class='badge'>11</span>", "Type" => "text", "Size" => "4", "Description" => "<br>Taxa em Porcentagem (%) cobrada a mais do cliente por utilizar este gateway de pagamento. Exemplo:  2.00 (porcento). Obs: Use o ponto (.) como delimitador de casas decimais. Deixe em branco para não cobrar nenhuma taxa."],
            "efipixmpay_multaporatraso" => ["FriendlyName" => "Multa por Atraso (%) <span class='badge'>12</span>", "Type" => "text", "Size" => "4", "Description" => "O percentual máximo autorizado é de 2%, de acordo artigo 52, parágrafo primeiro do Código de Defesa do Consumidor, Lei 8.078/90"],
            "efipixmpay_jurosporatraso" => ["FriendlyName" => "Juros Proporcional (%) <span class='badge'>13</span>", "Type" => "yesno", "Description" => "Ao aplicar 1% de juros máximo ao mês, esse percentual será cobrado proporcionalmente aos dias de atraso. Dividindo 1% por 30 dias = 0,033% por dia de atraso."],
            "efipixmpay_descontoantecipado" => ["FriendlyName" => "Desconto Antecipado (dias) <span class='badge'>14</span>", "Type" => "text", "Size" => "4", "Description" => "Número de dias para aplicar desconto quando ocorrer pagamento antecipado."],
            "efipixmpay_percentualdescontoantecipado" => ["FriendlyName" => "Desconto Antecipado (%) <span class='badge'>15</span>", "Type" => "text", "Size" => "4", "Description" => "% de desconto para o pagamento antecipado."],
            "efipixmpay_temcredito" => ["FriendlyName" => "Aplicar Crédito ao Gerar Nova Fatura <span class='badge'>16</span>", "Type" => "yesno", "Description" => "Habilite se quer aplicar saldo de crédito durante a criação da fatura"],
            "efipixmpay_obs_4" => ["FriendlyName" => "", "Description" => "<div style='padding:10px;background: rgba(0, 128, 0, 0.3);font-size: 1.0em;font-family: \"Lucida Console\", \"Courier New\", monospace;'><b>IMAGENS</b></div>"],
            "efipixmpay_imagem_botao" => ["FriendlyName" => "Imagem do botão [Pagar Agora] <span class='badge'>17</span>", "Type" => "text", "Size" => "50", "Description" => "<p>Insira a URL da imagem que será usada como botão [Pagar Agora] (tamanho recomendado 160x43 px)</p><p>Deixe em branco para exibir a imagem padrão.</p><img border=\"0\" src=\"" . htmlspecialchars((string) $imagemdobotao, ENT_QUOTES, "UTF-8") . "\">"],
            "efipixmpay_campocpfcnpj" => ["FriendlyName" => "Campo CPF/CNPJ <span class='badge'>18</span> ", "Type" => "dropdown", "Options" => $camposPersonalizados_CPF, "Size" => "40", "Description" => "<i style='color:#ff1c29;'>* (Obrigatório)</i><br>Reservado para campo personalizado do sistema WHMCS referente ao CPF e CNPJ.<br>"],
            "efipixmpay_obs_7" => ["FriendlyName" => "", "Description" => "<p><strong>@2025 - " . date("Y") . " <a href='https://centralvix.com.br/painel' target='_blank' style='text-decoration: underline;'>CentralVix</a> | Versão " . htmlspecialchars($versao_atual_modulo, ENT_QUOTES, "UTF-8") . " | <a href='" . htmlspecialchars($linktutorial, ENT_QUOTES, "UTF-8") . "' target='_blank' style='text-decoration: underline;'>Tutorial</a></strong></p>"],
        ];
        return $configarray;
    } catch (Throwable $e) {
        logActivity("[EFI PIX MPAY] Erro em efipixmpay_config: " . $e->getMessage());
        return [
            "FriendlyName" => ["Type" => "System", "Value" => "EFI PAY PIX - Modules Pay"],
            "efipixmpay_licensa" => [
                "FriendlyName" => "Chave de Licença - License Key",
                "Type" => "text",
                "Size" => "80",
                "Description" => "<div class=\"alert alert-danger\"><strong>Erro ao carregar configuração:</strong> " . htmlspecialchars($e->getMessage(), ENT_QUOTES, "UTF-8") . "<br>Salve novamente após corrigir. Contate o suporte se persistir.</div>",
            ],
        ];
    }
}
function efipixmpay_link($params)
{
    $invoiceId = $params["invoiceid"];
    $valor_minimo = $params["efipixmpay_valorminimo"];
    $paymentmethod = $params["paymentmethod"];
    $certificate = $params["efipixmpay_certificado"];
    $systemurl = $params["systemurl"];
    $clientId = $params["efipixmpay_clientIdProd"];
    $clientSecret = $params["efipixmpay_clientSecretProd"];
    $chavepix = $params["efipixmpay_chavepix"];
    $sandboxMode = $params["efipixmpay_sandboxMode"];
    $licenca = $params["efipixmpay_licensa"];
    $imagem_botao = $params["efipixmpay_imagem_botao"];
    $returnurl = $params["returnurl"];
    $descontoantecipado = $params["efipixmpay_descontoantecipado"];
    $percentualdescontoantecipado = $params["efipixmpay_percentualdescontoantecipado"];
    $gatewayModuleName = "efipixmpay";
    $PathCertificado = ROOTDIR . "/modules/gateways/efipixmpay/certs/" . $certificate;
    $pixefi = new efipixmpay($licenca);
    $statuslicenca = $pixefi->checkLicense();
    $hmac_url = $pixefi->obterDominioSemSubdominio();
    $hmac = $pixefi->cleanDomain($hmac_url);
    $urldocallback = $systemurl . "modules/gateways/efipixmpay/webhook/?hmac=" . $hmac . "&ignorar=";
    if (!is_array($statuslicenca) || ($statuslicenca["status"] ?? "") !== "Active") {
        return "<div class=\"alert alert-warning text-center\">\r\n    Este gateway no momento não está funcional. <br />Escolha outro.\r\n    </div>";
    }
    $pixefi->verify_Table();
    $imagempadrao = $systemurl . "modules/gateways/efipixmpay/images/gerar_pix.png";
    $imagemdobotao = $pixefi->exibe_botao($imagem_botao, $imagempadrao);
    $invoice = WHMCS\Database\Capsule::table("tblinvoices")->where("id", $invoiceId)->first();
    $client = WHMCS\Database\Capsule::table("tblclients")->where("id", $invoice->userid)->first();
    $cobranca = WHMCS\Database\Capsule::table("mod_efimpay")->where("fatura_id", $invoiceId)->first();
    if (!$invoice) {
        logActivity("[EFI PIX MPAY] Invoice ID: " . $invoiceId . " não localizada.");
        return "<div class=\"alert alert-warning text-center\">\r\n    Invoice Não Localizada.\r\n    </div>";
    }
    if (!$client) {
        logActivity("[EFI PIX MPAY] Client ID: " . $invoice->userid . " não localizado.");
        return "<div class=\"alert alert-warning text-center\">\r\n    Client ID: " . $invoice->userid . " não localizado.\r\n    </div>";
    }
    if (!empty($valor_minimo) && (float) $invoice->total < (float) $valor_minimo) {
        return "<div class=\"alert alert-warning text-center\">\r\n    O valor da fatura R\$ " . $invoice->total . " é inferior ao valor minimo aceito para pagamento via PIX que é de R\$ " . $pixefi->formatValue($valor_minimo) . " Utilize outra forma de pagamento.\r\n    </div>";
    }
    if ($paymentmethod != $gatewayModuleName) {
        return "<div class=\"alert alert-warning text-center\">\r\n    Este gateway no momento não está funcional. <br />Escolha outro.\r\n    </div>";
    }
    $cpfCnpj = $pixefi->PegaCPFCNPJ($params["efipixmpay_campocpfcnpj"], $client->id);
    $cpfCnpj = preg_replace("/\\D/", "", $cpfCnpj);
    if (!$pixefi->validador_cpf_cnpj($cpfCnpj)) {
        logActivity("[EFI PIX MPAY] O CPF/CNPJ do client ID: " . $invoice->userid . " está em branco ou não é um documento válido.");
        return "<div class=\"alert alert-warning text-center\">\r\n    O CPF/CNPJ do client ID: " . $invoice->userid . " está em branco ou não é um documento válido.\r\n    </div>";
    }
    $endparts = explode(",", $client->address1, 2);
    $endereco = trim($endparts[0]);
    $endnumero = isset($endparts[1]) ? trim($endparts[1]) : "S/N";
    $enderecocompleto = $endereco . ", " . $endnumero . ", " . $client->address2;
    $autorizacao = base64_encode($clientId . ":" . $clientSecret);
    if (!$pixefi->ArquivoExiste($PathCertificado)) {
        logActivity("[EFI PIX MPAY] Certificado de Produção não encontrado.");
        return "<div class=\"alert alert-warning text-center\">\r\n    Não foi localizado o arquivo do Certificado de Produção.\r\n    </div>";
    }
    $accessTokenRetorno = $pixefi->ApiGetAcessToken($autorizacao, $PathCertificado);
    if (!is_array($accessTokenRetorno) || empty($accessTokenRetorno["access_token"])) {
        $accessTokenErro = is_array($accessTokenRetorno)
            ? (string) ($accessTokenRetorno["error_description"] ?? ($accessTokenRetorno["error"] ?? "falha de autenticação"))
            : "falha de autenticação";
        logActivity("[EFI PIX MPAY] O Token Gerado é Inválido: " . $accessTokenErro);
        return "<div class=\"alert alert-warning text-center\">\r\n    O Token Gerado é Inválido: " . htmlspecialchars($accessTokenErro, ENT_QUOTES, "UTF-8") . "\r\n    </div>";
    }
    $accessToken = $accessTokenRetorno["access_token"];
    $listarHook = $pixefi->ListarWebhook($autorizacao, "2020-12-30T16:01:35Z", date("Y-m-d") . "T16:01:35Z", $PathCertificado, $accessToken);
    $cadastrarHook = true;
    if (isset($listarHook["webhooks"]) && is_array($listarHook["webhooks"])) {
        for ($i = 0; $i < count($listarHook["webhooks"]); $i++) {
            if ($listarHook["webhooks"][$i]["webhookUrl"] === $urldocallback) {
                $cadastrarHook = false;
            }
        }
    }
    if ($cadastrarHook) {
        $cadastrarWebhook = $pixefi->CadastrarWebhook($urldocallback, $chavepix, $accessToken, $PathCertificado);
    }
    $exibirHook = $pixefi->WebhookExiste($chavepix, $accessToken, $PathCertificado);
    $dataVencida = $pixefi->ValidarDatas($invoice->duedate, date("Y-m-d"));
    list($valordaInvoice, $dataVencimento, $valordastaxas, $totalJurosMulta) = $pixefi->calculateInvoiceValues($pixefi, $invoice->total, $invoice->duedate, $dataVencida, $params);
    $valordoDesconto = 0;
    $valorSemDesconto = (float) ($valordaInvoice + $valordastaxas + $totalJurosMulta);
    if (!empty($descontoantecipado) && !empty($percentualdescontoantecipado)) {
        $dias = (int) $descontoantecipado;
        $percentualdesc = (float) $percentualdescontoantecipado;
        $datalimite = date("Y-m-d", strtotime("-" . $dias . " days", strtotime($dataVencimento)));
        $dataLimiteDesconto = $pixefi->ValidarDatas($datalimite, date("Y-m-d"));
        if (!$dataLimiteDesconto) {
            $valorDoDesconto = $pixefi->naoArredondar($valorSemDesconto * $percentualdesc / 100);
        }
    }
    $valorTotal = $pixefi->formatValue($valorSemDesconto - $valorDoDesconto);
    if ($cobranca) {
        $txid = $cobranca->txid;
    } else {
        $txid = $pixefi->gerarTxidPix($invoiceId);
    }
    $dadosdopix = $pixefi->PegarStatusDoPix($txid, $PathCertificado, $accessToken);
    $criarPix = false;
    $atualizarPix = false;
    $manterPix = false;
    if ($dadosdopix["status"] == 404) {
        $criarPix = true;
    }
    if ($dadosdopix["status"] === "CONCLUIDA") {
        if ($cobranca->status != "CONCLUIDA") {
            $pixefi->UpdateTbl_Efi_Status($invoice->id, "CONCLUIDA");
        }
        logActivity("[EFI PIX MPAY] Invoice #" . $invoice->id . " Já está com o Status PAGO.");
        return "<div class=\"alert alert-warning text-center\">\r\n    Invoice #" . $invoice->id . " Já está com o Status PAGO.\r\n    </div>";
    }
    if ($dadosdopix["status"] === "REMOVIDA_PELO_USUARIO_RECEBEDOR") {
        if ($cobranca->status != "REMOVIDA_PELO_USUARIO_RECEBEDOR") {
            $pixefi->UpdateTbl_Efi_Status($invoice->id, "REMOVIDA_PELO_USUARIO_RECEBEDOR");
        }
        logActivity("[EFI PIX MPAY] Invoice #" . $invoice->id . " Não existe na EFI.");
        $criarPix = true;
    }
    if ($dadosdopix["status"] === "ATIVA") {
        if ($dataVencida || $cobranca->valor != $pixefi->formatValue($valorSemDesconto) || $dadosdopix["valor"]["original"] != $pixefi->formatValue($valorSemDesconto)) {
            $atualizarPix = true;
        } else {
            $manterPix = true;
        }
    }
    if ($criarPix) {
        $resultado = $pixefi->createNewPix($pixefi, $invoice, $client, $params, $pixefi->formatValue($valorSemDesconto), $dataVencimento, $accessToken);
        if (isset($resultado) && $resultado["status"] === "ATIVA") {
            if ($resultado["loc"]["id"]) {
                $responseLoc = $pixefi->GerarPixLocation($resultado["loc"]["id"], $PathCertificado, $accessToken);
                $PixCopiaCola = $responseLoc["qrcode"];
                $imagemQrcode = $responseLoc["imagemQrcode"];
                $linkvisual = $responseLoc["linkVisualizacao"];
                $pixefi->UpdateInsertTbl_Efi(["iddainvoice" => $invoice->id, "clientId" => $invoice->userid, "txid" => $resultado["txid"], "qrcode" => $PixCopiaCola, "qrcode_image" => $imagemQrcode, "link" => $linkvisual, "valor" => $pixefi->formatValue($resultado["valor"]["original"]), "tipo" => "pix", "duedate" => $resultado["calendario"]["dataDeVencimento"], "status" => $resultado["status"]]);
            }
        } else if (in_array($resultado["status"], [400, 403, 404, 503])) {
            logActivity("[EFI PIX MPAY] Erro: " . $resultado["status"] . " - " . $resultado["title"] . " - " . $resultado["detail"]);
            return "<div class=\"alert alert-warning text-center\">\r\n        Erro: " . $resultado["status"] . " - " . $resultado["title"] . " - " . $resultado["detail"] . "\r\n        </div>";
        }
    }
    if ($manterPix) {
        if (isset($dadosdopix) && $dadosdopix["status"] === "ATIVA") {
            if ($dadosdopix["loc"]["id"]) {
                $responseLocation = $pixefi->GerarPixLocation($dadosdopix["loc"]["id"], $PathCertificado, $accessToken);
                $PixCopiaCola = $responseLocation["qrcode"];
                $imagemQrcode = $responseLocation["imagemQrcode"];
                $linkvisual = $responseLocation["linkVisualizacao"];
                $pixefi->UpdateInsertTbl_Efi(["iddainvoice" => $invoice->id, "clientId" => $invoice->userid, "txid" => $dadosdopix["txid"], "qrcode" => $PixCopiaCola, "qrcode_image" => $imagemQrcode, "link" => $linkvisual, "valor" => $pixefi->formatValue($dadosdopix["valor"]["original"]), "tipo" => "pix", "duedate" => $dadosdopix["calendario"]["dataDeVencimento"], "status" => $dadosdopix["status"]]);
            }
        } else if (in_array($dadosdopix["status"], [400, 403, 404, 503])) {
            logActivity("[EFI PIX MPAY] Erro: " . $dadosdopix["status"] . " - " . $dadosdopix["title"] . " - " . $dadosdopix["detail"]);
            return "<div class=\"alert alert-warning text-center\">\r\n        Erro: " . $dadosdopix["status"] . " - " . $dadosdopix["title"] . " - " . $dadosdopix["detail"] . "\r\n        </div>";
        }
    }
    if ($atualizarPix && isset($dadosdopix) && $dadosdopix["status"] === "ATIVA") {
        $body = $pixefi->preparePixBody($pixefi, $invoice, $client, $cpfCnpj, $params, $pixefi->formatValue($valorSemDesconto), $dataVencimento);
        if (isset($body["calendario"]["dataDeVencimento"])) {
            $responsePix = $pixefi->ModificarDadosdoPix($dadosdopix["txid"], $body, $accessToken, $PathCertificado);
        }
        if ($responsePix["status"] && $responsePix["status"] == 400) {
            logActivity("[EFI PIX MPAY] Erro: " . $responsePix["detail"] . " - " . $responsePix["violacoes"][0]["razao"]);
            return "<div class=\"alert alert-warning text-center\">\r\n        Erro: " . $responsePix["detail"] . " - " . $responsePix["violacoes"][0]["razao"] . "\r\n        </div>";
        }
        if (isset($responsePix["loc"]["id"])) {
            $idLocationPix = $responsePix["loc"]["id"];
            $responseLoc = $pixefi->GerarPixLocation($idLocationPix, $PathCertificado, $accessToken);
            $PixCopiaCola = $responseLoc["qrcode"];
            $imagemQrcode = $responseLoc["imagemQrcode"];
            $linkvisual = $responseLoc["linkVisualizacao"];
            $pixefi->UpdateInsertTbl_Efi(["iddainvoice" => $invoice->id, "clientId" => $invoice->userid, "txid" => $responsePix["txid"], "qrcode" => $PixCopiaCola, "qrcode_image" => $imagemQrcode, "link" => $linkvisual, "valor" => $pixefi->formatValue($responsePix["valor"]["original"]), "tipo" => "pix", "duedate" => $responsePix["calendario"]["dataDeVencimento"], "status" => $responsePix["status"]]);
        }
    }
    $html = "<style>\r\n  .button:hover {border: none!important;}\r\n  .button {border: none!important;display: block;margin: 0 auto;}\r\n  .button:focus {outline-style: none;}\r\n  .b-transparent {background-color: transparent;}\r\n  #efipay-copy-button {\r\n    background-color: #4CAF50;\r\n    color: white;\r\n    border: none;\r\n    padding: 10px 20px;\r\n    text-align: center;\r\n    font-size: 16px;\r\n    border-radius: 8px;\r\n    cursor: pointer;\r\n    transition: background-color 0.3s ease;\r\n    display: inline-flex;\r\n    align-items: center;\r\n    gap: 8px;\r\n  }\r\n  #efipay-copy-button:hover {\r\n    background-color: #45a049;\r\n  }\r\n  #efipay-copy-button:focus {\r\n    outline: none;\r\n    box-shadow: 0 0 0 2px rgba(72, 180, 97, 0.4);\r\n  }\r\n  #efipay-copy-button i {\r\n    font-size: 16px;\r\n  }\r\n</style>";
    $html .= "<div align=\"center\">\r\n<p>\r\n<img style=\"width:150px;\" src=\"" . $systemurl . "modules/gateways/efipixmpay/images/efi.png\" />\r\n</p>\r\n<p>\r\nValor da Fatura: <b>R\$ " . $pixefi->formatValue($valordaInvoice) . "</b><br>";
    if ($pixefi->formatValue($valordastaxas) != "0.00") {
        $html .= "Valor das Taxas: <b>R\$ " . $pixefi->formatValue($valordastaxas) . "</b><br>";
    }
    if ($pixefi->formatValue($totalJurosMulta) != "0.00") {
        $html .= "Valor de Multas/Juros: <b>R\$ " . $pixefi->formatValue($totalJurosMulta) . "</b><br>";
    }
    if ($pixefi->formatValue($valorDoDesconto) != "0.00") {
        $html .= "Valor do Desconto: <b>R\$ " . $pixefi->formatValue($valorDoDesconto) . "</b><br>";
    }
    $html .= "\r\n<hr>";
    if ($pixefi->formatValue($valordastaxas) != "0.00" || $pixefi->formatValue($totalJurosMulta) != "0.00" || $pixefi->formatValue($valorDoDesconto) != "0.00") {
        $html .= "<b>Valor a Pagar: R\$ " . $pixefi->formatValue($valorTotal) . "</b>";
    }
    $html .= "\r\n</p>\r\n<div style=\"background: #fff;border: 2px solid #e0e0e0;border-radius: 12px;padding: 16px;display: inline-block;text-align: center;box-shadow: 0 4px 8px rgba(0,0,0,0.05);\">\r\n<img src=\"" . $imagemQrcode . "\" alt=\"Pix QR Code\" style=\"max-width: 220px; width: 100%; border-radius: 8px;\"/>\r\n<p style=\"margin-top: 10px; color: #333; font-weight: 500;\">Escaneie o QR Code para pagar com Pix</p>\r\n</div>\r\n<p><strong>Pix Copia e Cola:</strong></p>\r\n<textarea id=\"efipay-pix-copia-e-cola\" readonly style=\"width: 100%; height: 100px;\">" . $PixCopiaCola . "</textarea>\r\n<button id=\"efipay-copy-button\">\r\n<i class=\"fa-solid fa-copy\"></i> Copiar\r\n</button>\r\n<hr />\r\n<div class=\"alert alert-info text-center\">\r\n<b>Não é necessário enviar o comprovante. A baixa é automática após a liquidação do pagamento.</b>\r\n</div>\r\n</div>";
    $html .= "\r\n<script>\r\ndocument.getElementById('efipay-copy-button').addEventListener('click', function() {\r\n  var copyText = document.getElementById('efipay-pix-copia-e-cola');\r\n  if (navigator.clipboard && window.isSecureContext) {\r\n    navigator.clipboard.writeText(copyText.value).then(function() {\r\n      alert('Copiado para a área de transferência!');\r\n    }, function(err) {\r\n      console.error('Erro ao copiar: ', err);\r\n    });\r\n  } else {\r\n    copyText.select();\r\n    copyText.setSelectionRange(0, 99999);\r\n    var successful = document.execCommand('copy');\r\n    if (successful) {\r\n      alert('Copiado para a área de transferência!');\r\n    } else {\r\n      alert('Não foi possível copiar. Por favor, copie manualmente.');\r\n    }\r\n  }\r\n});\r\n</script>\r\n\r\n<script>\r\n// Função para verificar o pagamento a cada 3 segundos\r\ndocument.addEventListener('DOMContentLoaded', function() {\r\n  let elapsedTime = 0; // Tempo decorrido em segundos\r\n  const interval = 3000; // Intervalo de 3 segundos\r\n  const timeout = 180000; // Limite de 6 minutos\r\n\r\n  const idInvoice = " . json_encode($invoice->id) . ";\r\n  const Txid = " . json_encode($txid) . ";\r\n\r\n  function checkPayment() {\r\n    fetch('" . $systemurl . "modules/gateways/efipixmpay/checkout/validarpagamento.php', {\r\n      method: 'POST',\r\n      headers: {\r\n        'Content-Type': 'application/x-www-form-urlencoded',\r\n      },\r\n      body: 'iddainvoice=' + encodeURIComponent(idInvoice),\r\n    })\r\n    .then(response => response.text())\r\n    .then(result => {\r\n      if (result.trim() === 'OK') {\r\n        window.location.href = '" . $systemurl . "viewinvoice.php?id=" . $invoice->id . "';\r\n      } else {\r\n        elapsedTime += interval;\r\n        if (elapsedTime >= timeout) {\r\n          console.log('Tempo limite atingido. Encerrando verificação.');\r\n          clearInterval(checkInterval);\r\n        }\r\n      }\r\n    })\r\n    .catch(error => {\r\n      console.error('Erro ao verificar pagamento:', error);\r\n      clearInterval(checkInterval);\r\n    });\r\n  }\r\n\r\n  const checkInterval = setInterval(checkPayment, interval);\r\n});\r\n</script>";
    return $html;
}

?>