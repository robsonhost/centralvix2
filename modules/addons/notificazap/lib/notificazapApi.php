<?php
/*
 * @ https://EasyToYou.eu - IonCube v14 Decoder Online
 * @ PHP 7.4
 * @ Decoder version: 1.0.2
 * @ Release: 10/08/2022
 */

// Decoded file for php version 74.
include_once __DIR__ . "/../../../../init.php";
include_once __DIR__ . "/../../../../notificazapCore.php";
$WHMCS_URLSISTEMA = rtrim(App::getSystemUrl(), "/");
if (!empty($_POST)) {
    $core = new notificazapCore();
    $titulos_gerais = ["Ativação de Serviço", "Validação Número Whatsapp", "Alerta Login", "Alteração de Senha", "Fatura Criada", "Lembrete de Vencimento", "Confirmação de Pagamento", "Cancelamento de Fatura", "Suspensão de Serviço", "Reativação do Serviço", "Abertura de Ticket", "Resposta do Ticket", "Fechar Ticket", "Cadastro Novo Cliente", "Falha de Login", "Cancelamento de Serviço", "Troca de Senha do Cliente", "Serviço Pronto", "Lembrete Fatura Vencida", "Solicitação Cancelamento", "Alteração Status Cotação", "Cotação Aceita", "Enviar Cotação", "Ticket Close", "Feliz Aniversário", "Nota Fiscal Asaas", "Alerta de Suspensão de Serviço", "Alerta de Cancelamento de Serviço", "Fatura Vencendo Hoje"];
    if ($_POST["acao"] == "post_editarmensagem") {
        try {
            WHMCS\Database\Capsule::table("mod_notificazap_templates")->WHERE("id", $_POST["id"])->update(["message" => $_POST["mensagem"]]);
            echo "<script>\r\n            Swal.fire({\r\n                icon: 'success',\r\n                title: 'Sucesso!',\r\n                text: 'Template editado com sucesso.',\r\n                showConfirmButton: false,\r\n                timer: 3000\r\n            }).then(() => {\r\n                window.location.href = 'addonmodules.php?module=notificazap&action=templates';\r\n            });\r\n            </script>";
        } catch (Throwable $th) {
            echo "<script>\r\n            Swal.fire({\r\n                icon: 'error',\r\n                title: 'Erro!',\r\n                text: 'Ops, não foi possível editar o template.',\r\n                }).then(() => {\r\n                    window.location.href = 'addonmodules.php?module=notificazap&action=templates';\r\n                    });\r\n                    </script>";
        }
    }
    if ($_POST["acao"] == "desativarTemplate") {
        try {
            WHMCS\Database\Capsule::table("mod_notificazap_templates")->WHERE("id", $_POST["id"])->update(["status" => "deactive"]);
            echo "<script>\r\n            Swal.fire({\r\n                icon: 'success',\r\n                title: 'Sucesso!',\r\n                text: 'Template desativado com sucesso.',\r\n                showConfirmButton: false,\r\n                timer: 3000\r\n                }).then(() => {\r\n                    window.location.href = 'addonmodules.php?module=notificazap&action=templates';\r\n                    });\r\n                    </script>";
        } catch (Throwable $th) {
            echo "<script>\r\n            Swal.fire({\r\n                icon: 'error',\r\n                title: 'Erro!',\r\n                text: 'Ops, não foi possível desativar o template.',\r\n                }).then(() => {\r\n                    window.location.href = 'addonmodules.php?module=notificazap&action=templates';\r\n                    });\r\n                    </script>";
        }
    }
    if ($_POST["acao"] == "ativarTemplate") {
        try {
            WHMCS\Database\Capsule::table("mod_notificazap_templates")->WHERE("id", $_POST["id"])->update(["status" => "active"]);
            echo "<script>\r\n            Swal.fire({\r\n                icon: 'success',\r\n                title: 'Sucesso!',\r\n                text: 'Template ativado com sucesso.',\r\n                showConfirmButton: false,\r\n                timer: 3000\r\n                }).then(() => {\r\n                    window.location.href = 'addonmodules.php?module=notificazap&action=templates';\r\n                    });\r\n                    </script>";
        } catch (Throwable $th) {
            echo "<script>\r\n            Swal.fire({\r\n                icon: 'error',\r\n                title: 'Erro!',\r\n                text: 'Ops, não foi possível ativar o template.',\r\n                }).then(() => {\r\n                    window.location.href = 'addonmodules.php?module=notificazap&action=templates';\r\n                    });\r\n                    </script>";
        }
    }
    if ($_POST["acao"] == "editarTemplateMensagem") {
        $IDTemplate = $_POST["id"];
        $template = WHMCS\Database\Capsule::table("mod_notificazap_templates")->where("id", $IDTemplate)->first();
        if ($template) {
            $ID = $template->id;
            $Titulo = $core->NOTIFICAZAP_Titulo_Template($template->type);
            $Mensagem = $template->message;
            $Status = $template->status;
        }
        echo "        <nav aria-label=\"breadcrumb\">\r\n            <ol class=\"breadcrumb\">\r\n                <li class=\"breadcrumb-item active\" aria-current=\"page\">WHMCS</li>\r\n                <li class=\"breadcrumb-item active\" aria-current=\"page\">Addons</li>\r\n                <li class=\"breadcrumb-item active\" aria-current=\"page\">ZAP MPAY</li>\r\n                <li class=\"breadcrumb-item active\" aria-current=\"page\">Templates de Mensagem</li>\r\n                <li class=\"breadcrumb-item active\" aria-current=\"page\">Editar mensagem</li>\r\n                <li class=\"breadcrumb-item active\" aria-current=\"page\"><strong>";
        echo htmlspecialchars($Titulo);
        echo "</strong></li>\r\n            </ol>\r\n        </nav>\r\n        ";
        echo $Status != "active" ? "<div class=\"alert alert-warning\" role=\"alert\"><i class=\"fal fa-info-circle\"></i> Este template esta desativado, note que o mesmo não sera adicionado a fila de envio.</div>" : "";
        echo "        <div id=\"div_carregando_template\" style=\"display: none;\">Carregando...</div>\r\n        <div id=\"div_retorno_template\"></div>\r\n        <form method=\"POST\" id=\"EditarTemplate\" onSubmit=\"sendEditarTemplate();return false;\" style=\"padding-bottom: 20px\">\r\n            <input type=\"hidden\" name=\"acao\" value=\"post_editarmensagem\">\r\n            <input type=\"hidden\" name=\"id\" value=\"";
        echo $ID;
        echo "\">\r\n            <div class=\"form-group\">\r\n                <label for=\"mensagem\">Conteudo da Mensagem [Pode ser utilizado Emoji 😀 nas Mensagens] - <a href=\"https://pt.piliapp.com/emoji/list/\" target=\"_blank\">Lista de Emoji</a></label>\r\n                <div class=\"btn-toolbar\" role=\"toolbar\" aria-label=\"Toolbar with button groups\" style=\"position: absolute; margin-top: 1px; margin-left: 1px;\">\r\n                    <div class=\"btn-group mr-2\" role=\"group\" aria-label=\"First group\" style=\"background-color: #f4f4f4; border-radius: 0px 0px 0px 17px;\">\r\n                        <li style=\"float:left; padding: 5px 20px; list-style: none;\">Ações disponíveis</li>\r\n                        <button style=\"border-radius: 0px 0px 0px 4px;\" type=\"button\" class=\"btn btn-primary\" onclick=\"addTag_Edicao('*');\"><i class=\"fas fa-bold\"></i></button>\r\n                        <button type=\"button\" class=\"btn btn-primary\" onclick=\"addTag_Edicao('_');\"><i class=\"fas fa-italic\"></i></button>\r\n                        <button type=\"button\" class=\"btn btn-primary\" onclick=\"addTag_Edicao('~');\"><i class=\"fas fa-strikethrough\"></i></button>\r\n                        <button style=\"border-radius: 0px 4px 0px 0px;\" type=\"button\" class=\"btn btn-primary\" onclick=\"addTag_Edicao('```');\"><i class=\"fab fa-autoprefixer\"></i></button>\r\n                    </div>\r\n                </div>\r\n                <textarea class=\"form-control\" id=\"mensagem\" name=\"mensagem\" rows=\"8\" style=\"border-radius: 5px; padding: 50px 20px 20px 20px; margin-bottom: 15px;\">";
        echo htmlspecialchars($Mensagem);
        echo " </textarea>\r\n                <table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\r\n                    <tbody>\r\n                        ";
        if (in_array($Titulo, $titulos_gerais)) {
            echo "                            <tr>\r\n                                <td width=\"15%\" class=\"fieldlabel\">Nome do Cliente</td>\r\n                                <td class=\"fieldarea\"><strong>%name%</strong></td>\r\n                            </tr>\r\n                            <tr>\r\n                                <td width=\"15%\" class=\"fieldlabel\">Assinatura das mensagens</td>\r\n                                <td class=\"fieldarea\"><strong>%assinatura%</strong></td>\r\n                            </tr>\r\n                            <tr>\r\n                                <td width=\"15%\" class=\"fieldlabel\">Dominio do WHMCS</td>\r\n                                <td class=\"fieldarea\"><strong>%dominiowhmcs%</strong></td>\r\n                            </tr>\r\n                        ";
        }
        if ($Titulo == "Feliz Aniversário") {
            echo "                            <tr>\r\n                                <td width=\"15%\" class=\"fieldlabel\">Idade do Cliente</td>\r\n                                <td class=\"fieldarea\"><strong>%idade%</strong></td>\r\n                            </tr>\r\n                        ";
        }
        if ($Titulo == "Cadastro Novo Cliente") {
            echo "                            <tr>\r\n                                <td width=\"15%\" class=\"fieldlabel\">Nome da Empresa (salvo nas configs. do WHMCS)</td>\r\n                                <td class=\"fieldarea\"><strong>%company%</strong></td>\r\n                            </tr>\r\n                        ";
        }
        if ($Titulo == "Validação Número Whatsapp") {
            echo "                            <tr>\r\n                                <td width=\"15%\" class=\"fieldlabel\">Código de Validação</td>\r\n                                <td class=\"fieldarea\"><strong>%codigo_Validacao%</strong></td>\r\n                            </tr>\r\n                        ";
        }
        $titulos_login_senha = ["Alerta Login", "Falha de Login", "Troca de Senha do Cliente"];
        if (in_array($Titulo, $titulos_login_senha)) {
            echo "                            <tr>\r\n                                <td width=\"15%\" class=\"fieldlabel\">Data do Evento</td>\r\n                                <td class=\"fieldarea\"><strong>%date%</strong></td>\r\n                            </tr>\r\n                            <tr>\r\n                                <td width=\"15%\" class=\"fieldlabel\">Hora do Evento</td>\r\n                                <td class=\"fieldarea\"><strong>%hour%</strong></td>\r\n                            </tr>\r\n                            <tr>\r\n                                <td width=\"15%\" class=\"fieldlabel\">IP do Evento</td>\r\n                                <td class=\"fieldarea\"><strong>%ipaddr%</strong></td>\r\n                            </tr>\r\n                        ";
        }
        $titulos_email = ["Falha de Login", "Troca de Senha do Cliente", "Serviço Pronto", "Ativação de Serviço"];
        if (in_array($Titulo, $titulos_email)) {
            echo "                            <tr>\r\n                                <td width=\"15%\" class=\"fieldlabel\">E-mail</td>\r\n                                <td class=\"fieldarea\"><strong>%email%</strong></td>\r\n                            </tr>\r\n                        ";
        }
        $titulos_dns = ["Ativação de Serviço", "Serviço Pronto"];
        if (in_array($Titulo, $titulos_dns)) {
            echo "                            <tr>\r\n                                <td width=\"15%\" class=\"fieldlabel\">Servidor de DNS1</td>\r\n                                <td class=\"fieldarea\"><strong>%ns1%</strong></td>\r\n                            </tr>\r\n                            <tr>\r\n                                <td width=\"15%\" class=\"fieldlabel\">Servidor de DNS2</td>\r\n                                <td class=\"fieldarea\"><strong>%ns2%</strong></td>\r\n                            </tr>\r\n                            <tr>\r\n                                <td width=\"15%\" class=\"fieldlabel\">Nome de Usuario</td>\r\n                                <td class=\"fieldarea\"><strong>%username%</strong></td>\r\n                            </tr>\r\n                            <tr>\r\n                                <td width=\"15%\" class=\"fieldlabel\">Senha</td>\r\n                                <td class=\"fieldarea\"><strong>%password%</strong></td>\r\n                            </tr>\r\n                            <tr>\r\n                                <td width=\"15%\" class=\"fieldlabel\">IP</td>\r\n                                <td class=\"fieldarea\"><strong>%ip%</strong></td>\r\n                            </tr>\r\n                            <tr>\r\n                                <td width=\"15%\" class=\"fieldlabel\">Data Vencimento</td>\r\n                                <td class=\"fieldarea\"><strong>%duedate%</strong></td>\r\n                            </tr>\r\n                        ";
        }
        $titulos_linkdainvoice = ["Fatura Criada", "Lembrete de Vencimento", "Confirmação de Pagamento", "Cancelamento de Fatura", "Lembrete Fatura Vencida", "Fatura Vencendo Hoje"];
        if (in_array($Titulo, $titulos_linkdainvoice)) {
            echo "                            <tr>\r\n                                <td width=\"15%\" class=\"fieldlabel\">Número da Fatura</td>\r\n                                <td class=\"fieldarea\">\r\n                                    <strong>%invoiceid%</strong>\r\n                                </td>\r\n                            </tr>\r\n                            <tr>\r\n                                <td width=\"15%\" class=\"fieldlabel\">Link da Fatura no WHMCS</td>\r\n                                <td class=\"fieldarea\">\r\n                                    <strong>%linkdainvoice%</strong>\r\n                                </td>\r\n                            </tr>\r\n                            <tr>\r\n                                <td width=\"15%\" class=\"fieldlabel\">Produto</td>\r\n                                <td class=\"fieldarea\">\r\n                                    <strong>%produto%</strong>\r\n                                </td>\r\n                            </tr>\r\n                        ";
        }
        $titulos_faturavalor = ["Fatura Criada", "Lembrete de Vencimento", "Confirmação de Pagamento", "Cancelamento de Fatura", "Lembrete Fatura Vencida", "Serviço Pronto", "Fatura Vencendo Hoje"];
        if (in_array($Titulo, $titulos_faturavalor)) {
            echo "                            <tr>\r\n                                <td width=\"15%\" class=\"fieldlabel\">Valor Fatura</td>\r\n                                <td class=\"fieldarea\">\r\n                                    <strong>%value%</strong>\r\n                                </td>\r\n                            </tr>\r\n                        ";
        }
        $dias_vencido_faturar = ["Lembrete Fatura Vencida"];
        if (in_array($Titulo, $dias_vencido_faturar)) {
            echo "                            <tr>\r\n                                <td width=\"15%\" class=\"fieldlabel\">Dias que a fatura está em atraso</td>\r\n                                <td class=\"fieldarea\">\r\n                                    <strong>%days%</strong>\r\n                                </td>\r\n                            </tr>\r\n                        ";
        }
        $titulos_empresa = ["Confirmação de Pagamento"];
        if (in_array($Titulo, $titulos_empresa)) {
            echo "                            <tr>\r\n                                <td width=\"15%\" class=\"fieldlabel\">Nome da Empresa</td>\r\n                                <td class=\"fieldarea\">\r\n                                    <strong>%nomeempresa%</strong>\r\n                                </td>\r\n                            </tr>\r\n                            <tr>\r\n                                <td width=\"15%\" class=\"fieldlabel\">Data do Pagamento</td>\r\n                                <td class=\"fieldarea\">\r\n                                    <strong>%datapagamento%</strong>\r\n                                </td>\r\n                            </tr>\r\n                        ";
        }
        $titulos_datavenc = ["Fatura Criada", "Lembrete de Vencimento", "Cancelamento de Fatura", "Lembrete Fatura Vencida", "Fatura Vencendo Hoje"];
        if (in_array($Titulo, $titulos_datavenc)) {
            echo "                            <tr>\r\n                                <td width=\"15%\" class=\"fieldlabel\">Vencimento da Fatura</td>\r\n                                <td class=\"fieldarea\">\r\n                                    <strong>%duedate%</strong>\r\n                                </td>\r\n                            </tr>\r\n                        ";
        }
        $titulos_formapgto = ["Fatura Criada", "Lembrete de Vencimento", "Lembrete Fatura Vencida", "Cancelamento de Fatura", "Fatura Vencendo Hoje"];
        if (in_array($Titulo, $titulos_formapgto)) {
            echo "                            <tr>\r\n                                <td width=\"15%\" class=\"fieldlabel\">Forma de Pagamento</td>\r\n                                <td class=\"fieldarea\">\r\n                                    <strong>%formapgto%</strong>\r\n                                </td>\r\n                            </tr>\r\n                        ";
        }
        $titulos_produto = ["Suspensão de Serviço", "Reativação do Serviço", "Ativação de Serviço", "Cancelamento de Serviço", "Serviço Pronto", "Alerta de Suspensão de Serviço", "Alerta de Cancelamento de Serviço"];
        if (in_array($Titulo, $titulos_produto)) {
            echo "                            <tr>\r\n                                <td width=\"15%\" class=\"fieldlabel\">Nome do Produto</td>\r\n                                <td class=\"fieldarea\">\r\n                                    <strong>%product%</strong>\r\n                                </td>\r\n                            </tr>\r\n                        ";
        }
        $titulos_ticket = ["Abertura de Ticket", "Resposta do Ticket", "Fechar Ticket", "Ticket Close"];
        if (in_array($Titulo, $titulos_ticket)) {
            echo "                            <tr>\r\n                                <td width=\"15%\" class=\"fieldlabel\">ID do Ticket</td>\r\n                                <td class=\"fieldarea\">\r\n                                    <strong>%ticket%</strong>\r\n                                </td>\r\n                            </tr>\r\n                        ";
        }
        $titulos_datahora = ["Abertura de Ticket", "Resposta do Ticket"];
        if (in_array($Titulo, $titulos_datahora)) {
            echo "                            <tr>\r\n                                <td width=\"15%\" class=\"fieldlabel\">Data da abertura / resposta</td>\r\n                                <td class=\"fieldarea\">\r\n                                    <strong>%date%</strong>\r\n                                </td>\r\n                            </tr>\r\n                            <tr>\r\n                                <td width=\"15%\" class=\"fieldlabel\">Hora da abertura / resposta</td>\r\n                                <td class=\"fieldarea\">\r\n                                    <strong>%hour%</strong>\r\n                                </td>\r\n                            </tr>\r\n                        ";
        }
        $titulos_ticketclose = ["Fechar Ticket"];
        if (in_array($Titulo, $titulos_ticketclose)) {
            echo "                            <tr>\r\n                                <td width=\"15%\" class=\"fieldlabel\">Titulo do Chamado</td>\r\n                                <td class=\"fieldarea\">\r\n                                    <strong>%tituloticket%</strong>\r\n                                </td>\r\n                            </tr>\r\n                            <tr>\r\n                                <td width=\"15%\" class=\"fieldlabel\">Key do Chamado</td>\r\n                                <td class=\"fieldarea\">\r\n                                    <strong>%keychamado%</strong>\r\n                                </td>\r\n                            </tr>\r\n                        ";
        }
        $titulos_cancelamentos = ["Solicitação Cancelamento"];
        if (in_array($Titulo, $titulos_cancelamentos)) {
            echo "                            <tr>\r\n                                <td width=\"15%\" class=\"fieldlabel\">ID do Serviço</td>\r\n                                <td class=\"fieldarea\">\r\n                                    <strong>%id%</strong>\r\n                                </td>\r\n                            </tr>\r\n                            <tr>\r\n                                <td width=\"15%\" class=\"fieldlabel\">Tipo do Serviço</td>\r\n                                <td class=\"fieldarea\">\r\n                                    <strong>%tipo%</strong>\r\n                                </td>\r\n                            </tr>\r\n                            <tr>\r\n                                <td width=\"15%\" class=\"fieldlabel\">Razão do Cancelamento</td>\r\n                                <td class=\"fieldarea\">\r\n                                    <strong>%razao%</strong>\r\n                                </td>\r\n                            </tr>\r\n                        ";
        }
        $titulos_nfasaas = ["Nota Fiscal Asaas"];
        if (in_array($Titulo, $titulos_nfasaas)) {
            echo "                            <tr>\r\n                                <td width=\"15%\" class=\"fieldlabel\">Número da Nota Fiscal</td>\r\n                                <td class=\"fieldarea\">\r\n                                    <strong>%numeronf%</strong>\r\n                                </td>\r\n                            </tr>\r\n                            <tr>\r\n                                <td width=\"15%\" class=\"fieldlabel\">Data Emissão da NF</td>\r\n                                <td class=\"fieldarea\">\r\n                                    <strong>%nfdataemissao%</strong>\r\n                                </td>\r\n                            </tr>\r\n                            <tr>\r\n                                <td width=\"15%\" class=\"fieldlabel\">Produto</td>\r\n                                <td class=\"fieldarea\">\r\n                                    <strong>%produto%</strong>\r\n                                </td>\r\n                            </tr>\r\n                            <tr>\r\n                                <td width=\"15%\" class=\"fieldlabel\">ID da Fatura</td>\r\n                                <td class=\"fieldarea\">\r\n                                    <strong>%invoiceid%</strong>\r\n                                </td>\r\n                            </tr>\r\n                            <tr>\r\n                                <td width=\"15%\" class=\"fieldlabel\">Valor da Fatura</td>\r\n                                <td class=\"fieldarea\">\r\n                                    <strong>%value%</strong>\r\n                                </td>\r\n                            </tr>\r\n                        ";
        }
        $titulos_cotacao = ["Alteração Status Cotação", "Cotação Aceita", "Enviar Cotação"];
        if (in_array($Titulo, $titulos_cotacao)) {
            echo "                            <tr>\r\n                                <td width=\"15%\" class=\"fieldlabel\">Orçamento Número</td>\r\n                                <td class=\"fieldarea\">\r\n                                    <strong>%idcotacao%</strong>\r\n                                </td>\r\n                            </tr>\r\n                            <tr>\r\n                                <td width=\"15%\" class=\"fieldlabel\">Status do Orçamento</td>\r\n                                <td class=\"fieldarea\">\r\n                                    <strong>%statuscotacao%</strong>\r\n                                </td>\r\n                            </tr>\r\n                        ";
        }
        echo "                    </tbody>\r\n                </table>\r\n            </div>\r\n            <button class=\"btn btn-success\" type=\"submit\"><i class=\"fal fa-save\"></i> Salvar Template</button>\r\n        </form>\r\n";
    }
}
echo "<script>\r\n    function sendEditarTemplate() {\r\n        \$(\".btn-success\").prop(\"disabled\", true);\r\n        \$(\"#div_retorno_template\").hide();\r\n        \$(\"#div_carregando_template\").show();\r\n\r\n        \$.ajax({\r\n            type: \"POST\",\r\n            url: \"";
echo $WHMCS_URLSISTEMA;
echo "/modules/addons/notificazap/lib/notificazapApi.php\",\r\n            data: \$(\"#EditarTemplate\").serialize()\r\n        })\r\n        .done(function(data) {\r\n            \$(\"#div_carregando_template\").hide();\r\n            Swal.fire({\r\n                icon: 'success',\r\n                title: 'Sucesso!',\r\n            html: data, // Exibe a mensagem de sucesso retornada pelo servidor\r\n            timer: 1500,\r\n            showConfirmButton: false\r\n        });\r\n        })\r\n        .fail(function(jqXHR, textStatus, errorThrown) {\r\n            \$(\"#div_carregando_template\").hide();\r\n            Swal.fire({\r\n                icon: 'error',\r\n                title: 'Erro!',\r\n                html: `Erro ao editar o template: \${textStatus} - \${errorThrown}`,\r\n            });\r\n            console.error(\"Erro AJAX:\", textStatus, errorThrown);\r\n        })\r\n        .always(function() {\r\n            \$(\".btn-success\").prop(\"disabled\", false);\r\n        });\r\n    }\r\n    function addTag_Edicao(tag) {\r\n        var textarea = document.getElementById(\"mensagem\");\r\n        if (!textarea) {\r\n            console.error(\"Elemento textarea com ID 'mensagem' não encontrado.\");\r\n            Swal.fire({\r\n                icon: 'error',\r\n                title: 'Erro!',\r\n                text: 'Elemento textarea com ID \"mensagem\" não encontrado.',\r\n            });\r\n            return;\r\n        }\r\n        var start = textarea.selectionStart;\r\n        var end = textarea.selectionEnd;\r\n        var value = textarea.value;\r\n        if (start === end) {\r\n            Swal.fire({\r\n                icon: 'warning',\r\n                title: 'Atenção!',\r\n                text: 'Sem texto selecionado.',\r\n            });\r\n        textarea.focus(); // Manter o foco no textarea\r\n        return;\r\n    }\r\n    var selectedText = value.substring(start, end);\r\n    var newText = value.substring(0, start) + tag + selectedText + tag + value.substring(end);\r\n    textarea.value = newText;\r\n    // Opcional: Manter a seleção do texto com as tags\r\n    textarea.focus();\r\n    textarea.selectionStart = start;\r\n    textarea.selectionEnd = start + 2 * tag.length + selectedText.length;\r\n}\r\n</script>";

?>