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
    exit("Acesso restrito - Power by www.modulespay.com.br");
}
if (!defined("ROOTDIR")) {
    define("ROOTDIR", dirname(__DIR__, 3));
}
include_once ROOTDIR . "/modules/addons/notificazap/config.php";
include_once ROOTDIR . "/modules/addons/notificazap/lib/notificazapCore.php";
include_once ROOTDIR . "/modules/addons/notificazap/lib/notificaWhatsApp.php";
include_once ROOTDIR . "/modules/addons/notificazap/notificavalida.php";
add_hook("AdminClientServicesTabFields", 1, function ($vars) {
    $core = new notificazapCore();
    $onlineServer = $core->NOTIFICAZAP_Servidor_WhatsApp_Online(NZURLSERVIDORWHATSAPP);
    if (!$onlineServer) {
        $core->NOTIFICAZAP_LogWarning("[NOTIFICA ZAP] Servidor do WhatsApp está offline.");
    } else {
        $onlineModulesPay = $core->NOTIFICAZAP_ModulesPay_Online(NZSITEMODULESPAY);
        if ($onlineModulesPay) {
            $license = NOTIFICAZAP_check_license($core->NOTIFICAZAP_Licenca());
            $statusdalicenca = $license["status"];
        } else {
            $core->NOTIFICAZAP_LogWarning("Servidor da MODULES PAY está offline ou inacessível.");
            $statusdalicenca = "Active";
        }
        if ($statusdalicenca !== "Active") {
            $core->NOTIFICAZAP_LogWarning("[NOTIFICA ZAP] Licença inválida ou expirada.");
        } else {
            $whatsapp = new notificaWhatsAppMpay($core->NOTIFICAZAP_Server(), $core->NOTIFICAZAP_Descriptografar($core->NOTIFICAZAP_KEY()), $core->NOTIFICAZAP_Token_Geral(), $core->NOTIFICAZAP_Telefone_Origem());
            $information = $whatsapp->getInformation();
            $conexao = $whatsapp->connectionStatus();
            if (!is_array($information) || !isset($information["status"]) || !is_array($conexao) || !isset($conexao["instance"]) || !is_array($conexao["instance"]) || !isset($conexao["instance"]["state"])) {
                return NULL;
            }
            if ($information["status"] !== 200 || $conexao["instance"]["state"] !== "open") {
                return NULL;
            }
            $service = WHMCS\Database\Capsule::table("tblhosting")->find($vars["id"]);
            $idCampoReceberNotificacao = $core->NOTIFICAZAP_Pega_Info_tbladdonmodules("addon_notificazap_receber_whatsapp");
            $receberNotificacao = $core->NOTIFICAZAP_Pega_Info_tblcustomfieldsvalues($idCampoReceberNotificacao, $service->userid);
            $urldoWHMCS = $core->NOTIFICAZAP_Pega_Info_tblconfiguration("SystemURL");
            $optionselect = "";
            if ($service->domainstatus === "Active") {
                $optionselect .= "\r\n        <option value=\"serviceready\">Serviço Pronto</option>\r\n        <option value=\"servicealertsuspended\">Alerta de Suspensão de Serviço</option>\r\n        ";
            }
            if ($service->domainstatus === "Suspended") {
                $optionselect .= "\r\n        <option value=\"servicesuspended\">Aviso de Serviço Suspenso</option>\r\n        <option value=\"servicereactived\">Reativação do Serviço</option>\r\n        <option value=\"servicealertcancelled\">Alerta de Cancelamento de Serviço</option>\r\n        ";
            }
            if ($service->domainstatus === "Cancelled") {
                $optionselect .= "<option value=\"servicecancelled\">Cancelamento de Serviço</option>";
            }
            echo "<div style=\"margin-top: 15px;margin-bottom: 7px\">\r\n    <label for=\"selectOptionAlert\" style=\"margin-right: 10px;\">[NOTIFICA ZAP] Selecione uma opção:</label>\r\n    <select id=\"selectOptionAlert\" style=\"padding: 5px; border-radius: 5px; border: 1px solid #ccc;\">\r\n    <option value=\"\">Escolha...</option>\r\n    " . $optionselect . "\r\n    </select>\r\n    <button id=\"sendButtonAlert\" style=\"margin-left: 10px; padding: 5px 15px; border: none; border-radius: 5px; background-color: #007bff; color: white; cursor: pointer;\">\r\n    Enviar\r\n    </button>\r\n    </div>\r\n    <script>\r\n    const sendButton = document.getElementById('sendButtonAlert');\r\n    const selectOption = document.getElementById('selectOptionAlert');\r\n    // Adiciona evento ao botão \"Enviar\"\r\n    sendButton.addEventListener('click', (event) => {\r\n        event.preventDefault();\r\n        const selectedValue = selectOption.value;\r\n        const idservice = \"" . $service->id . "\";\r\n        const urldominio = \"" . $urldoWHMCS . "\";\r\n        // Verificar se uma opção foi selecionada\r\n        if (!selectedValue) {\r\n            Swal.fire({\r\n                icon: 'warning',\r\n                title: 'Atenção',\r\n                text: 'Por favor, selecione uma opção antes de enviar!',\r\n                });\r\n                return;            \r\n            }\r\n            // Desabilitar o botão enquanto envia\r\n            sendButton.textContent = 'Enviando...';\r\n            sendButton.disabled = true;\r\n            // Enviar os dados via AJAX\r\n            fetch(urldominio + 'modules/addons/notificazap/enviar.notificacao_service.php', {\r\n                method: 'POST',\r\n                headers: {\r\n                    'Content-Type': 'application/x-www-form-urlencoded',\r\n                    },\r\n                    body: 'template=' + selectedValue + '&idservice=' + idservice,\r\n                    })\r\n                    .then(response => response.json())\r\n                    .then(data => {\r\n                        sendButton.textContent = 'Enviar';\r\n                        sendButton.disabled = false;\r\n                        if (data.status === 'success') {\r\n                            Swal.fire({\r\n                                icon: 'success',\r\n                                title: 'Sucesso',\r\n                                text: data.message,\r\n                                });\r\n                                } else {\r\n                                    Swal.fire({\r\n                                        icon: 'error',\r\n                                        title: 'Erro',\r\n                                        text: data.message,\r\n                                        });\r\n                                    }\r\n                                    })\r\n                                    .catch(error => {\r\n                                        sendButton.textContent = 'Enviar';\r\n                                        sendButton.disabled = false;\r\n\r\n                                        Swal.fire({\r\n                                            icon: 'error',\r\n                                            title: 'Erro',\r\n                                            text: 'Erro ao enviar notificação.',\r\n                                            });\r\n                                            });\r\n                                            });\r\n    </script>";
        }
    }
});
add_hook("AdminAreaHeaderOutput", 1, function ($vars) {
    $core = new notificazapCore();
    $versao_atual = NZVERSAODOMODULO;
    $version = $core->NOTIFICAZAP_Pega_Versao(NZLINKVERSAO);
    if ((float) $versao_atual < (float) $version) {
        $mensagem = "Existe uma nova atualização do Módulo NOTIFICA ZAP v." . $version . " [<a target=\"_blank\" href=\"" . NZLINKDOWNLOAD . "\">Download</a>] [<a target=\"_blank\" href=\"" . NZARQUIVOLOG . "\">Log</a>]";
        return "<script>\r\n        document.addEventListener('DOMContentLoaded', function () {\r\n            const widgetsContainer = document.querySelector('.home-widgets-container');\r\n            if (widgetsContainer) {\r\n                const customText = document.createElement('div');\r\n                customText.id = 'customAlertMessage'; // Define um ID para fácil manipulação\r\n                customText.innerHTML = '<div class=\"alert alert-primary text-center\" role=\"alert\" style=\"margin-top:10px;margin-bottom:10px; padding:10px\"><b>AVISO!</b><h2> " . $mensagem . "</h2></div>';\r\n                widgetsContainer.insertAdjacentElement('beforebegin', customText);\r\n                setTimeout(() => {\r\n                    const alertMessage = document.getElementById('customAlertMessage');\r\n                    if (alertMessage) {\r\n                        alertMessage.remove();\r\n                    }\r\n                    }, 20000); \r\n                }\r\n                });\r\n        </script>";
    }
});
add_hook("AdminAreaHeadOutput", 1, function ($vars) {
    $cssUrl = "https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css";
    $jsUrl = "https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js";
    return "\r\n    <script>\r\n    (function() {\r\n        // Verifica se o SweetAlert2 já está carregado - NOTIFICAZAP\r\n        if (typeof Swal === 'undefined') {\r\n            // Carrega o CSS somente se não existir\r\n            if (!document.querySelector('link[href=\"" . $cssUrl . "\"]')) {\r\n                const link = document.createElement('link');\r\n                link.rel = 'stylesheet';\r\n                link.href = '" . $cssUrl . "';\r\n                document.head.appendChild(link);\r\n            }\r\n            // Carrega o JS somente se não existir\r\n            if (!document.querySelector('script[src=\"" . $jsUrl . "\"]')) {\r\n                const script = document.createElement('script');\r\n                script.src = '" . $jsUrl . "';\r\n                document.head.appendChild(script);\r\n            }\r\n        }\r\n        })();\r\n    </script>";
});
add_hook("AdminAreaClientSummaryPage", 1, function ($vars) {
    $core = new notificazapCore();
    $onlineServer = $core->NOTIFICAZAP_Servidor_WhatsApp_Online(NZURLSERVIDORWHATSAPP);
    if (!$onlineServer) {
        $core->NOTIFICAZAP_LogWarning("[NOTIFICA ZAP] Servidor do WhatsApp está offline.");
    } else {
        $onlineModulesPay = $core->NOTIFICAZAP_ModulesPay_Online(NZSITEMODULESPAY);
        if ($onlineModulesPay) {
            $license = NOTIFICAZAP_check_license($core->NOTIFICAZAP_Licenca());
            $statusdalicenca = $license["status"];
        } else {
            $core->NOTIFICAZAP_LogWarning("[ADMIN] Servidor da MODULES PAY está offline ou inacessível.");
            $statusdalicenca = "Active";
        }
        if ($statusdalicenca !== "Active") {
            $core->NOTIFICAZAP_LogWarning("[NOTIFICA ZAP] Licença inválida ou expirada.");
        } else {
            return "<br>\r\n    <a href=\"#\" data-toggle=\"modal\" data-target=\"#zapmemessage\" class=\"btn btn-primary btn-sm\" \r\n    title=\"Permite enviar uma mensagem à este cliente em específico\">\r\n    <i class=\"fa fa-arrow-right\" aria-hidden=\"true\"></i> NOTIFICA ZAP - Enviar Mensagem [MANUAL]\r\n    </a>\r\n    <div class=\"modal fade\" id=\"zapmemessage\">\r\n    <div class=\"modal-dialog\">\r\n    <div class=\"modal-content\">\r\n    <div class=\"modal-header\">\r\n    <button type=\"button\" class=\"close\" data-dismiss=\"modal\" aria-label=\"Close\">\r\n    <span aria-hidden=\"true\">×</span>\r\n    </button>\r\n    <h5 class=\"modal-title\">NOTIFICA ZAP - Envio de Mensagem Manual</h5>\r\n    </div>\r\n    <form id=\"formNotificazap\" method=\"post\" class=\"form-horizontal\">\r\n    <input type=\"hidden\" name=\"userid\" value=\"" . $vars["userid"] . "\">\r\n    <div class=\"modal-body\">                       \r\n    <label>Mensagem [Pode ser utilizado Emoji 😀 nas Mensagens]</label>\r\n    <textarea name=\"message\" id=\"mensagem\" rows=\"10\" class=\"form-control\" style=\"resize: none;\" required></textarea>\r\n    <p>Variáveis Disponíveis</p>\r\n    <div class=\"alert alert-info text-center\">\r\n    %name% - Nome do cliente<br>\r\n    %email% - E-mail do cliente<br>\r\n    %dominio% - Domínio do WHMCS\r\n    </div>\r\n    </div>\r\n    <div class=\"modal-footer\">\r\n    <button type=\"button\" class=\"btn btn-default\" data-dismiss=\"modal\">Cancelar</button>\r\n    <button type=\"submit\" id=\"btnEnviar\" class=\"btn btn-success\">Enviar</button>\r\n    </div>\r\n    </form>\r\n    </div>\r\n    </div>\r\n    </div>\r\n    <script type=\"text/javascript\">\r\n    document.getElementById(\"formNotificazap\").addEventListener(\"submit\", function(event) {\r\n        event.preventDefault();\r\n        var form = this;\r\n        var button = document.getElementById(\"btnEnviar\");\r\n        var mensagem = document.getElementById(\"mensagem\").value;\r\n        var userid = form.userid.value;\r\n        button.disabled = true;\r\n        button.innerHTML = '<i class=\"fa fa-spinner fa-spin\"></i> Processando o Envio';\r\n        var xhr = new XMLHttpRequest();\r\n        xhr.open(\"GET\", \"addonmodules.php?module=notificazap&aba=logs&outputaction=manualmessage&userid=\" + userid + \"&message=\" + encodeURIComponent(mensagem), true);           \r\n        xhr.onload = function() {\r\n            if (xhr.status === 200) {\r\n                var response = JSON.parse(xhr.responseText);\r\n                if (response.status === \"success\") {\r\n                // Sucesso - mensagem enviada\r\n                    button.disabled = false;\r\n                    button.innerHTML = \"Enviar\";\r\n                    alert(response.message);\r\n                    \$(\"#zapmemessage\").modal(\"hide\"); // Fecha o modal\r\n                    } else {\r\n                // Erro\r\n                        alert(response.message);\r\n                        button.disabled = false;\r\n                        button.innerHTML = \"Enviar\";\r\n                    }\r\n                }\r\n            };\r\n            xhr.onerror = function() {\r\n        // Erro de rede\r\n                alert(\"Ocorreu um erro na conexão. Tente novamente.\");\r\n                button.disabled = false;\r\n                button.innerHTML = \"Enviar\";\r\n            };\r\n    // Enviar dados\r\n            xhr.send();\r\n            });\r\n            </script>";
        }
    }
});
add_hook("AdminInvoicesControlsOutput", 1, function ($vars) {
    $core = new notificazapCore();
    $onlineServer = $core->NOTIFICAZAP_Servidor_WhatsApp_Online(NZURLSERVIDORWHATSAPP);
    if (!$onlineServer) {
        $core->NOTIFICAZAP_LogWarning("[NOTIFICA ZAP] Servidor do WhatsApp está offline.");
    } else {
        $onlineModulesPay = $core->NOTIFICAZAP_ModulesPay_Online(NZSITEMODULESPAY);
        if ($onlineModulesPay) {
            $license = NOTIFICAZAP_check_license($core->NOTIFICAZAP_Licenca());
            $statusdalicenca = $license["status"];
        } else {
            $core->NOTIFICAZAP_LogWarning("Servidor da MODULES PAY está offline ou inacessível.");
            $statusdalicenca = "Active";
        }
        if ($statusdalicenca !== "Active") {
            $core->NOTIFICAZAP_LogWarning("[NOTIFICA ZAP] Licença inválida ou expirada.");
        } else {
            $whatsapp = new notificaWhatsAppMpay($core->NOTIFICAZAP_Server(), $core->NOTIFICAZAP_Descriptografar($core->NOTIFICAZAP_KEY()), $core->NOTIFICAZAP_Token_Geral(), $core->NOTIFICAZAP_Telefone_Origem());
            $information = $whatsapp->getInformation();
            $conexao = $whatsapp->connectionStatus();
            if ($information["status"] !== 200 || $conexao["instance"]["state"] !== "open") {
                return NULL;
            }
            $invoice = WHMCS\Database\Capsule::table("tblinvoices")->find($vars["invoiceid"]);
            if ((float) $invoice->total == 0) {
                return NULL;
            }
            $urldoWHMCS = $core->NOTIFICAZAP_Pega_Info_tblconfiguration("SystemURL");
            $dataVencimento = strtotime($invoice->duedate);
            $hoje = strtotime(date("Y-m-d"));
            $optionselect = "";
            if ($hoje == $dataVencimento && $invoice->status == "Unpaid") {
                $optionselect .= "<option value=\"faturavencendohoje\">Lembrete de Fatura Vencendo Hoje</option>";
            }
            if ($hoje != $dataVencimento && $dataVencimento < $hoje && $invoice->status == "Unpaid") {
                $optionselect .= "<option value=\"servicealerta\">Lembrete de Fatura Vencida</option>";
            }
            if ($hoje != $dataVencimento && $hoje < $dataVencimento && $invoice->status == "Unpaid") {
                $optionselect .= "<option value=\"invoicereminder\">Lembrete de Fatura a Vencer</option>";
            }
            if ($invoice->status == "Paid") {
                $optionselect .= "<option value=\"invoicepaid\">Confirmação de Pagamento</option>";
            }
            if ($invoice->status == "Unpaid") {
                $optionselect .= "<option value=\"createdinvoice\">Nova Fatura</option>";
            }
            if ($invoice->status == "Cancelled") {
                $optionselect .= "<option value=\"invoicecancelled\">Fatura Cancelada</option>";
            }
            if (WHMCS\Database\Capsule::schema()->hasTable("mod_nfasaasmpay")) {
                $notafiscal = WHMCS\Database\Capsule::table("mod_nfasaasmpay")->where("id_fatura", $invoice->id)->first();
                if ($notafiscal) {
                    $optionselect .= "<option value=\"notafiscalasaas\">Enviar PDF Nota Fiscal Asaas</option>";
                }
            }
            $clientId = $invoice->userid;
            $logs = WHMCS\Database\Capsule::table("mod_notificazap_logs")->where("clientid", $clientId)->where("invoiceid", $invoice->id)->where("type", "sendmessage")->orderBy("created_at", "desc")->get();
            $tabelaLogs = "";
            if ($logs->isNotEmpty()) {
                $tabelaLogs = "<div style=\"margin-top: 15px;\">\r\n        <h4 style=\"margin: 0 0 10px; font-size: 14px; color: #333;\">Histórico de Envio (WhatsApp)</h4>\r\n        <div style=\"overflow-x: auto; position: relative;\">\r\n        <table id=\"notificazap-logs-table\" style=\"width: 100%; border-collapse: collapse; font-size: 13px; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.1);\">\r\n        <thead>\r\n        <tr style=\"background: #f8f9fa; text-align: left;\">\r\n        <th style=\"padding: 8px 10px; border-bottom: 1px solid #dee2e6;\">ID</th>\r\n        <th style=\"padding: 8px 10px; border-bottom: 1px solid #dee2e6;\">Tipo Mensagem</th>\r\n        <th style=\"padding: 8px 10px; border-bottom: 1px solid #dee2e6;\">Data Envio</th>\r\n        </tr>\r\n        </thead>\r\n        <tbody>";
                $popupData = [];
                foreach ($logs as $log) {
                    $dataEnvio = date("d/m/Y H:i:s", strtotime($log->created_at));
                    $mensagemBinaria = $log->message;
                    $mensagemTexto = "Mensagem vazia ou corrompida.";
                    $tipoMensagem = $log->tipo_mensagem;
                    if ($log->tipo_mensagem == "createdinvoice") {
                        $tipoMensagem = "Fatura Criada";
                    }
                    if ($log->tipo_mensagem == "invoicereminder") {
                        $tipoMensagem = "Lembrete de Vencimento";
                    }
                    if ($log->tipo_mensagem == "servicealerta") {
                        $tipoMensagem = "Lembrete Fatura Vencida";
                    }
                    if ($log->tipo_mensagem == "faturavencendohoje") {
                        $tipoMensagem = "Fatura Vencendo Hoje";
                    }
                    if ($log->tipo_mensagem == "invoicepaid") {
                        $tipoMensagem = "Fatura Paga";
                    }
                    if (!empty($mensagemBinaria)) {
                        $unserialized = @unserialize($mensagemBinaria);
                        if ($unserialized !== false) {
                            $mensagemTexto = is_array($unserialized) || is_object($unserialized) ? json_encode($unserialized, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : $unserialized;
                        } else {
                            $json = json_decode($mensagemBinaria, true);
                            if (json_last_error() === JSON_ERROR_NONE) {
                                $mensagemTexto = json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                            } else {
                                $mensagemTexto = is_string($mensagemBinaria) ? $mensagemBinaria : "Dados binários não legíveis";
                            }
                        }
                    }
                    $mensagemLimpa = preg_replace("/^\\s*[\\r\\n]+\\s*/m", "\n", $mensagemTexto);
                    $mensagemLimpa = preg_replace("/\\n{3,}/", "\n\n", $mensagemLimpa);
                    $mensagemLimpa = trim($mensagemLimpa);
                    $mensagemJs = addslashes(htmlspecialchars($mensagemLimpa, ENT_QUOTES, "UTF-8"));
                    $popupData[$log->id] = $mensagemJs;
                    $tabelaLogs .= "<tr style=\"border-bottom: 1px solid #eee;\">\r\n        <td style=\"padding: 8px 10px;\">\r\n        <a href=\"javascript:void(0)\" \r\n        class=\"notificazap-msg-link\" \r\n        data-msg-id=\"" . $log->id . "\" \r\n        style=\"color: #007bff; text-decoration: none; font-weight: 500;\">\r\n        " . $log->id . "\r\n        </a>\r\n        </td>\r\n        <td style=\"padding: 8px 10px; text-align: left;\">" . $tipoMensagem . "</td>\r\n        <td style=\"padding: 8px 10px; text-align: left;\">" . $dataEnvio . "</td>\r\n        </tr>";
                }
                $tabelaLogs .= "    </tbody>\r\n    </table>\r\n    </div>\r\n    </div>";
                $popupDataJson = json_encode($popupData, JSON_UNESCAPED_UNICODE);
                $tabelaLogs .= "<script>\r\n    document.addEventListener(\"DOMContentLoaded\", function() {\r\n        const messages = " . $popupDataJson . ";\r\n        let currentPopup = null;\r\n        let popupTimeout;\r\n\r\n        function showPopup(e, id) {\r\n                // Limpa timeout anterior\r\n            if (popupTimeout) clearTimeout(popupTimeout);\r\n\r\n                // Remove popup anterior\r\n            if (currentPopup && currentPopup.parentNode) {\r\n                currentPopup.remove();\r\n            }\r\n\r\n            const msg = messages[id] || \"Mensagem não disponível.\";\r\n            const popup = document.createElement(\"div\");\r\n            popup.style.cssText = `\r\n            position: absolute; background: white; border: 1px solid #ddd;\r\n            border-radius: 8px; box-shadow: 0 8px 24px rgba(0,0,0,0.15);\r\n            padding: 14px; max-width: 420px; z-index: 9999; font-size: 13px;\r\n            line-height: 1.5; pointer-events: auto; opacity: 0;\r\n            transition: opacity 0.2s ease; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, sans-serif;\r\n            cursor: default;\r\n            `;\r\n\r\n            popup.innerHTML = `\r\n            <div style=\"margin-bottom: 8px; font-weight: 600; color: #2c3e50; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;\">\r\n            Mensagem ID #\${id}\r\n            </div>\r\n            <div style=\"background: #f8f9fa; padding: 10px; border-radius: 6px; max-height: 300px; overflow-y: auto; white-space: pre-wrap; word-wrap: break-word; text-align: left; font-family: monospace; font-size: 12.5px; color: #2d2d2d;\">\r\n            \${msg.replace(/\\n/g, \"<br>\")}\r\n            </div>\r\n            <div style=\"margin-top: 8px; font-size: 11px; color: #7f8c8d; text-align: right;\">\r\n            Clique fora para fechar\r\n            </div>\r\n            `;\r\n\r\n                // Eventos do popup (não fecha ao entrar)\r\n            popup.addEventListener(\"mouseenter\", () => clearTimeout(popupTimeout));\r\n            popup.addEventListener(\"mouseleave\", () => {\r\n                popupTimeout = setTimeout(() => {\r\n                    if (currentPopup === popup) hidePopup();\r\n                    }, 300);\r\n                    });\r\n\r\n                    document.body.appendChild(popup);\r\n                    currentPopup = popup;\r\n\r\n                // Posiciona\r\n                    let x = e.pageX + 15;\r\n                    let y = e.pageY - 10;\r\n                    const rect = popup.getBoundingClientRect();\r\n\r\n                    if (x + rect.width > window.innerWidth) x = e.pageX - rect.width - 15;\r\n                    if (y + rect.height > window.innerHeight) y = e.pageY - rect.height - 15;\r\n\r\n                    popup.style.left = x + \"px\";\r\n                    popup.style.top = y + \"px\";\r\n                    popup.style.opacity = 1;\r\n                }\r\n\r\n                function hidePopup() {\r\n                    if (currentPopup) {\r\n                        currentPopup.style.opacity = 0;\r\n                        setTimeout(() => {\r\n                            if (currentPopup && currentPopup.parentNode) {\r\n                                currentPopup.remove();\r\n                            }\r\n                            currentPopup = null;\r\n                            }, 200);\r\n                        }\r\n                    }\r\n\r\n            // Event delegation\r\n                    let hoverTarget = null;\r\n                    document.addEventListener(\"mouseover\", function(e) {\r\n                        if (e.target && e.target.classList.contains(\"notificazap-msg-link\")) {\r\n                            hoverTarget = e.target;\r\n                            const id = hoverTarget.getAttribute(\"data-msg-id\");\r\n                            showPopup(e, id);\r\n                        }\r\n                        });\r\n\r\n                        document.addEventListener(\"mouseout\", function(e) {\r\n                            if (e.target && e.target.classList.contains(\"notificazap-msg-link\")) {\r\n                                popupTimeout = setTimeout(() => {\r\n                                    if (hoverTarget === e.target) hidePopup();\r\n                                    }, 300);\r\n                                }\r\n                                });\r\n\r\n            // Fecha ao clicar fora\r\n                                document.addEventListener(\"click\", function(e) {\r\n                                    if (currentPopup && !currentPopup.contains(e.target) && !e.target.classList.contains(\"notificazap-msg-link\")) {\r\n                                        hidePopup();\r\n                                    }\r\n                                    });\r\n\r\n            // CSS do link\r\n                                    const style = document.createElement(\"style\");\r\n                                    style.textContent = `\r\n                                    .notificazap-msg-link { cursor: help !important; }\r\n                                    .notificazap-msg-link:hover { text-decoration: underline !important; }\r\n                                    `;\r\n                                    document.head.appendChild(style);\r\n                                    });\r\n                                    </script>";
            }
            return "<div style=\"margin-top: 15px;margin-bottom: 7px\">\r\n                                <label for=\"selectOptionInvoice\" style=\"margin-right: 10px;\">[NOTIFICA ZAP] Selecione uma opção:</label>\r\n                                <select id=\"selectOptionInvoice\" style=\"padding: 5px; border-radius: 5px; border: 1px solid #ccc;\">\r\n                                <option value=\"\">Escolha...</option>\r\n                                " . $optionselect . "\r\n                                </select>\r\n                                <button id=\"sendNotificationInvoice\" style=\"margin-left: 10px; padding: 5px 15px; border: none; border-radius: 5px; background-color: #007bff; color: white; cursor: pointer;\">\r\n                                Enviar\r\n                                </button>\r\n                                </div>\r\n                                " . $tabelaLogs . "\r\n                                <script>\r\n                                const sendButton2 = document.getElementById('sendNotificationInvoice');\r\n                                const selectOption2 = document.getElementById('selectOptionInvoice');\r\n    // Adiciona evento ao botão \"Enviar\"\r\n                                sendButton2.addEventListener('click', (event) => {\r\n                                    event.preventDefault();\r\n                                    const selectedValue = selectOption2.value;\r\n                                    const idservice = \"" . $invoice->id . "\";\r\n                                    const urldominio = \"" . $urldoWHMCS . "\";\r\n        // Verificar se uma opção foi selecionada\r\n                                    if (!selectedValue) {\r\n                                        Swal.fire({\r\n                                            icon: 'warning',\r\n                                            title: 'Atenção',\r\n                                            text: 'Por favor, selecione uma opção antes de enviar!',\r\n                                            });\r\n                                            return;\r\n                                        }\r\n        // Desabilitar o botão enquanto envia\r\n                                        sendButton2.textContent = 'Enviando...';\r\n                                        sendButton2.disabled = true;\r\n        // Enviar os dados via AJAX\r\n                                        fetch(urldominio + 'modules/addons/notificazap/enviar.notificacao.php', {\r\n                                            method: 'POST',\r\n                                            headers: {\r\n                                                'Content-Type': 'application/x-www-form-urlencoded',\r\n                                                },\r\n                                                body: 'template=' + selectedValue + '&idinvoice=' + idservice,\r\n                                                })\r\n                                                .then(response => response.json())\r\n                                                .then(data => {\r\n                                                    sendButton2.textContent = 'Enviar';\r\n                                                    sendButton2.disabled = false;\r\n                                                    if (data.status === 'success') {\r\n                                                        Swal.fire({\r\n                                                            icon: 'success',\r\n                                                            title: 'Sucesso',\r\n                                                            text: data.message,\r\n                                                            });\r\n                                                            } else {\r\n                                                                Swal.fire({\r\n                                                                    icon: 'error',\r\n                                                                    title: 'Erro',\r\n                                                                    html: data.message,\r\n                                                                    });\r\n                                                                }\r\n                                                                })\r\n                                                                .catch(error => {\r\n                                                                    sendButton2.textContent = 'Enviar';\r\n                                                                    sendButton2.disabled = false;\r\n                                                                    Swal.fire({\r\n                                                                        icon: 'error',\r\n                                                                        title: 'Erro',\r\n                                                                        text: 'SISTEMA: Erro ao enviar notificação.',\r\n                                                                        });\r\n                                                                        });\r\n                                                                        });\r\n   </script>";
        }
    }
});
add_hook("DailyCronJob", 1, function ($vars) {
    $diaAtual = date("d");
    $mesAtual = date("m");
    $anoAtual = date("Y");
    $core = new notificazapCore();
    $onlineServer = $core->NOTIFICAZAP_Servidor_WhatsApp_Online(NZURLSERVIDORWHATSAPP);
    if (!$onlineServer) {
        $core->NOTIFICAZAP_LogWarning("[NOTIFICA ZAP] Servidor do WhatsApp está offline.");
    } else {
        $onlineModulesPay = $core->NOTIFICAZAP_ModulesPay_Online(NZSITEMODULESPAY);
        if ($onlineModulesPay) {
            $license = NOTIFICAZAP_check_license($core->NOTIFICAZAP_Licenca());
            $statusdalicenca = $license["status"];
        } else {
            $core->NOTIFICAZAP_LogWarning("Servidor da MODULES PAY está offline ou inacessível.");
            $statusdalicenca = "Active";
        }
        if ($statusdalicenca !== "Active") {
            $core->NOTIFICAZAP_LogWarning("[NOTIFICA ZAP] Licença inválida ou expirada.");
        } else {
            $whatsapp = new notificaWhatsAppMpay($core->NOTIFICAZAP_Server(), $core->NOTIFICAZAP_Descriptografar($core->NOTIFICAZAP_KEY()), $core->NOTIFICAZAP_Token_Geral(), $core->NOTIFICAZAP_Telefone_Origem());
            $information = $whatsapp->getInformation();
            $Conexao = $whatsapp->connectionStatus();
            if ($information["status"] != 200 || $Conexao["instance"]["state"] != "open") {
                $core->NOTIFICAZAP_LogError("Conexão com a API WhatsApp não está ativa ou está inacessível", ["status" => $information["status"], "state" => $Conexao["instance"]["state"], "datetime" => date("Y-m-d H:i:s"), "action" => "daily_cron_birthday_check"]);
            } else if ($information["status"] == 200 && $Conexao["instance"]["state"] == "open") {
                $obtem_Template = $core->NOTIFICAZAP_Pegar_Status_Mensagem_Template("happybirthday");
                $corpo_mensagem = $obtem_Template["corpo_mensagem"];
                $status_mensagem = $obtem_Template["status_mensagem"];
                $id_campo_receber_notificacao = $core->NOTIFICAZAP_Pega_Info_tbladdonmodules("addon_notificazap_receber_whatsapp");
                $id_campo_data_nascimento = $core->NOTIFICAZAP_Pega_Info_tbladdonmodules("addon_notificazap_data_nascimento");
                if (empty($id_campo_receber_notificacao) || empty($id_campo_data_nascimento)) {
                    $core->NOTIFICAZAP_LogError("Campos personalizados não configurados", ["receber_notificacao_field" => $id_campo_receber_notificacao, "data_nascimento_field" => $id_campo_data_nascimento, "action" => "birthday_check"]);
                    return NULL;
                }
                $enviar_status = $core->NOTIFICAZAP_Pega_Info_tbladdonmodules("addon_notificazap_data_nascimento_enviar");
                if ($enviar_status == "Clientes Ativo") {
                    $enviar_para = "Active";
                } else if ($enviar_status == "Clientes Inativos") {
                    $enviar_para = "Inactive";
                } else {
                    $enviar_para = "";
                }
                $DOMINIO_WHMCS = $core->NOTIFICAZAP_Pega_Info_tblconfiguration("SystemURL");
                if ($status_mensagem == "active" && $id_campo_data_nascimento != NULL) {
                    foreach (WHMCS\Database\Capsule::table("tblclients")->get() as $tblclients) {
                        $userid = $tblclients->id;
                        $phonenumber = $tblclients->phonenumber;
                        $firstname = $tblclients->firstname;
                        $status = $tblclients->status;
                        if (empty($enviar_para) || $enviar_para == $status) {
                            $birthdate = "";
                            $textomsg = $corpo_mensagem;
                            $receber_notificacao = $core->NOTIFICAZAP_Pega_Info_tblcustomfieldsvalues($id_campo_receber_notificacao, $userid);
                            $birthdate = $core->NOTIFICAZAP_Pega_Info_tblcustomfieldsvalues($id_campo_data_nascimento, $userid);
                            if (empty($birthdate) || $receber_notificacao !== "Sim") {
                            } else {
                                $birthdate = trim($birthdate);
                                $clean = preg_replace("/[^0-9]/", "", $birthdate);
                                if (strlen($clean) !== 8) {
                                    $core->NOTIFICAZAP_LogWarning("Data de nascimento inválida ou com formato incorreto (deve ter 8 dígitos)", ["original" => $birthdate, "cleaned" => $clean, "length" => strlen($clean), "user_id" => $userid, "action" => "birthday_check"]);
                                } else {
                                    $dia = substr($clean, 0, 2);
                                    $mes = substr($clean, 2, 2);
                                    $ano = substr($clean, 4, 4);
                                    if (!checkdate((int) $mes, (int) $dia, (int) $ano)) {
                                        $core->NOTIFICAZAP_LogWarning("Data de nascimento inválida (não existe no calendário)", ["dia" => $dia, "mes" => $mes, "ano" => $ano, "original" => $birthdate, "user_id" => $userid, "action" => "birthday_check"]);
                                    } else if ($dia === $diaAtual && $mes === $mesAtual) {
                                        $idade = (int) $anoAtual - (int) $ano;
                                        $textomsg = str_replace("%name%", $firstname, $textomsg);
                                        $textomsg = str_replace("%idade%", $idade, $textomsg);
                                        $textomsg = str_replace("%assinatura%", $core->NOTIFICAZAP_Assinatura_WhatsApp(), $textomsg);
                                        $textomsg = str_replace("%dominiowhmcs%", $DOMINIO_WHMCS, $textomsg);
                                        $client = WHMCS\User\Client::find($userid);
                                        if (!$client) {
                                        } else {
                                            $phone = $core->NOTIFICAZAP_AdjustNumber($client);
                                            $phone_whatsapp = $whatsapp->Valida_Number_WhatsApp($phone);
                                            $existsPhone = $phone_whatsapp[0]["exists"] ?? NULL;
                                            if (!$existsPhone) {
                                                $core->NOTIFICAZAP_CadastrarLogFalhas($client->id, "Telefone " . $phone . " do Cliente não é um número de WhatsApp");
                                            } else {
                                                $retorno = $core->NOTIFICAZAP_EnviarMensagem($phone, $textomsg);
                                                if ($retorno["status"] === "success") {
                                                    $core->NOTIFICAZAP_CadastrarLogWhatsapp($client->id, $textomsg, "sendmessage", "happybirthday");
                                                    $core->NOTIFICAZAP_LogInfo("NOTIFICAÇÃO enviada com sucesso para o telefone: [" . $phone . "] Cliente ID: " . $client->id);
                                                } else {
                                                    $core->NOTIFICAZAP_LogWarning("Mensagem de WhatsApp não foi enviada", ["phone" => $phone, "cliente" => json_encode($client), "hook" => "mensagem de aniversário", "retorno" => json_encode($retorno)]);
                                                    $core->NOTIFICAZAP_CadastrarLogFalhas($client->id, "Falha ao Enviar Mensagem de Aniversário - Retorno: " . json_encode($retorno));
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
});
add_hook("QuoteStatusChange", 1, function ($vars) {
    try {
        $quoteid = $vars["quoteid"];
        $core = new notificazapCore();
        $onlineServer = $core->NOTIFICAZAP_Servidor_WhatsApp_Online(NZURLSERVIDORWHATSAPP);
        if (!$onlineServer) {
            $core->NOTIFICAZAP_LogWarning("[NOTIFICA ZAP] Servidor do WhatsApp está offline.");
            return NULL;
        }
        $onlineModulesPay = $core->NOTIFICAZAP_ModulesPay_Online(NZSITEMODULESPAY);
        if ($onlineModulesPay) {
            $license = NOTIFICAZAP_check_license($core->NOTIFICAZAP_Licenca());
            $statusdalicenca = $license["status"];
        } else {
            $core->NOTIFICAZAP_LogWarning("Servidor da MODULES PAY está offline ou inacessível.");
            $statusdalicenca = "Active";
        }
        if ($statusdalicenca !== "Active") {
            $core->NOTIFICAZAP_LogWarning("[NOTIFICA ZAP] Licença inválida ou expirada.");
            return NULL;
        }
        $whatsapp = new notificaWhatsAppMpay($core->NOTIFICAZAP_Server(), $core->NOTIFICAZAP_Descriptografar($core->NOTIFICAZAP_KEY()), $core->NOTIFICAZAP_Token_Geral(), $core->NOTIFICAZAP_Telefone_Origem());
        $information = $whatsapp->getInformation();
        $conexao = $whatsapp->connectionStatus();
        if ($information["status"] !== 200 || $conexao["instance"]["state"] !== "open") {
            $core->NOTIFICAZAP_LogWarning("[NOTIFICA ZAP] Conexão instável - Status: " . ($information["status"] ?? "N/A") . " | Estado: " . ($conexao["instance"]["state"] ?? "N/A"));
            return NULL;
        }
        $template = $core->NOTIFICAZAP_Pegar_Status_Mensagem_Template("alterarstatuscotacao");
        if ($template["status_mensagem"] !== "active" || empty($template["corpo_mensagem"])) {
            $core->NOTIFICAZAP_LogWarning("[NOTIFICA ZAP] Template de mensagem desativado ou vazio");
            return NULL;
        }
        $quote = WHMCS\Database\Capsule::table("tblquotes")->where("id", $quoteid)->first();
        if (!$quote) {
            $core->NOTIFICAZAP_LogWarning("[NOTIFICA ZAP] Cotação não encontrada: ID# " . ($quoteid ?? "N/A"));
            return NULL;
        }
        if ($quote->stage === "Draft" || $quote->stage === "On Hold" || $quote->stage === "Lost" || $quote->stage === "Dead") {
            return NULL;
        }
        if ($quote->stage == "Accepted") {
            $status = "Aceito";
        }
        if ($quote->stage == "Delivered") {
            $status = "Entregue";
        }
        if ($quote->userid == 0) {
            $iddousuario = $quote->userid;
            $primeiroNome = $quote->firstname;
            $phone = $core->NOTIFICAZAP_AdjustNumberUnico($quote->phonenumber);
            if (empty($phone)) {
                $core->NOTIFICAZAP_LogWarning("[NOTIFICA ZAP] Número de telefone inválido: " . ($quote->phonenumber ?? "N/A"));
                return NULL;
            }
        } else {
            $client = WHMCS\User\Client::find($quote->userid);
            if (!$client || empty($client->phonenumber)) {
                $core->NOTIFICAZAP_LogWarning("[NOTIFICA ZAP] Cliente não encontrado para cotação ID# : " . $quoteid);
                return NULL;
            }
            $iddousuario = $client->id;
            $primeiroNome = $client->firstname;
            $phone = $core->NOTIFICAZAP_AdjustNumber($client);
            if (empty($phone)) {
                $core->NOTIFICAZAP_LogWarning("[NOTIFICA ZAP] Número de telefone inválido: " . ($client->phonenumber ?? "N/A"));
                return NULL;
            }
        }
        if ($iddousuario != 0) {
            $campoRecebimento = $core->NOTIFICAZAP_Pega_Info_tbladdonmodules("addon_notificazap_receber_whatsapp");
            $receberNotificacao = $core->NOTIFICAZAP_Pega_Info_tblcustomfieldsvalues($campoRecebimento, $iddousuario);
            if ($receberNotificacao !== "Sim") {
                $core->NOTIFICAZAP_LogWarning("[NOTIFICA ZAP] Cliente optou por não receber notificações: " . $iddousuario);
                return NULL;
            }
        }
        $dominio = $core->NOTIFICAZAP_Pega_Info_tblconfiguration("SystemURL");
        if ($iddousuario != 0 && WHMCS\Database\Capsule::table("tbladdonmodules")->where("module", "autologinmpay")->exists()) {
            $linkAutologin = $core->NOTIFICAZAP_generateAutologinLink($iddousuario, $quoteid, "cotacao");
        }
        $corpoMensagem = str_replace(["%name%", "%assinatura%", "%idcotacao%", "%statuscotacao%", "%dominiowhmcs%"], [$primeiroNome, $core->NOTIFICAZAP_Assinatura_WhatsApp(), $quoteid, $status, isset($linkAutologin) ? $linkAutologin : $dominio], $template["corpo_mensagem"]);
        $phone_whatsapp = $whatsapp->Valida_Number_WhatsApp($phone);
        $existsPhone = $phone_whatsapp[0]["exists"] ?? NULL;
        if (!$existsPhone) {
            $core->NOTIFICAZAP_LogWarning("[NOTIFICA ZAP] Número não é WhatsApp: " . $phone . " do cliente ID# " . $iddousuario);
            $core->NOTIFICAZAP_CadastrarLogFalhas($iddousuario, "Telefone " . $phone . " do Cliente não é um número de WhatsApp");
            return NULL;
        }
        $retorno = $core->NOTIFICAZAP_EnviarMensagem($phone, $corpoMensagem);
        if (isset($retorno["status"]) && $retorno["status"] == "success") {
            $core->NOTIFICAZAP_CadastrarLogWhatsapp($iddousuario, $corpoMensagem, "sendmessage", "alterarstatuscotacao");
            $core->NOTIFICAZAP_LogInfo("[NOTIFICA ZAP] Notificação enviada com sucesso para: " . $phone);
        } else {
            $core->NOTIFICAZAP_LogWarning("Mensagem de WhatsApp não foi enviada", ["phone" => $phone, "cliente" => json_encode($client), "hook" => "cotação trocar status", "dados do retorno" => json_encode($retorno)]);
            $core->NOTIFICAZAP_CadastrarLogFalhas($client->id, "Falha ao Enviar Mensagem de Alteração de Status de Cotação - Retorno: " . json_encode($retorno));
        }
    } catch (Exception $e) {
        $core->NOTIFICAZAP_LogWarning("[NOTIFICA ZAP] Erro crítico: " . $e->getMessage() . " na linha " . $e->getLine());
    }
});
add_hook("QuoteCreated", 1, function ($vars) {
    $quote = WHMCS\Database\Capsule::table("tblquotes")->where("id", "=", $vars["quoteid"])->first();
    if (!$quote) {
        return NULL;
    }
    if ($quote->stage === "Draft" || $quote->stage === "On Hold" || $quote->stage === "Lost" || $quote->stage === "Dead") {
        return NULL;
    }
    $core = new notificazapCore();
    $onlineServer = $core->NOTIFICAZAP_Servidor_WhatsApp_Online(NZURLSERVIDORWHATSAPP);
    if (!$onlineServer) {
        $core->NOTIFICAZAP_LogWarning("[NOTIFICA ZAP] Servidor do WhatsApp está offline.");
    } else {
        $onlineModulesPay = $core->NOTIFICAZAP_ModulesPay_Online(NZSITEMODULESPAY);
        if ($onlineModulesPay) {
            $license = NOTIFICAZAP_check_license($core->NOTIFICAZAP_Licenca());
            $statusdalicenca = $license["status"];
        } else {
            $core->NOTIFICAZAP_LogWarning("Servidor da MODULES PAY está offline ou inacessível.");
            $statusdalicenca = "Active";
        }
        if ($statusdalicenca !== "Active") {
            $core->NOTIFICAZAP_LogWarning("[NOTIFICA ZAP] Licença inválida ou expirada.");
        } else {
            $whatsapp = new notificaWhatsAppMpay($core->NOTIFICAZAP_Server(), $core->NOTIFICAZAP_Descriptografar($core->NOTIFICAZAP_KEY()), $core->NOTIFICAZAP_Token_Geral(), $core->NOTIFICAZAP_Telefone_Origem());
            $information = $whatsapp->getInformation();
            $conexao = $whatsapp->connectionStatus();
            if ($information["status"] !== 200 || $conexao["instance"]["state"] !== "open") {
                return NULL;
            }
            $template = $core->NOTIFICAZAP_Pegar_Status_Mensagem_Template("enviarcotacao");
            if ($template["status_mensagem"] !== "active") {
                return NULL;
            }
            $status = "";
            if ($quote->stage == "Accepted") {
                $status = "Aceito";
            }
            if ($quote->stage == "Delivered") {
                $status = "Entregue";
            }
            if ($quote->userid == 0) {
                $iddousuario = $quote->userid;
                $primeiroNome = $quote->firstname;
                $phone = $core->NOTIFICAZAP_AdjustNumberUnico($quote->phonenumber);
                if (empty($phone)) {
                    $core->NOTIFICAZAP_LogWarning("[NOTIFICA ZAP] Número de telefone inválido: " . ($quote->phonenumber ?? "N/A"));
                    return NULL;
                }
            } else {
                $client = WHMCS\User\Client::find($quote->userid);
                if (!$client || empty($client->phonenumber)) {
                    $core->NOTIFICAZAP_LogWarning("[NOTIFICA ZAP] Cliente não encontrado para cotação ID# : " . $vars["quoteid"]);
                    return NULL;
                }
                $iddousuario = $client->id;
                $primeiroNome = $client->firstname;
                $phone = $core->NOTIFICAZAP_AdjustNumber($client);
                if (empty($phone)) {
                    $core->NOTIFICAZAP_LogWarning("[NOTIFICA ZAP] Número de telefone inválido: " . ($client->phonenumber ?? "N/A"));
                    return NULL;
                }
            }
            if ($iddousuario != 0) {
                $campoRecebimento = $core->NOTIFICAZAP_Pega_Info_tbladdonmodules("addon_notificazap_receber_whatsapp");
                $receberNotificacao = $core->NOTIFICAZAP_Pega_Info_tblcustomfieldsvalues($campoRecebimento, $iddousuario);
                if ($receberNotificacao !== "Sim") {
                    $core->NOTIFICAZAP_LogWarning("[NOTIFICA ZAP] Cliente optou por não receber notificações: " . $iddousuario);
                    return NULL;
                }
            }
            $dominioWHMCS = $core->NOTIFICAZAP_Pega_Info_tblconfiguration("SystemURL");
            $linkAutologin = NULL;
            if ($iddousuario != 0 && WHMCS\Database\Capsule::table("tbladdonmodules")->where("module", "autologinmpay")->exists()) {
                $linkAutologin = $core->NOTIFICAZAP_generateAutologinLink($iddousuario, $vars["quoteid"], "cotacao");
            }
            $corpoMensagem = str_replace(["%name%", "%assinatura%", "%idcotacao%", "%statuscotacao%", "%dominiowhmcs%"], [$primeiroNome, $core->NOTIFICAZAP_Assinatura_WhatsApp(), $vars["quoteid"], $status, $linkAutologin ?? $dominioWHMCS], $template["corpo_mensagem"]);
            $phone_whatsapp = $whatsapp->Valida_Number_WhatsApp($phone);
            $existsPhone = $phone_whatsapp[0]["exists"] ?? NULL;
            if (!$existsPhone) {
                $core->NOTIFICAZAP_CadastrarLogFalhas($iddousuario, "Telefone " . $phone . " do Cliente não é um número de WhatsApp");
                $core->NOTIFICAZAP_LogWarning("[NOTIFICA ZAP] O número do Telefone [" . $phone . "] Não é um número de WhatsApp");
            } else {
                $retorno = $core->NOTIFICAZAP_EnviarMensagem($phone, $corpoMensagem);
                if (isset($retorno["status"]) && $retorno["status"] == "success") {
                    $core->NOTIFICAZAP_CadastrarLogWhatsapp($iddousuario, $corpoMensagem, "sendmessage", "enviarcotacao");
                    $core->NOTIFICAZAP_LogInfo("Notificação enviada com sucesso para o telefone: [" . $phone . "] Cliente #ID " . $iddousuario);
                } else {
                    $core->NOTIFICAZAP_LogWarning("Mensagem de WhatsApp não foi enviada", ["phone" => $phone, "cliente" => json_encode($client), "hook" => "pagina de login", "dados do retorno" => json_encode($retorno)]);
                    $core->NOTIFICAZAP_CadastrarLogFalhas($client->id, "Falha ao Enviar Mensagem de Alteração de Status de Cotação - Phone " . $phone . " Retorno: " . json_encode($retorno));
                }
            }
        }
    }
});
add_hook("AcceptQuote", 1, function ($vars) {
    $quote = WHMCS\Database\Capsule::table("tblquotes")->where("id", "=", $vars["quoteid"])->first();
    if (!$quote) {
        return NULL;
    }
    if ($quote->stage === "Draft" || $quote->stage === "On Hold" || $quote->stage === "Lost" || $quote->stage === "Dead") {
        return NULL;
    }
    $core = new notificazapCore();
    $onlineServer = $core->NOTIFICAZAP_Servidor_WhatsApp_Online(NZURLSERVIDORWHATSAPP);
    if (!$onlineServer) {
        $core->NOTIFICAZAP_LogWarning("Servidor do WhatsApp está offline.");
    } else {
        $onlineModulesPay = $core->NOTIFICAZAP_ModulesPay_Online(NZSITEMODULESPAY);
        if ($onlineModulesPay) {
            $license = NOTIFICAZAP_check_license($core->NOTIFICAZAP_Licenca());
            $statusdalicenca = $license["status"];
        } else {
            $core->NOTIFICAZAP_LogWarning("Servidor da MODULES PAY está offline ou inacessível.");
            $statusdalicenca = "Active";
        }
        if ($statusdalicenca !== "Active") {
            $core->NOTIFICAZAP_LogWarning("[NOTIFICA ZAP] Licença inválida ou expirada.");
        } else {
            $whatsapp = new notificaWhatsAppMpay($core->NOTIFICAZAP_Server(), $core->NOTIFICAZAP_Descriptografar($core->NOTIFICAZAP_KEY()), $core->NOTIFICAZAP_Token_Geral(), $core->NOTIFICAZAP_Telefone_Origem());
            if ($whatsapp->getInformation()["status"] !== 200 || $whatsapp->connectionStatus()["instance"]["state"] !== "open") {
                return NULL;
            }
            $template = $core->NOTIFICAZAP_Pegar_Status_Mensagem_Template("cotacaoaceita");
            if ($template["status_mensagem"] !== "active") {
                return NULL;
            }
            $status = "";
            if ($quote->stage == "Accepted") {
                $status = "Aceito";
            }
            if ($quote->stage == "Delivered") {
                $status = "Entregue";
            }
            if ($quote->userid == 0) {
                $iddousuario = $quote->userid;
                $primeiroNome = $quote->firstname;
                $phone = $core->NOTIFICAZAP_AdjustNumberUnico($quote->phonenumber);
                if (empty($phone)) {
                    $core->NOTIFICAZAP_LogWarning("[NOTIFICA ZAP] Número de telefone inválido: " . ($quote->phonenumber ?? "N/A"), [], $iddousuario ?? NULL);
                    return NULL;
                }
            } else {
                $client = WHMCS\User\Client::find($quote->userid);
                if (!$client || empty($client->phonenumber)) {
                    $core->NOTIFICAZAP_LogWarning("[NOTIFICA ZAP] Cliente não encontrado para cotação ID# : " . $vars["quoteid"]);
                    return NULL;
                }
                $iddousuario = $client->id;
                $primeiroNome = $client->firstname;
                $phone = $core->NOTIFICAZAP_AdjustNumber($client);
                if (empty($phone)) {
                    $core->NOTIFICAZAP_LogWarning("[NOTIFICA ZAP] Número de telefone inválido: " . ($client->phonenumber ?? "N/A"));
                    return NULL;
                }
            }
            if ($iddousuario != 0) {
                $campoRecebimento = $core->NOTIFICAZAP_Pega_Info_tbladdonmodules("addon_notificazap_receber_whatsapp");
                $receberNotificacao = $core->NOTIFICAZAP_Pega_Info_tblcustomfieldsvalues($campoRecebimento, $iddousuario);
                if ($receberNotificacao !== "Sim") {
                    $core->NOTIFICAZAP_LogWarning("[NOTIFICA ZAP] Cliente optou por não receber notificações: " . $iddousuario);
                    return NULL;
                }
            }
            $dominioWHMCS = $core->NOTIFICAZAP_Pega_Info_tblconfiguration("SystemURL");
            $linkAutologin = NULL;
            if ($iddousuario != 0 && WHMCS\Database\Capsule::table("tbladdonmodules")->where("module", "autologinmpay")->exists()) {
                $linkAutologin = $core->NOTIFICAZAP_generateAutologinLink($iddousuario, $vars["quoteid"], "cotacao");
            }
            $corpoMensagem = str_replace(["%name%", "%assinatura%", "%idcotacao%", "%statuscotacao%", "%dominiowhmcs%"], [$primeiroNome, $core->NOTIFICAZAP_Assinatura_WhatsApp(), $vars["quoteid"], $status, $linkAutologin ?? $dominioWHMCS], $template["corpo_mensagem"]);
            $phone_whatsapp = $whatsapp->Valida_Number_WhatsApp($phone);
            $existsPhone = $phone_whatsapp[0]["exists"] ?? NULL;
            if (!$existsPhone) {
                $core->NOTIFICAZAP_CadastrarLogFalhas($iddousuario, "Telefone " . $phone . " do Cliente não é um número de WhatsApp");
                $core->NOTIFICAZAP_LogWarning("[NOTIFICA ZAP] O número do Telefone [" . $phone . "] Não é um número de WhatsApp");
            } else {
                $retorno = $core->NOTIFICAZAP_EnviarMensagem($phone, $corpoMensagem);
                if (isset($retorno["status"]) && $retorno["status"] == "success") {
                    $core->NOTIFICAZAP_CadastrarLogWhatsapp($iddousuario, $corpoMensagem, "sendmessage", "cotacaoaceita");
                    $core->NOTIFICAZAP_LogInfo("Notificação enviada com sucesso para o telefone: [" . $phone . "] Client ID# " . $iddousuario);
                } else {
                    $core->NOTIFICAZAP_LogWarning("Mensagem de WhatsApp não foi enviada", ["phone" => $phone, "cliente" => json_encode($client), "hook" => "cotação aceita", "dados do retorno" => json_encode($retorno)]);
                    $core->NOTIFICAZAP_CadastrarLogFalhas($iddousuario, "Falha ao Enviar Mensagem de Criação de Cotação - Phone " . $phone . " Retorno: " . json_encode($retorno));
                }
            }
        }
    }
});
add_hook("AfterModuleUnsuspend", 1, function ($vars) {
    $core = new notificazapCore();
    $onlineServer = $core->NOTIFICAZAP_Servidor_WhatsApp_Online(NZURLSERVIDORWHATSAPP);
    if (!$onlineServer) {
        $core->NOTIFICAZAP_LogWarning("Servidor do WhatsApp está offline.");
    } else {
        $onlineModulesPay = $core->NOTIFICAZAP_ModulesPay_Online(NZSITEMODULESPAY);
        if ($onlineModulesPay) {
            $license = NOTIFICAZAP_check_license($core->NOTIFICAZAP_Licenca());
            $statusdalicenca = $license["status"];
        } else {
            $core->NOTIFICAZAP_LogWarning("Servidor da MODULES PAY está offline ou inacessível.");
            $statusdalicenca = "Active";
        }
        if ($statusdalicenca !== "Active") {
            $core->NOTIFICAZAP_LogWarning("[NOTIFICA ZAP] Licença inválida ou expirada.");
        } else {
            $whatsapp = new notificaWhatsAppMpay($core->NOTIFICAZAP_Server(), $core->NOTIFICAZAP_Descriptografar($core->NOTIFICAZAP_KEY()), $core->NOTIFICAZAP_Token_Geral(), $core->NOTIFICAZAP_Telefone_Origem());
            $information = $whatsapp->getInformation();
            $conexao = $whatsapp->connectionStatus();
            if ($information["status"] !== 200 || $conexao["instance"]["state"] !== "open") {
                return NULL;
            }
            $template = $core->NOTIFICAZAP_Pegar_Status_Mensagem_Template("servicereactived");
            if ($template["status_mensagem"] !== "active") {
                return NULL;
            }
            $DOMINIO_WHMCS = $core->NOTIFICAZAP_Pega_Info_tblconfiguration("SystemURL");
            $service = WHMCS\Database\Capsule::table("tblhosting")->find($vars["params"]["serviceid"]);
            $product = WHMCS\Database\Capsule::table("tblproducts")->find($vars["params"]["pid"]);
            $client = WHMCS\User\Client::find($service->userid);
            $receber_notificacao = $core->NOTIFICAZAP_Pega_Info_tblcustomfieldsvalues($core->NOTIFICAZAP_Pega_Info_tbladdonmodules("addon_notificazap_receber_whatsapp"), $service->userid);
            if ($receber_notificacao != "Sim") {
                return NULL;
            }
            $linkautologin = $DOMINIO_WHMCS;
            if (WHMCS\Database\Capsule::table("tbladdonmodules")->where("module", "autologinmpay")->exists()) {
                $linkautologin = $core->NOTIFICAZAP_generateAutologinLink($client->id, $service->userid, "painel");
            }
            $corpo_mensagem = str_replace(["%name%", "%assinatura%", "%product%", "%domain%", "%dominiowhmcs%"], [$client->firstname, $core->NOTIFICAZAP_Assinatura_WhatsApp(), $product->name, $service->domain, $linkautologin], $template["corpo_mensagem"]);
            $phone = $core->NOTIFICAZAP_AdjustNumber($client);
            $phone_whatsapp = $whatsapp->Valida_Number_WhatsApp($phone);
            $existsPhone = $phone_whatsapp[0]["exists"] ?? NULL;
            if (!$existsPhone) {
                $core->NOTIFICAZAP_CadastrarLogFalhas($client->id, "Telefone " . $phone . " do Cliente não é um número de WhatsApp");
                $core->NOTIFICAZAP_LogWarning("[NOTIFICA ZAP] O número do Telefone [" . $phone . "] Não é um número de WhatsApp");
            } else {
                $retorno = $core->NOTIFICAZAP_EnviarMensagem($phone, $corpo_mensagem);
                if (isset($retorno["status"]) && $retorno["status"] == "success") {
                    $core->NOTIFICAZAP_CadastrarLogWhatsapp($client->id, $corpo_mensagem, "sendmessage", "servicereactived");
                    $core->NOTIFICAZAP_LogInfo("Notificação enviada com sucesso para o telefone: [" . $phone . "] Cliente ID# " . $client->id);
                } else {
                    $core->NOTIFICAZAP_LogWarning("Mensagem de WhatsApp não foi enviada", ["phone" => $phone, "cliente" => json_encode($client), "hook" => "modulo remover suspensão de serviço", "dados do retorno" => json_encode($retorno)]);
                    $core->NOTIFICAZAP_CadastrarLogFalhas($client->id, "Falha ao Enviar Mensagem de Remover Suspensão de Serviço - Phone " . $phone . " Retorno: " . json_encode($retorno));
                }
            }
        }
    }
});
add_hook("AfterModuleTerminate", 1, function ($vars) {
    $core = new notificazapCore();
    $onlineServer = $core->NOTIFICAZAP_Servidor_WhatsApp_Online(NZURLSERVIDORWHATSAPP);
    if (!$onlineServer) {
        $core->NOTIFICAZAP_LogWarning("Servidor do WhatsApp está offline.");
    } else {
        $onlineModulesPay = $core->NOTIFICAZAP_ModulesPay_Online(NZSITEMODULESPAY);
        if ($onlineModulesPay) {
            $license = NOTIFICAZAP_check_license($core->NOTIFICAZAP_Licenca());
            $statusdalicenca = $license["status"];
        } else {
            $core->NOTIFICAZAP_LogWarning("Servidor da MODULES PAY está offline ou inacessível.");
            $statusdalicenca = "Active";
        }
        if ($statusdalicenca !== "Active") {
            $core->NOTIFICAZAP_LogWarning("[NOTIFICA ZAP] Licença inválida ou expirada.");
        } else {
            $whatsapp = new notificaWhatsAppMpay($core->NOTIFICAZAP_Server(), $core->NOTIFICAZAP_Descriptografar($core->NOTIFICAZAP_KEY()), $core->NOTIFICAZAP_Token_Geral(), $core->NOTIFICAZAP_Telefone_Origem());
            $information = $whatsapp->getInformation();
            $conexao = $whatsapp->connectionStatus();
            if ($information["status"] !== 200 || $conexao["instance"]["state"] !== "open") {
                return NULL;
            }
            $template = $core->NOTIFICAZAP_Pegar_Status_Mensagem_Template("servicecancelled");
            if ($template["status_mensagem"] !== "active") {
                return NULL;
            }
            $DOMINIO_WHMCS = $core->NOTIFICAZAP_Pega_Info_tblconfiguration("SystemURL");
            $service = WHMCS\Database\Capsule::table("tblhosting")->find($vars["params"]["serviceid"]);
            $product = WHMCS\Database\Capsule::table("tblproducts")->find($vars["params"]["pid"]);
            $client = WHMCS\User\Client::find($service->userid);
            $receber_notificacao = $core->NOTIFICAZAP_Pega_Info_tblcustomfieldsvalues($core->NOTIFICAZAP_Pega_Info_tbladdonmodules("addon_notificazap_receber_whatsapp"), $service->userid);
            if ($receber_notificacao != "Sim") {
                return NULL;
            }
            $linkautologin = $DOMINIO_WHMCS . "clientarea.php";
            if (WHMCS\Database\Capsule::table("tbladdonmodules")->where("module", "autologinmpay")->exists()) {
                $linkautologin = $core->NOTIFICAZAP_generateAutologinLink($client->id, $service->userid, "painel");
            }
            $corpo_mensagem = str_replace(["%name%", "%assinatura%", "%product%", "%domain%", "%dominiowhmcs%"], [$client->firstname, $core->NOTIFICAZAP_Assinatura_WhatsApp(), $product->name, $service->domain, $linkautologin], $template["corpo_mensagem"]);
            $phone = $core->NOTIFICAZAP_AdjustNumber($client);
            $phone_whatsapp = $whatsapp->Valida_Number_WhatsApp($phone);
            $existsPhone = $phone_whatsapp[0]["exists"] ?? NULL;
            if (!$existsPhone) {
                $core->NOTIFICAZAP_CadastrarLogFalhas($client->id, "Telefone " . $phone . " do Cliente não é um número de WhatsApp");
                $core->NOTIFICAZAP_LogWarning("[NOTIFICA ZAP] O número do Telefone [" . $phone . "] Não é um número de WhatsApp");
            } else {
                $retorno = $core->NOTIFICAZAP_EnviarMensagem($phone, $corpo_mensagem);
                if (isset($retorno["status"]) && $retorno["status"] == "success") {
                    $core->NOTIFICAZAP_CadastrarLogWhatsapp($client->id, $corpo_mensagem, "sendmessage", "servicecancelled");
                    $core->NOTIFICAZAP_LogInfo("Notificação enviada com sucesso para o telefone: [" . $phone . "] Cliente ID# " . $client->id);
                } else {
                    $core->NOTIFICAZAP_LogWarning("Mensagem de WhatsApp não foi enviada", ["phone" => $phone, "cliente" => json_encode($client), "hook" => "modulo encerrar serviço", "dados do retorno" => json_encode($retorno)]);
                    $core->NOTIFICAZAP_CadastrarLogFalhas($client->id, "Falha ao Enviar Mensagem de Encerramento de Serviço - Phone " . $phone . " Retorno: " . json_encode($retorno));
                }
            }
        }
    }
});
add_hook("UserChangePassword", 1, function ($vars) {
    $core = new notificazapCore();
    $onlineServer = $core->NOTIFICAZAP_Servidor_WhatsApp_Online(NZURLSERVIDORWHATSAPP);
    if (!$onlineServer) {
        $core->NOTIFICAZAP_LogWarning("Servidor do WhatsApp está offline.");
    } else {
        $onlineModulesPay = $core->NOTIFICAZAP_ModulesPay_Online(NZSITEMODULESPAY);
        if ($onlineModulesPay) {
            $license = NOTIFICAZAP_check_license($core->NOTIFICAZAP_Licenca());
            $statusdalicenca = $license["status"];
        } else {
            $core->NOTIFICAZAP_LogWarning("Servidor da MODULES PAY está offline ou inacessível.");
            $statusdalicenca = "Active";
        }
        if ($statusdalicenca !== "Active") {
            $core->NOTIFICAZAP_LogWarning("[NOTIFICA ZAP] Licença inválida ou expirada.");
        } else {
            $whatsapp = new notificaWhatsAppMpay($core->NOTIFICAZAP_Server(), $core->NOTIFICAZAP_Descriptografar($core->NOTIFICAZAP_KEY()), $core->NOTIFICAZAP_Token_Geral(), $core->NOTIFICAZAP_Telefone_Origem());
            if ($whatsapp->getInformation()["status"] !== 200 || $whatsapp->connectionStatus()["instance"]["state"] !== "open") {
                return NULL;
            }
            $template = $core->NOTIFICAZAP_Pegar_Status_Mensagem_Template("clientchangepwd");
            if ($template["status_mensagem"] !== "active") {
                return NULL;
            }
            $client = WHMCS\User\Client::find($vars["userid"]);
            if ($client->status != "Active") {
                return NULL;
            }
            $receber_notificacao = $core->NOTIFICAZAP_Pega_Info_tblcustomfieldsvalues($core->NOTIFICAZAP_Pega_Info_tbladdonmodules("addon_notificazap_receber_whatsapp"), $vars["userid"]);
            if ($receber_notificacao != "Sim") {
                return NULL;
            }
            $DOMINIO_WHMCS = $core->NOTIFICAZAP_Pega_Info_tblconfiguration("SystemURL");
            $linkautologin = $DOMINIO_WHMCS . "clientarea.php";
            if (WHMCS\Database\Capsule::table("tbladdonmodules")->where("module", "autologinmpay")->exists()) {
                $linkautologin = $core->NOTIFICAZAP_generateAutologinLink($client->id, $vars["userid"], "painel");
            }
            $ipaddr = getenv("REMOTE_ADDR");
            $corpo_mensagem = str_replace(["%name%", "%email%", "%assinatura%", "%ipaddr%", "%date%", "%hour%", "%dominiowhmcs%"], [$client->firstname, $client->email, $core->NOTIFICAZAP_Assinatura_WhatsApp(), $ipaddr, date("d/m/Y"), date("H:i"), $linkautologin], $template["corpo_mensagem"]);
            $phone = $core->NOTIFICAZAP_AdjustNumber($client);
            $phone_whatsapp = $whatsapp->Valida_Number_WhatsApp($phone);
            $existsPhone = $phone_whatsapp[0]["exists"] ?? NULL;
            if (!$existsPhone) {
                $core->NOTIFICAZAP_CadastrarLogFalhas($client->id, "Telefone " . $phone . " do Cliente não é um número de WhatsApp");
                $core->NOTIFICAZAP_LogWarning("[NOTIFICA ZAP] O número do Telefone [" . $phone . "] Não é um número de WhatsApp");
            } else {
                $retorno = $core->NOTIFICAZAP_EnviarMensagem($phone, $corpo_mensagem);
                if (isset($retorno["status"]) && $retorno["status"] == "success") {
                    $core->NOTIFICAZAP_CadastrarLogWhatsapp($client->id, $corpo_mensagem, "sendmessage", "clientchangepwd");
                    $core->NOTIFICAZAP_LogInfo("Notificação enviada com sucesso para o telefone: [" . $phone . "] Cliente ID# " . $client->id);
                } else {
                    $core->NOTIFICAZAP_LogWarning("Mensagem de WhatsApp não foi enviada", ["phone" => $phone, "cliente" => json_encode($client), "hook" => "modulo trocar senha", "dados do retorno" => json_encode($retorno)]);
                    $core->NOTIFICAZAP_CadastrarLogFalhas($client->id, "Falha ao Enviar Mensagem de Trocar Senha de Cliente - Phone " . $phone . " Retorno: " . json_encode($retorno));
                }
            }
        }
    }
});
add_hook("AfterModuleCreate", 1, function ($vars) {
    $core = new notificazapCore();
    $onlineServer = $core->NOTIFICAZAP_Servidor_WhatsApp_Online(NZURLSERVIDORWHATSAPP);
    if (!$onlineServer) {
        $core->NOTIFICAZAP_LogWarning("Servidor do WhatsApp está offline.");
    } else {
        $onlineModulesPay = $core->NOTIFICAZAP_ModulesPay_Online(NZSITEMODULESPAY);
        if ($onlineModulesPay) {
            $license = NOTIFICAZAP_check_license($core->NOTIFICAZAP_Licenca());
            $statusdalicenca = $license["status"];
        } else {
            $core->NOTIFICAZAP_LogWarning("Servidor da MODULES PAY está offline ou inacessível.");
            $statusdalicenca = "Active";
        }
        if ($statusdalicenca !== "Active") {
            $core->NOTIFICAZAP_LogWarning("[NOTIFICA ZAP] Licença inválida ou expirada.");
        } else {
            $whatsapp = new notificaWhatsAppMpay($core->NOTIFICAZAP_Server(), $core->NOTIFICAZAP_Descriptografar($core->NOTIFICAZAP_KEY()), $core->NOTIFICAZAP_Token_Geral(), $core->NOTIFICAZAP_Telefone_Origem());
            if ($whatsapp->getInformation()["status"] !== 200 || $whatsapp->connectionStatus()["instance"]["state"] !== "open") {
                return NULL;
            }
            $template = $core->NOTIFICAZAP_Pegar_Status_Mensagem_Template("serviceready");
            if ($template["status_mensagem"] !== "active") {
                $core->NOTIFICAZAP_LogWarning("[NOTIFICA ZAP] Templatge servicecreated não está ativo.");
            } else {
                $DOMINIO_WHMCS = $core->NOTIFICAZAP_Pega_Info_tblconfiguration("SystemURL");
                $service = WHMCS\Database\Capsule::table("tblhosting")->where("id", $vars["params"]["serviceid"])->first();
                $product = WHMCS\Database\Capsule::table("tblproducts")->where("id", $vars["params"]["pid"])->first();
                $client = WHMCS\User\Client::find($vars["params"]["userid"]);
                if (!$client) {
                    $core->NOTIFICAZAP_LogWarning("[NOTIFICA ZAP] Hook [AfterModuleCreate] Dados do Cliente #" . $vars["params"]["userid"] . " não foi possível validar.");
                } else {
                    $id_campo_notificacao = $core->NOTIFICAZAP_Pega_Info_tbladdonmodules("addon_notificazap_receber_whatsapp");
                    if ($core->NOTIFICAZAP_Pega_Info_tblcustomfieldsvalues($id_campo_notificacao, $service->userid) !== "Sim") {
                        $core->NOTIFICAZAP_LogWarning("[NOTIFICA ZAP] O Cliente ID" . $vars["params"]["userid"] . " Não deseja receber mensagem de WhatsApp.");
                    } else {
                        $linkautologin = $DOMINIO_WHMCS . "clientarea.php";
                        if (WHMCS\Database\Capsule::table("tbladdonmodules")->where("module", "autologinmpay")->exists()) {
                            $linkautologin = $core->NOTIFICAZAP_generateAutologinLink($client->id, $service->userid, "painel");
                        }
                        $corpo_mensagem = str_replace(["%name%", "%email%", "%assinatura%", "%product%", "%ip%", "%duedate%", "%value%", "%ns1%", "%ns2%", "%domain%", "%username%", "%password%", "%dominiowhmcs%"], [$client->firstname, $client->email, $core->NOTIFICAZAP_Assinatura_WhatsApp(), $product->name, $service->dedicatedip, $core->NOTIFICAZAP_AdjustDate($service->nextduedate), $core->NOTIFICAZAP_AdjustValue($service->amount), $service->ns1, $service->ns2, $service->domain, $service->username, decrypt($service->password), $linkautologin], $template["corpo_mensagem"]);
                        $phone = $core->NOTIFICAZAP_AdjustNumber($client);
                        $phone_whatsapp = $whatsapp->Valida_Number_WhatsApp($phone);
                        $existsPhone = $phone_whatsapp[0]["exists"] ?? NULL;
                        if (!$existsPhone) {
                            $core->NOTIFICAZAP_CadastrarLogFalhas($client->id, "Telefone " . $phone . " do Cliente não é um número de WhatsApp");
                            $core->NOTIFICAZAP_LogWarning("[NOTIFICA ZAP] O número do Telefone [" . $phone . "] Não é um número de WhatsApp");
                        } else {
                            $retorno = $core->NOTIFICAZAP_EnviarMensagem($phone, $corpo_mensagem);
                            if (isset($retorno["status"]) && $retorno["status"] == "success") {
                                $core->NOTIFICAZAP_CadastrarLogWhatsapp($client->id, $corpo_mensagem, "sendmessage", "servicecreated");
                                $core->NOTIFICAZAP_LogInfo("Notificação enviada com sucesso para o telefone: [" . $phone . "] Cliente  ID# " . $client->id);
                            } else {
                                $core->NOTIFICAZAP_LogWarning("Mensagem de WhatsApp não foi enviada", ["phone" => $phone, "cliente" => json_encode($client), "hook" => "módulo criar serviço", "dados do retorno" => json_encode($retorno)]);
                                $core->NOTIFICAZAP_CadastrarLogFalhas($client->id, "Falha ao Enviar Mensagem de Novo Serviço - Phone " . $phone . " Retorno: " . json_encode($retorno));
                            }
                        }
                    }
                }
            }
        }
    }
});
add_hook("AfterModuleSuspend", 999, function ($vars) {
    $core = new notificazapCore();
    $onlineServer = $core->NOTIFICAZAP_Servidor_WhatsApp_Online(NZURLSERVIDORWHATSAPP);
    if (!$onlineServer) {
        $core->NOTIFICAZAP_LogWarning("Servidor do WhatsApp está offline.");
    } else {
        $onlineModulesPay = $core->NOTIFICAZAP_ModulesPay_Online(NZSITEMODULESPAY);
        if ($onlineModulesPay) {
            $license = NOTIFICAZAP_check_license($core->NOTIFICAZAP_Licenca());
            $statusdalicenca = $license["status"];
        } else {
            $core->NOTIFICAZAP_LogWarning("Servidor da MODULES PAY está offline ou inacessível.");
            $statusdalicenca = "Active";
        }
        if ($statusdalicenca !== "Active") {
            $core->NOTIFICAZAP_LogWarning("[NOTIFICA ZAP] Licença inválida ou expirada.");
        } else {
            $whatsapp = new notificaWhatsAppMpay($core->NOTIFICAZAP_Server(), $core->NOTIFICAZAP_Descriptografar($core->NOTIFICAZAP_KEY()), $core->NOTIFICAZAP_Token_Geral(), $core->NOTIFICAZAP_Telefone_Origem());
            if ($whatsapp->getInformation()["status"] !== 200 || $whatsapp->connectionStatus()["instance"]["state"] !== "open") {
                return NULL;
            }
            $template = $core->NOTIFICAZAP_Pegar_Status_Mensagem_Template("servicesuspended");
            if ($template["status_mensagem"] !== "active") {
                return NULL;
            }
            $DOMINIO_WHMCS = $core->NOTIFICAZAP_Pega_Info_tblconfiguration("SystemURL");
            $service = WHMCS\Database\Capsule::table("tblhosting")->where("id", $vars["params"]["serviceid"])->first();
            $product = WHMCS\Database\Capsule::table("tblproducts")->where("id", $vars["params"]["pid"])->first();
            $client = WHMCS\User\Client::find($service->userid);
            $id_campo_notificacao = $core->NOTIFICAZAP_Pega_Info_tbladdonmodules("addon_notificazap_receber_whatsapp");
            if ($core->NOTIFICAZAP_Pega_Info_tblcustomfieldsvalues($id_campo_notificacao, $service->userid) !== "Sim") {
                return NULL;
            }
            $linkautologin = $DOMINIO_WHMCS . "clientarea.php";
            if (WHMCS\Database\Capsule::table("tbladdonmodules")->where("module", "autologinmpay")->exists()) {
                $linkautologin = $core->NOTIFICAZAP_generateAutologinLink($client->id, $service->userid, "painel");
            }
            $corpo_mensagem = str_replace(["%name%", "%assinatura%", "%product%", "%domain%", "%dominiowhmcs%"], [$client->firstname, $core->NOTIFICAZAP_Assinatura_WhatsApp(), $product->name, $service->domain, $linkautologin], $template["corpo_mensagem"]);
            $phone = $core->NOTIFICAZAP_AdjustNumber($client);
            $phone_whatsapp = $whatsapp->Valida_Number_WhatsApp($phone);
            $existsPhone = $phone_whatsapp[0]["exists"] ?? NULL;
            if (!$existsPhone) {
                $core->NOTIFICAZAP_CadastrarLogFalhas($client->id, "Telefone " . $phone . " do Cliente não é um número de WhatsApp");
                $core->NOTIFICAZAP_LogWarning("[NOTIFICA ZAP] O número do Telefone [" . $phone . "] Não é um número de WhatsApp");
            } else {
                $retorno = $core->NOTIFICAZAP_EnviarMensagem($phone, $corpo_mensagem);
                if (isset($retorno["status"]) && $retorno["status"] == "success") {
                    $core->NOTIFICAZAP_CadastrarLogWhatsapp($client->id, $corpo_mensagem, "sendmessage", "servicesuspended");
                    $core->NOTIFICAZAP_LogInfo("Notificação enviada com sucesso para o telefone: [" . $phone . "] Cliente ID# " . $client->id);
                } else {
                    $core->NOTIFICAZAP_LogWarning("Mensagem de WhatsApp não foi enviada", ["phone" => $phone, "cliente" => json_encode($client), "hook" => "serviço suspenso", "dados do retorno" => json_encode($retorno)]);
                    $core->NOTIFICAZAP_CadastrarLogFalhas($client->id, "Falha ao Enviar Mensagem de Serviço Suspenso - Phone " . $phone . " Retorno: " . json_encode($retorno));
                }
            }
        }
    }
});
add_hook("ClientLogin", 1, function ($vars) {
    $core = new notificazapCore();
    $onlineServer = $core->NOTIFICAZAP_Servidor_WhatsApp_Online(NZURLSERVIDORWHATSAPP);
    if (!$onlineServer) {
        $core->NOTIFICAZAP_LogWarning("Servidor do WhatsApp está offline.");
    } else {
        $onlineModulesPay = $core->NOTIFICAZAP_ModulesPay_Online(NZSITEMODULESPAY);
        if ($onlineModulesPay) {
            $license = NOTIFICAZAP_check_license($core->NOTIFICAZAP_Licenca());
            $statusdalicenca = $license["status"];
        } else {
            $core->NOTIFICAZAP_LogWarning("Servidor da MODULES PAY está offline ou inacessível.");
            $statusdalicenca = "Active";
        }
        if ($statusdalicenca !== "Active") {
            $core->NOTIFICAZAP_LogWarning("[NOTIFICA ZAP] Licença inválida ou expirada.");
        } else {
            $whatsapp = new notificaWhatsAppMpay($core->NOTIFICAZAP_Server(), $core->NOTIFICAZAP_Descriptografar($core->NOTIFICAZAP_KEY()), $core->NOTIFICAZAP_Token_Geral(), $core->NOTIFICAZAP_Telefone_Origem());
            if ($whatsapp->getInformation()["status"] !== 200 || $whatsapp->connectionStatus()["instance"]["state"] !== "open") {
                return NULL;
            }
            $template = $core->NOTIFICAZAP_Pegar_Status_Mensagem_Template("clientlogin");
            if ($template["status_mensagem"] !== "active") {
                return NULL;
            }
            $DOMINIO_WHMCS = $core->NOTIFICAZAP_Pega_Info_tblconfiguration("SystemURL");
            $client = WHMCS\User\Client::find($vars["userid"]);
            $id_campo_notificacao = $core->NOTIFICAZAP_Pega_Info_tbladdonmodules("addon_notificazap_receber_whatsapp");
            if ($core->NOTIFICAZAP_Pega_Info_tblcustomfieldsvalues($id_campo_notificacao, $vars["userid"]) !== "Sim") {
                return NULL;
            }
            $linkautologin = $DOMINIO_WHMCS . "clientarea.php";
            if (WHMCS\Database\Capsule::table("tbladdonmodules")->where("module", "autologinmpay")->exists()) {
                $linkautologin = $core->NOTIFICAZAP_generateAutologinLink($client->id, $vars["userid"], "painel");
            }
            $corpo_mensagem = str_replace(["%name%", "%assinatura%", "%ipaddr%", "%date%", "%hour%", "%dominiowhmcs%"], [$client->firstname, $core->NOTIFICAZAP_Assinatura_WhatsApp(), getenv("REMOTE_ADDR"), date("d/m/Y"), date("H:i"), $linkautologin], $template["corpo_mensagem"]);
            $phone = $core->NOTIFICAZAP_AdjustNumber($client);
            $phone_whatsapp = $whatsapp->Valida_Number_WhatsApp($phone);
            $existsPhone = $phone_whatsapp[0]["exists"] ?? NULL;
            if (!$existsPhone) {
                $core->NOTIFICAZAP_CadastrarLogFalhas($client->id, "Telefone " . $phone . " do Cliente não é um número de WhatsApp");
                $core->NOTIFICAZAP_LogWarning("[NOTIFICA ZAP] O número do Telefone [" . $phone . "] Não é um número de WhatsApp");
            } else {
                $retorno = $core->NOTIFICAZAP_EnviarMensagem($phone, $corpo_mensagem);
                if (isset($retorno["status"]) && $retorno["status"] == "success") {
                    $core->NOTIFICAZAP_CadastrarLogWhatsapp($client->id, $corpo_mensagem, "sendmessage", "clientlogin");
                    $core->NOTIFICAZAP_LogInfo("Notificação enviada com sucesso para o telefone: [" . $phone . "] Cliente ID# " . $client->id);
                } else {
                    $core->NOTIFICAZAP_LogWarning("Mensagem de WhatsApp não foi enviada", ["phone" => $phone, "cliente" => json_encode($client), "hook" => "pagina de login", "dados do retorno" => json_encode($retorno)]);
                    $core->NOTIFICAZAP_CadastrarLogFalhas($client->id, "Falha ao Enviar Mensagem de Login de Cliente - Phone " . $phone . " Retorno: " . json_encode($retorno));
                }
            }
        }
    }
});
add_hook("ClientAreaPageLogin", 1, function ($vars) {
    $core = new notificazapCore();
    $onlineServer = $core->NOTIFICAZAP_Servidor_WhatsApp_Online(NZURLSERVIDORWHATSAPP);
    if (!$onlineServer) {
        $core->NOTIFICAZAP_LogWarning("Servidor do WhatsApp está offline.");
    } else {
        $onlineModulesPay = $core->NOTIFICAZAP_ModulesPay_Online(NZSITEMODULESPAY);
        if ($onlineModulesPay) {
            $license = NOTIFICAZAP_check_license($core->NOTIFICAZAP_Licenca());
            $statusdalicenca = $license["status"];
        } else {
            $core->NOTIFICAZAP_LogWarning("Servidor da MODULES PAY está offline ou inacessível.");
            $statusdalicenca = "Active";
        }
        if ($statusdalicenca !== "Active") {
            $core->NOTIFICAZAP_LogWarning("[NOTIFICA ZAP] Licença inválida ou expirada.");
        } else {
            $whatsapp = new notificaWhatsAppMpay($core->NOTIFICAZAP_Server(), $core->NOTIFICAZAP_Descriptografar($core->NOTIFICAZAP_KEY()), $core->NOTIFICAZAP_Token_Geral(), $core->NOTIFICAZAP_Telefone_Origem());
            if ($whatsapp->getInformation()["status"] !== 200 || $whatsapp->connectionStatus()["instance"]["state"] !== "open") {
                return NULL;
            }
            $template = $core->NOTIFICAZAP_Pegar_Status_Mensagem_Template("failedaccess");
            if ($template["status_mensagem"] !== "active") {
                return NULL;
            }
            $log = WHMCS\Database\Capsule::table("tblactivitylog")->orderBy("date", "desc")->first();
            $id_campo_notificacao = $core->NOTIFICAZAP_Pega_Info_tbladdonmodules("addon_notificazap_receber_whatsapp");
            if ($core->NOTIFICAZAP_Pega_Info_tblcustomfieldsvalues($id_campo_notificacao, $log->userid) !== "Sim") {
                return NULL;
            }
            if ($log->user === "System") {
                $text = explode(" ", $log->description);
                if ($text[0] === "Failed" && $text[1] === "Login" && $text[2] === "Attempt") {
                    $logdate = date("Y-m-d", strtotime($log->date));
                    $loghour = date("H:i", strtotime($log->date));
                    if ($logdate === date("Y-m-d") && $loghour === date("H:i")) {
                        $linkautologin = $DOMINIO_WHMCS . "clientarea.php";
                        if (WHMCS\Database\Capsule::table("tbladdonmodules")->where("module", "autologinmpay")->exists()) {
                            $linkautologin = $core->NOTIFICAZAP_generateAutologinLink($log->userid, $log->userid, "painel");
                        }
                        $client = WHMCS\User\Client::find($log->userid);
                        $corpo_mensagem = str_replace(["%name%", "%email%", "%assinatura%", "%dominiowhmcs%", "%ipaddr%", "%date%", "%hour%"], [$client->firstname, $client->email, $core->NOTIFICAZAP_Assinatura_WhatsApp(), $linkautologin, $log->ipaddr, $core->NOTIFICAZAP_AdjustDate($log->date), date("H:i", strtotime($log->date))], $template["corpo_mensagem"]);
                        $phone = $core->NOTIFICAZAP_AdjustNumber($client);
                        $phone_whatsapp = $whatsapp->Valida_Number_WhatsApp($phone);
                        $existsPhone = $phone_whatsapp[0]["exists"] ?? NULL;
                        if (!$existsPhone) {
                            $core->NOTIFICAZAP_CadastrarLogFalhas($client->id, "Telefone " . $phone . " do Cliente não é um número de WhatsApp");
                            $core->NOTIFICAZAP_LogWarning("[NOTIFICA ZAP] O número do Telefone [" . $phone . "] Não é um número de WhatsApp");
                            return NULL;
                        }
                        $retorno = $core->NOTIFICAZAP_EnviarMensagem($phone, $corpo_mensagem);
                        if (isset($retorno["status"]) && $retorno["status"] == "success") {
                            $core->NOTIFICAZAP_CadastrarLogWhatsapp($client->id, $corpo_mensagem, "sendmessage", "failedaccess");
                            $core->NOTIFICAZAP_LogInfo("Notificação enviada com sucesso para o telefone: [" . $phone . "] Cliente ID# " . $client->id);
                        } else {
                            $core->NOTIFICAZAP_LogWarning("Mensagem de WhatsApp não foi enviada", ["phone" => $phone, "cliente" => json_encode($client), "hook" => "falha no acesso de login", "dados do retorno" => json_encode($retorno)]);
                            $core->NOTIFICAZAP_CadastrarLogFalhas($client->id, "Falha ao Enviar Mensagem de Login de Cliente - Phone " . $phone . " Retorno: " . json_encode($retorno));
                        }
                    }
                }
            }
        }
    }
});
add_hook("TicketOpen", 1, function ($vars) {
    $core = new notificazapCore();
    $onlineServer = $core->NOTIFICAZAP_Servidor_WhatsApp_Online(NZURLSERVIDORWHATSAPP);
    if (!$onlineServer) {
        $core->NOTIFICAZAP_LogWarning("Servidor do WhatsApp está offline.");
    } else {
        $onlineModulesPay = $core->NOTIFICAZAP_ModulesPay_Online(NZSITEMODULESPAY);
        if ($onlineModulesPay) {
            $license = NOTIFICAZAP_check_license($core->NOTIFICAZAP_Licenca());
            $statusdalicenca = $license["status"];
        } else {
            $core->NOTIFICAZAP_LogWarning("Servidor da MODULES PAY está offline ou inacessível.");
            $statusdalicenca = "Active";
        }
        if ($statusdalicenca !== "Active") {
            $core->NOTIFICAZAP_LogWarning("[NOTIFICA ZAP] Licença inválida ou expirada.");
        } else {
            $whatsapp = new notificaWhatsAppMpay($core->NOTIFICAZAP_Server(), $core->NOTIFICAZAP_Descriptografar($core->NOTIFICAZAP_KEY()), $core->NOTIFICAZAP_Token_Geral(), $core->NOTIFICAZAP_Telefone_Origem());
            if ($whatsapp->getInformation()["status"] !== 200 || $whatsapp->connectionStatus()["instance"]["state"] !== "open") {
                return NULL;
            }
            $template = $core->NOTIFICAZAP_Pegar_Status_Mensagem_Template("ticketcreated");
            if ($template["status_mensagem"] !== "active") {
                return NULL;
            }
            $id_campo_notificacao = $core->NOTIFICAZAP_Pega_Info_tbladdonmodules("addon_notificazap_receber_whatsapp");
            if ($core->NOTIFICAZAP_Pega_Info_tblcustomfieldsvalues($id_campo_notificacao, $vars["userid"]) !== "Sim") {
                return NULL;
            }
            $client = WHMCS\User\Client::find($vars["userid"]);
            $ticket = WHMCS\Database\Capsule::table("tbltickets")->where("id", $vars["ticketid"])->first();
            if (!$client || !$ticket) {
                return NULL;
            }
            $linkautologin = $DOMINIO_WHMCS . "clientarea.php";
            if (WHMCS\Database\Capsule::table("tbladdonmodules")->where("module", "autologinmpay")->exists()) {
                $linkautologin = $core->NOTIFICAZAP_generateAutologinLink($ticket->userid, $ticket->id, "ticket");
            }
            $corpo_mensagem = str_replace(["%name%", "%assinatura%", "%ticket%", "%date%", "%hour%", "%dominiowhmcs%"], [$client->firstname, $core->NOTIFICAZAP_Assinatura_WhatsApp(), $ticket->tid, $core->NOTIFICAZAP_AdjustDate($ticket->date), date("H:i", strtotime($ticket->date)), $linkautologin ?? $core->NOTIFICAZAP_Pega_Info_tblconfiguration("SystemURL") . "viewticket.php?tid=" . $ticket->tid . "&c=" . $ticket->c], $template["corpo_mensagem"]);
            $phone = $core->NOTIFICAZAP_AdjustNumber($client);
            $phone_whatsapp = $whatsapp->Valida_Number_WhatsApp($phone);
            $existsPhone = $phone_whatsapp[0]["exists"] ?? NULL;
            if (!$existsPhone) {
                $core->NOTIFICAZAP_CadastrarLogFalhas($client->id, "Telefone " . $phone . " do Cliente não é um número de WhatsApp");
                $core->NOTIFICAZAP_LogWarning("[NOTIFICA ZAP] O número do Telefone [" . $phone . "] Não é um número de WhatsApp");
            } else {
                $retorno = $core->NOTIFICAZAP_EnviarMensagem($phone, $corpo_mensagem);
                if (isset($retorno["status"]) && $retorno["status"] == "success") {
                    $core->NOTIFICAZAP_CadastrarLogWhatsapp($client->id, $corpo_mensagem, "sendmessage", "ticketcreated");
                    $core->NOTIFICAZAP_LogInfo("Notificação enviada com sucesso para o telefone: [" . $phone . "] Cliente ID# " . $client->id);
                } else {
                    $core->NOTIFICAZAP_LogWarning("Mensagem de WhatsApp não foi enviada", ["phone" => $phone, "cliente" => json_encode($client), "hook" => "ticket aberto", "ticket" => json_encode($ticket), "dados do retorno" => json_encode($retorno)]);
                    $core->NOTIFICAZAP_CadastrarLogFalhas($client->id, "Falha ao Enviar Mensagem de Ticket Aberto - Phone " . $phone . " Retorno: " . json_encode($retorno));
                }
            }
        }
    }
});
add_hook("TicketAdminReply", 999, function ($vars) {
    $core = new notificazapCore();
    $onlineServer = $core->NOTIFICAZAP_Servidor_WhatsApp_Online(NZURLSERVIDORWHATSAPP);
    if (!$onlineServer) {
        $core->NOTIFICAZAP_LogWarning("Servidor do WhatsApp está offline.");
    } else {
        $onlineModulesPay = $core->NOTIFICAZAP_ModulesPay_Online(NZSITEMODULESPAY);
        if ($onlineModulesPay) {
            $license = NOTIFICAZAP_check_license($core->NOTIFICAZAP_Licenca());
            $statusdalicenca = $license["status"];
        } else {
            $core->NOTIFICAZAP_LogWarning("Servidor da MODULES PAY está offline ou inacessível.");
            $statusdalicenca = "Active";
        }
        if ($statusdalicenca !== "Active") {
            $core->NOTIFICAZAP_LogWarning("[NOTIFICA ZAP] Licença inválida ou expirada.");
        } else {
            $whatsapp = new notificaWhatsAppMpay($core->NOTIFICAZAP_Server(), $core->NOTIFICAZAP_Descriptografar($core->NOTIFICAZAP_KEY()), $core->NOTIFICAZAP_Token_Geral(), $core->NOTIFICAZAP_Telefone_Origem());
            if ($whatsapp->getInformation()["status"] !== 200 || $whatsapp->connectionStatus()["instance"]["state"] !== "open") {
                return NULL;
            }
            $template = $core->NOTIFICAZAP_Pegar_Status_Mensagem_Template("ticketreplied");
            if ($template["status_mensagem"] !== "active") {
                return NULL;
            }
            $ticket = WHMCS\Database\Capsule::table("tbltickets")->where("id", $vars["ticketid"])->first();
            $client = WHMCS\User\Client::find($ticket->userid);
            if (!$client || !$ticket) {
                return NULL;
            }
            $id_campo_notificacao = $core->NOTIFICAZAP_Pega_Info_tbladdonmodules("addon_notificazap_receber_whatsapp");
            if ($core->NOTIFICAZAP_Pega_Info_tblcustomfieldsvalues($id_campo_notificacao, $ticket->userid) !== "Sim") {
                return NULL;
            }
            $linkautologin = $DOMINIO_WHMCS . "clientarea.php";
            if (WHMCS\Database\Capsule::table("tbladdonmodules")->where("module", "autologinmpay")->exists()) {
                $linkautologin = $core->NOTIFICAZAP_generateAutologinLink($ticket->userid, $ticket->id, "ticket");
            }
            $corpo_mensagem = str_replace(["%name%", "%assinatura%", "%ticket%", "%date%", "%hour%", "%dominiowhmcs%"], [$client->firstname, $core->NOTIFICAZAP_Assinatura_WhatsApp(), $ticket->tid, $core->NOTIFICAZAP_AdjustDate($ticket->date), date("H:i", strtotime($ticket->date)), $linkautologin ?? $core->NOTIFICAZAP_Pega_Info_tblconfiguration("SystemURL") . "viewticket.php?tid=" . $ticket->tid . "&c=" . $ticket->c], $template["corpo_mensagem"]);
            $phone = $core->NOTIFICAZAP_AdjustNumber($client);
            $phone_whatsapp = $whatsapp->Valida_Number_WhatsApp($phone);
            $existsPhone = $phone_whatsapp[0]["exists"] ?? NULL;
            if (!$existsPhone) {
                $core->NOTIFICAZAP_CadastrarLogFalhas($client->id, "Telefone " . $phone . " do Cliente não é um número de WhatsApp");
                $core->NOTIFICAZAP_LogWarning("[NOTIFICA ZAP] O número do Telefone [" . $phone . "] Não é um número de WhatsApp");
            } else {
                $retorno = $core->NOTIFICAZAP_EnviarMensagem($phone, $corpo_mensagem);
                if (isset($retorno["status"]) && $retorno["status"] == "success") {
                    $core->NOTIFICAZAP_CadastrarLogWhatsapp($client->id, $corpo_mensagem, "sendmessage", "ticketreplied");
                    $core->NOTIFICAZAP_LogInfo("Notificação enviada com sucesso para o telefone: [" . $phone . "] Cliente ID# " . $client->id);
                } else {
                    $core->NOTIFICAZAP_LogWarning("Mensagem de WhatsApp não foi enviada", ["phone" => $phone, "cliente" => json_encode($client), "hook" => "ticket resposta do admin", "ticket" => json_encode($ticket), "dados do retorno" => json_encode($retorno)]);
                    $core->NOTIFICAZAP_CadastrarLogFalhas($client->id, "Falha ao Enviar Mensagem de Resposta de Ticket pelo Admin - Phone " . $phone . " Retorno: " . json_encode($retorno));
                }
            }
        }
    }
});
add_hook("TicketClose", 999, function ($vars) {
    $WHMCS_URLSISTEMA = rtrim(App::getSystemUrl(), "/");
    $core = new notificazapCore();
    $onlineServer = $core->NOTIFICAZAP_Servidor_WhatsApp_Online(NZURLSERVIDORWHATSAPP);
    if (!$onlineServer) {
        $core->NOTIFICAZAP_LogWarning("Servidor do WhatsApp está offline.");
    } else {
        $onlineModulesPay = $core->NOTIFICAZAP_ModulesPay_Online(NZSITEMODULESPAY);
        if ($onlineModulesPay) {
            $license = NOTIFICAZAP_check_license($core->NOTIFICAZAP_Licenca());
            $statusdalicenca = $license["status"];
        } else {
            $core->NOTIFICAZAP_LogWarning("Servidor da MODULES PAY está offline ou inacessível.");
            $statusdalicenca = "Active";
        }
        if ($statusdalicenca !== "Active") {
            $core->NOTIFICAZAP_LogWarning("[NOTIFICA ZAP] Licença inválida ou expirada.");
        } else {
            $whatsapp = new notificaWhatsAppMpay($core->NOTIFICAZAP_Server(), $core->NOTIFICAZAP_Descriptografar($core->NOTIFICAZAP_KEY()), $core->NOTIFICAZAP_Token_Geral(), $core->NOTIFICAZAP_Telefone_Origem());
            $information = $whatsapp->getInformation();
            $Conexao = $whatsapp->connectionStatus();
            if ($information["status"] !== 200 || $Conexao["instance"]["state"] !== "open") {
                return NULL;
            }
            $template = $core->NOTIFICAZAP_Pegar_Status_Mensagem_Template("ticketclose");
            if ($template["status_mensagem"] !== "active") {
                return NULL;
            }
            $ticket = WHMCS\Database\Capsule::table("tbltickets")->where("id", $vars["ticketid"])->first();
            $client = WHMCS\User\Client::find($ticket->userid);
            $idCampoNotificacao = $core->NOTIFICAZAP_Pega_Info_tbladdonmodules("addon_notificazap_receber_whatsapp");
            $receberNotificacao = $core->NOTIFICAZAP_Pega_Info_tblcustomfieldsvalues($idCampoNotificacao, $ticket->userid);
            if ($receberNotificacao !== "Sim") {
                return NULL;
            }
            $linkAutologin = NULL;
            if (WHMCS\Database\Capsule::table("tbladdonmodules")->where("module", "autologinmpay")->exists()) {
                $linkAutologin = $core->NOTIFICAZAP_generateAutologinLink($ticket->userid, $ticket->id, "ticket");
            }
            $mensagem = str_replace(["%name%", "%assinatura%", "%ticket%", "%tituloticket%", "%keychamado%", "%dominiowhmcs%"], [$client->firstname, $core->NOTIFICAZAP_Assinatura_WhatsApp(), $ticket->tid, $ticket->title, $ticket->c, $linkAutologin ?: $WHMCS_URLSISTEMA . "viewticket.php?tid=" . $ticket->tid . "&c=" . $ticket->c], $template["corpo_mensagem"]);
            $phone = $core->NOTIFICAZAP_AdjustNumber($client);
            $phone_whatsapp = $whatsapp->Valida_Number_WhatsApp($phone);
            $existsPhone = $phone_whatsapp[0]["exists"] ?? NULL;
            if (!$existsPhone) {
                $core->NOTIFICAZAP_CadastrarLogFalhas($client->id, "Telefone " . $phone . " do Cliente não é um número de WhatsApp");
                $core->NOTIFICAZAP_LogWarning("[NOTIFICA ZAP] O número do Telefone [" . $phone . "] Não é um número de WhatsApp");
            } else {
                $retorno = $core->NOTIFICAZAP_EnviarMensagem($phone, $mensagem);
                if (isset($retorno["status"]) && $retorno["status"] == "success") {
                    $core->NOTIFICAZAP_CadastrarLogWhatsapp($client->id, $mensagem, "sendmessage", "ticketclose");
                    $core->NOTIFICAZAP_LogInfo("[NOTIFICA ZAP] Notificação enviada com sucesso para: " . $phone);
                } else {
                    $core->NOTIFICAZAP_LogWarning("Mensagem de WhatsApp não foi enviada", ["phone" => $phone, "cliente" => json_encode($client), "hook" => "ticket fechado", "ticket" => json_encode($ticket), "dados do retorno" => json_encode($retorno)]);
                    $core->NOTIFICAZAP_CadastrarLogFalhas($client->id, "Falha ao Enviar Mensagem de Ticket Fechado - Phone " . $phone . " Retorno: " . json_encode($retorno));
                }
            }
        }
    }
});
add_hook("InvoiceCancelled", 999, function ($vars) {
    $core = new notificazapCore();
    $onlineServer = $core->NOTIFICAZAP_Servidor_WhatsApp_Online(NZURLSERVIDORWHATSAPP);
    if (!$onlineServer) {
        $core->NOTIFICAZAP_LogWarning("Servidor do WhatsApp está offline.");
    } else {
        $onlineModulesPay = $core->NOTIFICAZAP_ModulesPay_Online(NZSITEMODULESPAY);
        if ($onlineModulesPay) {
            $license = NOTIFICAZAP_check_license($core->NOTIFICAZAP_Licenca());
            $statusdalicenca = $license["status"];
        } else {
            $core->NOTIFICAZAP_LogWarning("Servidor da MODULES PAY está offline ou inacessível.");
            $statusdalicenca = "Active";
        }
        if ($statusdalicenca !== "Active") {
            $core->NOTIFICAZAP_LogWarning("[NOTIFICA ZAP] Licença inválida ou expirada.");
        } else {
            $whatsapp = new notificaWhatsAppMpay($core->NOTIFICAZAP_Server(), $core->NOTIFICAZAP_Descriptografar($core->NOTIFICAZAP_KEY()), $core->NOTIFICAZAP_Token_Geral(), $core->NOTIFICAZAP_Telefone_Origem());
            $information = $whatsapp->getInformation();
            $conexao = $whatsapp->connectionStatus();
            if ($information["status"] !== 200 || $conexao["instance"]["state"] !== "open") {
                return NULL;
            }
            $invoice = WHMCS\Database\Capsule::table("tblinvoices")->where("id", $vars["invoiceid"])->first();
            $client = WHMCS\User\Client::find($invoice->userid);
            $idCampoNotificacao = $core->NOTIFICAZAP_Pega_Info_tbladdonmodules("addon_notificazap_receber_whatsapp");
            $receberNotificacao = $core->NOTIFICAZAP_Pega_Info_tblcustomfieldsvalues($idCampoNotificacao, $invoice->userid);
            if ($receberNotificacao !== "Sim" || $invoice->total == 0) {
                return NULL;
            }
            $template = $core->NOTIFICAZAP_Pegar_Status_Mensagem_Template("invoicecancelled");
            if ($template["status_mensagem"] !== "active") {
                return NULL;
            }
            $linkAutologin = NULL;
            if (WHMCS\Database\Capsule::table("tbladdonmodules")->where("module", "autologinmpay")->exists()) {
                $linkAutologin = $core->NOTIFICAZAP_generateAutologinLink($invoice->userid, $invoice->id, "invoice");
            }
            $mensagem = str_replace(["%name%", "%invoiceid%", "%duedate%", "%value%", "%assinatura%", "%dominiowhmcs%"], [$client->firstname, $vars["invoiceid"], $core->NOTIFICAZAP_AdjustDate($invoice->duedate), $core->NOTIFICAZAP_AdjustValue($invoice->total), $core->NOTIFICAZAP_Assinatura_WhatsApp(), $linkAutologin ?: $core->NOTIFICAZAP_Pega_Info_tblconfiguration("SystemURL") . "viewinvoice.php?id=" . $invoice->id], $template["corpo_mensagem"]);
            $phone = $core->NOTIFICAZAP_AdjustNumber($client);
            $phone_whatsapp = $whatsapp->Valida_Number_WhatsApp($phone);
            $existsPhone = $phone_whatsapp[0]["exists"] ?? NULL;
            if (!$existsPhone) {
                $core->NOTIFICAZAP_CadastrarLogFalhas($client->id, "Telefone " . $phone . " do Cliente não é um número de WhatsApp");
                $core->NOTIFICAZAP_LogWarning("[NOTIFICA ZAP] O número do Telefone [" . $phone . "] Não é um número de WhatsApp");
            } else {
                $retorno = $core->NOTIFICAZAP_EnviarMensagem($phone, $mensagem);
                if (isset($retorno["status"]) && $retorno["status"] == "success") {
                    $core->NOTIFICAZAP_CadastrarLogWhatsapp($client->id, $mensagem, "sendmessage", "invoicecancelled", $invoice->id);
                    $core->NOTIFICAZAP_LogInfo("Notificação enviada com sucesso para o telefone: [" . $phone . "] Cliente ID# " . $client->id);
                } else {
                    $core->NOTIFICAZAP_LogWarning("Mensagem de WhatsApp não foi enviada", ["phone" => $phone, "cliente" => json_encode($client), "hook" => "invoice cancelada", "dados do retorno" => json_encode($retorno)]);
                    $core->NOTIFICAZAP_CadastrarLogFalhas($client->id, "Falha ao Enviar Mensagem de Cancelamento de Invoice - Phone " . $phone . " Retorno: " . json_encode($retorno), $invoice->id);
                }
            }
        }
    }
});
add_hook("InvoicePaid", 999, function ($vars) {
    $core = new notificazapCore();
    $onlineServer = $core->NOTIFICAZAP_Servidor_WhatsApp_Online(NZURLSERVIDORWHATSAPP);
    if (!$onlineServer) {
        $core->NOTIFICAZAP_LogWarning("Servidor do WhatsApp está offline.");
    } else {
        $onlineModulesPay = $core->NOTIFICAZAP_ModulesPay_Online(NZSITEMODULESPAY);
        if ($onlineModulesPay) {
            $license = NOTIFICAZAP_check_license($core->NOTIFICAZAP_Licenca());
            $statusdalicenca = $license["status"];
        } else {
            $core->NOTIFICAZAP_LogWarning("Servidor da MODULES PAY está offline ou inacessível.");
            $statusdalicenca = "Active";
        }
        if ($statusdalicenca !== "Active") {
            $core->NOTIFICAZAP_LogWarning("[NOTIFICA ZAP] Licença inválida ou expirada.");
        } else {
            $whatsapp = new notificaWhatsAppMpay($core->NOTIFICAZAP_Server(), $core->NOTIFICAZAP_Descriptografar($core->NOTIFICAZAP_KEY()), $core->NOTIFICAZAP_Token_Geral(), $core->NOTIFICAZAP_Telefone_Origem());
            $information = $whatsapp->getInformation();
            $conexao = $whatsapp->connectionStatus();
            if ($information["status"] !== 200 || $conexao["instance"]["state"] !== "open") {
                return NULL;
            }
            $invoice = WHMCS\Database\Capsule::table("tblinvoices")->where("id", "=", $vars["invoiceid"])->first();
            if (!$invoice) {
                $core->NOTIFICAZAP_LogWarning("[NOTIFICA ZAP] Nenhuma Invoice Encontrada.");
            } else {
                $client = WHMCS\User\Client::find($invoice->userid);
                if (!$client) {
                    $core->NOTIFICAZAP_LogWarning("[NOTIFICA ZAP] Nenhum Cliente Encontrado.");
                } else {
                    $idCampoNotificacao = $core->NOTIFICAZAP_Pega_Info_tbladdonmodules("addon_notificazap_receber_whatsapp");
                    $receberNotificacao = $core->NOTIFICAZAP_Pega_Info_tblcustomfieldsvalues($idCampoNotificacao, $invoice->userid);
                    if ($receberNotificacao !== "Sim" || $invoice->total == 0) {
                        return NULL;
                    }
                    $template = $core->NOTIFICAZAP_Pegar_Status_Mensagem_Template("invoicepaid");
                    if ($template["status_mensagem"] !== "active") {
                        return NULL;
                    }
                    $linkAutologin = NULL;
                    if (WHMCS\Database\Capsule::table("tbladdonmodules")->where("module", "autologinmpay")->exists()) {
                        $linkAutologin = $core->NOTIFICAZAP_generateAutologinLink($invoice->userid, $invoice->id, "invoice");
                    }
                    $DOMINIO_WHMCS = $core->NOTIFICAZAP_Pega_Info_tblconfiguration("SystemURL");
                    $NOME_EMPRESA = $core->NOTIFICAZAP_Pega_Info_tblconfiguration("CompanyName");
                    $mensagem = str_replace(["%name%", "%invoiceid%", "%duedate%", "%value%", "%assinatura%", "%nomeempresa%", "%dominiowhmcs%", "%linkdainvoice%", "%produto%", "%datapagamento%"], [$client->firstname, $vars["invoiceid"], $core->NOTIFICAZAP_AdjustDate($invoice->duedate), $core->NOTIFICAZAP_AdjustValue($invoice->total), $core->NOTIFICAZAP_Assinatura_WhatsApp(), $NOME_EMPRESA, $DOMINIO_WHMCS, $linkAutologin ?: $DOMINIO_WHMCS . "viewinvoice.php?id=" . $invoice->id, substr($core->NOTIFICAZAP_getProductNameByInvoiceId($invoice->id), 0, 250) ?: "", $core->NOTIFICAZAP_converterDataHoraParaBrasileira($invoice->datepaid)], $template["corpo_mensagem"]);
                    $phone = $core->NOTIFICAZAP_AdjustNumber($client);
                    $phone_whatsapp = $whatsapp->Valida_Number_WhatsApp($phone);
                    $existsPhone = $phone_whatsapp[0]["exists"] ?? NULL;
                    if (!$existsPhone) {
                        $core->NOTIFICAZAP_CadastrarLogFalhas($client->id, "Telefone " . $phone . " do Cliente não é um número de WhatsApp");
                        $core->NOTIFICAZAP_LogWarning("[NOTIFICA ZAP] O número do Telefone [" . $phone . "] Não é um número de WhatsApp");
                    } else {
                        $retorno = $core->NOTIFICAZAP_EnviarMensagem($phone, $mensagem);
                        if (isset($retorno["status"]) && $retorno["status"] == "success") {
                            $core->NOTIFICAZAP_CadastrarLogWhatsapp($client->id, $mensagem, "sendmessage", "invoicepaid", $invoice->id);
                            $core->NOTIFICAZAP_LogInfo("Mensagem WhatsApp enviada com sucesso", ["phone" => $phone, "hook" => "invoicepaid", "status" => $retorno["status"]]);
                        } else {
                            $core->NOTIFICAZAP_LogWarning("Mensagem de WhatsApp não foi enviada", ["phone" => $phone, "cliente" => json_encode($client), "invoice" => json_encode($invoice), "hook" => "invoicepaid", "status" => $retorno["status"]]);
                            $core->NOTIFICAZAP_CadastrarLogFalhas($client->id, "Falha ao Enviar Mensagem de Invoice Paga - Phone " . $phone . " Retorno: " . json_encode($retorno), $invoice->id);
                        }
                    }
                }
            }
        }
    }
});
add_hook("DailyCronJob", 999, function ($vars) {
    $core = new notificazapCore();
    $notasfiscais = WHMCS\Database\Capsule::table("mod_nfasaasmpay")->whereBetween("data_emissao", [$datainicial, $datadehoje])->where("status", "AUTHORIZED")->where("data_envionf", "0000-00-00")->get();
    if (count($notasfiscais) === 0) {
        return NULL;
    }
    $onlineServer = $core->NOTIFICAZAP_Servidor_WhatsApp_Online(NZURLSERVIDORWHATSAPP);
    if (!$onlineServer) {
        $core->NOTIFICAZAP_LogWarning("Servidor do WhatsApp está offline.");
    } else {
        $onlineModulesPay = $core->NOTIFICAZAP_ModulesPay_Online(NZSITEMODULESPAY);
        if ($onlineModulesPay) {
            $license = NOTIFICAZAP_check_license($core->NOTIFICAZAP_Licenca());
            $statusdalicenca = $license["status"];
        } else {
            $core->NOTIFICAZAP_LogWarning("Servidor da MODULES PAY está offline ou inacessível.");
            $statusdalicenca = "Active";
        }
        if ($statusdalicenca !== "Active") {
            $core->NOTIFICAZAP_LogWarning("[NOTIFICA ZAP] Licença inválida ou expirada.");
        } else {
            $whatsapp = new notificaWhatsAppMpay($core->NOTIFICAZAP_Server(), $core->NOTIFICAZAP_Descriptografar($core->NOTIFICAZAP_KEY()), $core->NOTIFICAZAP_Token_Geral(), $core->NOTIFICAZAP_Telefone_Origem());
            $information = $whatsapp->getInformation();
            $connection = $whatsapp->connectionStatus();
            if ($information["status"] != 200 || $connection["instance"]["state"] != "open") {
                return NULL;
            }
            if (!WHMCS\Database\Capsule::schema()->hasTable("mod_nfasaasmpay")) {
                return NULL;
            }
            $DOMINIO_WHMCS = $core->NOTIFICAZAP_Pega_Info_tblconfiguration("SystemURL");
            $enviar_nf = $core->NOTIFICAZAP_Pega_Info_tbladdonmodules("enviar_nota_fiscal");
            if (!$enviar_nf || $enviar_nf != "on" || $enviar_nf == NULL) {
                return NULL;
            }
            $id_campo_receber_notificacao = $core->NOTIFICAZAP_Pega_Info_tbladdonmodules("addon_notificazap_receber_whatsapp");
            $datadehoje = date("Y-m-d");
            $datainicial = date("Y-m-d", strtotime("-5 days", strtotime($datadehoje)));
            if ($notasfiscais->isEmpty()) {
                return NULL;
            }
            foreach ($notasfiscais as $nf) {
                $client = WHMCS\User\Client::find($nf->id_cliente);
                $receber_notificacao = $core->NOTIFICAZAP_Pega_Info_tblcustomfieldsvalues($id_campo_receber_notificacao, $nf->id_cliente);
                if ($receber_notificacao != "Sim") {
                } else {
                    $template = $core->NOTIFICAZAP_Pegar_Status_Mensagem_Template("notafiscalasaas");
                    if ($template["status_mensagem"] != "active") {
                    } else {
                        $corpo_mensagem = str_replace(["%name%", "%numeronf%", "%nfdataemissao%", "%assinatura%", "%invoiceid%", "%value%"], [$client->firstname, $nf->nf_num, $core->NOTIFICAZAP_AdjustDate($nf->data_emissao), $core->NOTIFICAZAP_Assinatura_WhatsApp(), $nf->id_fatura, $core->NOTIFICAZAP_AdjustValue($nf->valor)], $template["corpo_mensagem"]);
                        $linkautologin = $DOMINIO_WHMCS . "clientarea.php";
                        if (WHMCS\Database\Capsule::table("tbladdonmodules")->where("module", "autologinmpay")->exists()) {
                            $linkautologin = $core->NOTIFICAZAP_generateAutologinLink($client->id, $client->id, "painel");
                        }
                        $corpo_mensagem = str_replace("%linkdainvoice%", $linkautologin, $corpo_mensagem);
                        $phone = $core->NOTIFICAZAP_AdjustNumber($client);
                        $phone_whatsapp = $whatsapp->Valida_Number_WhatsApp($phone);
                        $existsPhone = $phone_whatsapp[0]["exists"] ?? NULL;
                        if (!$existsPhone) {
                            $core->NOTIFICAZAP_CadastrarLogFalhas($client->id, "Telefone " . $phone . " do Cliente não é um número de WhatsApp");
                        } else {
                            $retorno = $core->EnviarMensagemComBoleto_NOTIFICAZAP($phone, $nf->pdf, $nf->nf_num, $corpo_mensagem);
                            if (isset($retorno["status"]) && $retorno["status"] == "success") {
                                $core->NOTIFICAZAP_CadastrarLogWhatsapp($client->id, $corpo_mensagem, "sendmessage", "notafiscalasaas");
                                $core->NOTIFICAZAP_LogInfo("Notificação enviada com sucesso para o telefone: [" . $phone . "] Cliente ID# " . $client->id);
                                WHMCS\Database\Capsule::table("mod_nfasaasmpay")->where("id", $nf->id)->update(["data_envionf" => date("Y-m-d")]);
                            } else {
                                $core->NOTIFICAZAP_LogWarning("Mensagem de WhatsApp não foi enviada", ["phone" => $phone, "cliente" => $client->id, "hook" => "envio de nota fiscal asaas", "dados nota fiscal" => json_encode($notasfiscais)]);
                                $core->NOTIFICAZAP_CadastrarLogFalhas($client->id, "Falha ao Enviar Nota Fiscal Asaas - Phone " . $phone . " Retorno: " . json_encode($retorno));
                            }
                        }
                    }
                }
            }
        }
    }
});
add_hook("DailyCronJob", 999, function ($vars) {
    if (!WHMCS\Database\Capsule::schema()->hasTable("tblnotafacil")) {
        return NULL;
    }
    $notasfiscais = WHMCS\Database\Capsule::table("tblnotafacil")->whereDate("created_at", "=", date("Y-m-d"))->where("nota_situacao", "EMITIDO")->where("data_envionf", "0000-00-00")->get();
    if (count($notasfiscais) === 0) {
        return NULL;
    }
    $core = new notificazapCore();
    $onlineServer = $core->NOTIFICAZAP_Servidor_WhatsApp_Online(NZURLSERVIDORWHATSAPP);
    if (!$onlineServer) {
        $core->NOTIFICAZAP_LogWarning("Servidor do WhatsApp está offline.");
    } else {
        $onlineModulesPay = $core->NOTIFICAZAP_ModulesPay_Online(NZSITEMODULESPAY);
        if ($onlineModulesPay) {
            $license = NOTIFICAZAP_check_license($core->NOTIFICAZAP_Licenca());
            $statusdalicenca = $license["status"];
        } else {
            $core->NOTIFICAZAP_LogWarning("Servidor da MODULES PAY está offline ou inacessível.");
            $statusdalicenca = "Active";
        }
        if ($statusdalicenca !== "Active") {
            $core->NOTIFICAZAP_LogWarning("[NOTIFICA ZAP] Licença inválida ou expirada.");
        } else {
            $whatsapp = new notificaWhatsAppMpay($core->NOTIFICAZAP_Server(), $core->NOTIFICAZAP_Descriptografar($core->NOTIFICAZAP_KEY()), $core->NOTIFICAZAP_Token_Geral(), $core->NOTIFICAZAP_Telefone_Origem());
            $information = $whatsapp->getInformation();
            $connection = $whatsapp->connectionStatus();
            if ($information["status"] != 200 || $connection["instance"]["state"] != "open") {
                return NULL;
            }
            $DOMINIO_WHMCS = $core->NOTIFICAZAP_Pega_Info_tblconfiguration("SystemURL");
            $enviar_nf = $core->NOTIFICAZAP_Pega_Info_tbladdonmodules("enviar_nota_fiscal_nota_facil");
            if ($enviar_nf != "on") {
                return NULL;
            }
            $id_campo_receber_notificacao = $core->NOTIFICAZAP_Pega_Info_tbladdonmodules("addon_notificazap_receber_whatsapp");
            foreach ($notasfiscais as $nf) {
                $client = WHMCS\User\Client::find($nf->id_client);
                $receber_notificacao = $core->NOTIFICAZAP_Pega_Info_tblcustomfieldsvalues($id_campo_receber_notificacao, $nf->id_cliente);
                if ($receber_notificacao != "Sim") {
                } else {
                    $template = $core->NOTIFICAZAP_Pegar_Status_Mensagem_Template("notafiscalasaas");
                    if ($template["status_mensagem"] != "active") {
                    } else {
                        $corpo_mensagem = str_replace(["%name%", "%numeronf%", "%nfdataemissao%", "%assinatura%", "%dominiowhmcs%"], [$client->firstname, $nf->numero, $core->NOTIFICAZAP_AdjustDate(date("Y-m-d")), $core->NOTIFICAZAP_Assinatura_WhatsApp(), $DOMINIO_WHMCS], $template["corpo_mensagem"]);
                        $linkautologin = $DOMINIO_WHMCS . "clientarea.php";
                        if (WHMCS\Database\Capsule::table("tbladdonmodules")->where("module", "autologinmpay")->exists()) {
                            $linkautologin = $core->NOTIFICAZAP_generateAutologinLink($client->id, $client->id, "painel");
                        }
                        $corpo_mensagem = str_replace("%linkdainvoice%", $linkautologin, $corpo_mensagem);
                        $phone = $core->NOTIFICAZAP_AdjustNumber($client);
                        $phone_whatsapp = $whatsapp->Valida_Number_WhatsApp($phone);
                        $existsPhone = $phone_whatsapp[0]["exists"] ?? NULL;
                        if (!$existsPhone) {
                            $core->NOTIFICAZAP_CadastrarLogFalhas($client->id, "Telefone " . $phone . " do Cliente não é um número de WhatsApp");
                        } else {
                            $retorno = $core->EnviarMensagemComBoleto_NOTIFICAZAP($phone, $nf->nota_key, $nf->numero, $corpo_mensagem);
                            if (isset($retorno["status"]) && $retorno["status"] == "success") {
                                $core->NOTIFICAZAP_CadastrarLogWhatsapp($client->id, $corpo_mensagem, "sendmessage", "notafiscalasaas");
                                $core->NOTIFICAZAP_LogInfo("[NOTIFICA ZAP] PDF da Nota Fácil enviada com sucesso para o telefone: [" . $phone . "]");
                                WHMCS\Database\Capsule::table("tblnotafacil")->where("id", $nf->id)->update(["data_envionf" => date("Y-m-d")]);
                            } else {
                                $core->NOTIFICAZAP_LogWarning("Mensagem de WhatsApp não foi enviada", ["phone" => $phone, "cliente" => json_encode($client), "hook" => "envio de nota fiscal nota fácil", "dados nota fiscal" => json_encode($notasfiscais)]);
                                $core->NOTIFICAZAP_CadastrarLogFalhas($client->id, "Falha ao Enviar Mensagem de Nota Fiscal Nota Fácil - Phone " . $phone . " Retorno: " . json_encode($retorno));
                            }
                        }
                    }
                }
            }
        }
    }
});
add_hook("AfterCronJob", 1, function ($vars) {
    $core = new notificazapCore();
    $mensagens = WHMCS\Database\Capsule::table("mod_notificazap_cron")->where("enviado", "=", "no")->where("cron", "=", "minutos")->get();
    if ($mensagens->isEmpty()) {
        return NULL;
    }
    $onlineServer = $core->NOTIFICAZAP_Servidor_WhatsApp_Online(NZURLSERVIDORWHATSAPP);
    if (!$onlineServer) {
        $core->NOTIFICAZAP_LogWarning("Servidor do WhatsApp está offline.");
    } else {
        $onlineModulesPay = $core->NOTIFICAZAP_ModulesPay_Online(NZSITEMODULESPAY);
        if ($onlineModulesPay) {
            $license = NOTIFICAZAP_check_license($core->NOTIFICAZAP_Licenca());
            $statusdalicenca = $license["status"];
        } else {
            $core->NOTIFICAZAP_LogWarning("[AFTER] Servidor da MODULES PAY está offline ou inacessível.");
            $statusdalicenca = "Active";
        }
        if ($statusdalicenca !== "Active") {
            $core->NOTIFICAZAP_LogWarning("[NOTIFICA ZAP] Licença inválida ou expirada.");
        } else {
            $whatsapp = new notificaWhatsAppMpay($core->NOTIFICAZAP_Server(), $core->NOTIFICAZAP_Descriptografar($core->NOTIFICAZAP_KEY()), $core->NOTIFICAZAP_Token_Geral(), $core->NOTIFICAZAP_Telefone_Origem());
            $information = $whatsapp->getInformation();
            $connection = $whatsapp->connectionStatus();
            if ($information["status"] != 200 || $connection["instance"]["state"] != "open") {
                return NULL;
            }
            $DOMINIO_WHMCS = $core->NOTIFICAZAP_Pega_Info_tblconfiguration("SystemURL");
            foreach ($mensagens as $mensagem) {
                $clientesIds = explode(",", $mensagem->idclientes);
                if (empty($clientesIds)) {
                } else {
                    WHMCS\Database\Capsule::table("mod_notificazap_cron")->where("id", $mensagem->id)->update(["enviado" => "yes"]);
                    $contadordedisparo = 0;
                    $enviados = false;
                    foreach ($clientesIds as $clienteId) {
                        $client = WHMCS\User\Client::find($clienteId);
                        if (!$client) {
                        } else if ($mensagem->status != "todos" && $mensagem->status != $client->status) {
                        } else {
                            $linkautologin = $DOMINIO_WHMCS . "clientarea.php";
                            if (WHMCS\Database\Capsule::table("tbladdonmodules")->where("module", "autologinmpay")->exists()) {
                                $linkautologin = $core->NOTIFICAZAP_generateAutologinLink($client->id, $client->id, "painel");
                            }
                            $corpo_mensagem = str_replace(["%assinatura%", "%name%", "%email%", "%dominiowhmcs%"], [$core->NOTIFICAZAP_Assinatura_WhatsApp(), $client->firstname, $client->email, $linkautologin], $mensagem->mensagem);
                            $phone = $core->NOTIFICAZAP_AdjustNumber($client);
                            $phone_whatsapp = $whatsapp->Valida_Number_WhatsApp($phone);
                            $existsPhone = $phone_whatsapp[0]["exists"] ?? NULL;
                            if (!$existsPhone) {
                                $core->NOTIFICAZAP_CadastrarLogFalhas($client->id, "Telefone " . $phone . " do Cliente não é um número de WhatsApp");
                            } else {
                                if (50 <= $contadordedisparo) {
                                    sleep(10);
                                    $contadordedisparo = 0;
                                }
                                if ($mensagem->type == "texto") {
                                    $core->NOTIFICAZAP_Envio_Geral_Mensagem($phone, $corpo_mensagem);
                                } else if (!empty($mensagem->urlimagem)) {
                                    if (file_exists(dirname(__FILE__) . "/downloads/" . $mensagem->urlimagem)) {
                                        $pathdaImagem = $DOMINIO_WHMCS . "modules/addons/notificazap/downloads/" . $mensagem->urlimagem;
                                        $core->NOTIFICAZAP_Envio_Imagem_Mensagem($phone, $pathdaImagem, $corpo_mensagem);
                                    } else {
                                        $core->NOTIFICAZAP_Envio_Geral_Mensagem($phone, $corpo_mensagem);
                                    }
                                } else {
                                    $core->NOTIFICAZAP_Envio_Geral_Mensagem($phone, $corpo_mensagem);
                                }
                                $enviados = true;
                                $contadordedisparo++;
                            }
                        }
                    }
                    if ($enviados) {
                        $core->NOTIFICAZAP_LogInfo("[NOTIFICA ZAP] Mensagens Agendadas da lista [" . $mensagem->idclientes . "] foram enviadas com sucesso!");
                    }
                }
            }
        }
    }
});
add_hook("DailyCronJob", 1, function ($vars) {
    $core = new notificazapCore();
    $mensagens = WHMCS\Database\Capsule::table("mod_notificazap_cron")->where("enviado", "=", "no")->where("cron", "=", "diario")->get();
    if ($mensagens->isEmpty()) {
        return NULL;
    }
    $onlineServer = $core->NOTIFICAZAP_Servidor_WhatsApp_Online(NZURLSERVIDORWHATSAPP);
    if (!$onlineServer) {
        $core->NOTIFICAZAP_LogWarning("Servidor do WhatsApp está offline.");
    } else {
        $onlineModulesPay = $core->NOTIFICAZAP_ModulesPay_Online(NZSITEMODULESPAY);
        if ($onlineModulesPay) {
            $license = NOTIFICAZAP_check_license($core->NOTIFICAZAP_Licenca());
            $statusdalicenca = $license["status"];
        } else {
            $core->NOTIFICAZAP_LogWarning("Servidor da MODULES PAY está offline ou inacessível.");
            $statusdalicenca = "Active";
        }
        if ($statusdalicenca !== "Active") {
            $core->NOTIFICAZAP_LogWarning("[NOTIFICA ZAP] Licença inválida ou expirada.");
        } else {
            $whatsapp = new notificaWhatsAppMpay($core->NOTIFICAZAP_Server(), $core->NOTIFICAZAP_Descriptografar($core->NOTIFICAZAP_KEY()), $core->NOTIFICAZAP_Token_Geral(), $core->NOTIFICAZAP_Telefone_Origem());
            $information = $whatsapp->getInformation();
            $connection = $whatsapp->connectionStatus();
            if ($information["status"] != 200 || $connection["instance"]["state"] != "open") {
                return NULL;
            }
            $DOMINIO_WHMCS = $core->NOTIFICAZAP_Pega_Info_tblconfiguration("SystemURL");
            foreach ($mensagens as $mensagem) {
                $clientesIds = explode(",", $mensagem->idclientes);
                if (empty($clientesIds)) {
                } else {
                    WHMCS\Database\Capsule::table("mod_notificazap_cron")->where("id", $mensagem->id)->update(["enviado" => "yes"]);
                    $contadordedisparo = 0;
                    $enviados = false;
                    foreach ($clientesIds as $clienteId) {
                        $client = WHMCS\User\Client::find($clienteId);
                        if (!$client) {
                        } else if ($mensagem->status != "todos" && $mensagem->status != $client->status) {
                        } else {
                            $linkautologin = $DOMINIO_WHMCS . "clientarea.php";
                            if (WHMCS\Database\Capsule::table("tbladdonmodules")->where("module", "autologinmpay")->exists()) {
                                $linkautologin = $core->NOTIFICAZAP_generateAutologinLink($client->id, $client->id, "painel");
                            }
                            $corpo_mensagem = str_replace(["%assinatura%", "%name%", "%email%", "%dominiowhmcs%"], [$core->NOTIFICAZAP_Assinatura_WhatsApp(), $client->firstname, $client->email, $linkautologin], $mensagem->mensagem);
                            $phone = $core->NOTIFICAZAP_AdjustNumber($client);
                            $phone_whatsapp = $whatsapp->Valida_Number_WhatsApp($phone);
                            $existsPhone = $phone_whatsapp[0]["exists"] ?? NULL;
                            if (!$existsPhone) {
                                $core->NOTIFICAZAP_CadastrarLogFalhas($client->id, "Telefone " . $phone . " do Cliente não é um número de WhatsApp");
                            } else {
                                if (50 <= $contadordedisparo) {
                                    sleep(10);
                                    $contadordedisparo = 0;
                                }
                                if ($mensagem->type == "texto") {
                                    $core->NOTIFICAZAP_Envio_Geral_Mensagem($phone, $corpo_mensagem);
                                } else if (!empty($mensagem->urlimagem)) {
                                    if (file_exists(dirname(__FILE__) . "/downloads/" . $mensagem->urlimagem)) {
                                        $pathdaImagem = $DOMINIO_WHMCS . "modules/addons/notificazap/downloads/" . $mensagem->urlimagem;
                                        $core->NOTIFICAZAP_Envio_Imagem_Mensagem($phone, $pathdaImagem, $corpo_mensagem);
                                    } else {
                                        $core->NOTIFICAZAP_Envio_Geral_Mensagem($phone, $corpo_mensagem);
                                    }
                                } else {
                                    $core->NOTIFICAZAP_Envio_Geral_Mensagem($phone, $corpo_mensagem);
                                }
                                $enviados = true;
                                $contadordedisparo++;
                            }
                        }
                    }
                    if ($enviados) {
                        $core->NOTIFICAZAP_LogInfo("[NOTIFICA ZAP] Mensagens Agendadas da lista [" . $mensagem->idclientes . "] foram enviadas com sucesso!");
                    }
                }
            }
        }
    }
});
add_hook("InvoiceCreation", 9999, function ($vars) {
    $invoice_MPAY = WHMCS\Database\Capsule::table("tblinvoices")->where("id", $vars["invoiceid"])->where("total", "!=", 0)->where("status", "=", "Unpaid")->first();
    if (!$invoice_MPAY) {
        return NULL;
    }
    $core = new notificazapCore();
    $onlineServer = $core->NOTIFICAZAP_Servidor_WhatsApp_Online(NZURLSERVIDORWHATSAPP);
    if (!$onlineServer) {
        $core->NOTIFICAZAP_LogWarning("Servidor do WhatsApp está offline.");
    } else {
        $onlineModulesPay = $core->NOTIFICAZAP_ModulesPay_Online(NZSITEMODULESPAY);
        if ($onlineModulesPay) {
            $license = NOTIFICAZAP_check_license($core->NOTIFICAZAP_Licenca());
            $statusdalicenca = $license["status"];
        } else {
            $core->NOTIFICAZAP_LogWarning("Servidor da MODULES PAY está offline ou inacessível.");
            $statusdalicenca = "Active";
        }
        if ($statusdalicenca !== "Active") {
            $core->NOTIFICAZAP_LogWarning("[NOTIFICA ZAP] Licença inválida ou expirada.");
        } else {
            $whatsapp = new notificaWhatsAppMpay($core->NOTIFICAZAP_Server(), $core->NOTIFICAZAP_Descriptografar($core->NOTIFICAZAP_KEY()), $core->NOTIFICAZAP_Token_Geral(), $core->NOTIFICAZAP_Telefone_Origem());
            $information = $whatsapp->getInformation();
            $connection = $whatsapp->connectionStatus();
            if ($information["status"] != 200 || $connection["instance"]["state"] != "open") {
                return NULL;
            }
            $DOMINIO_WHMCS = $core->NOTIFICAZAP_Pega_Info_tblconfiguration("SystemURL");
            $template = $core->NOTIFICAZAP_Pegar_Status_Mensagem_Template("createdinvoice");
            if ($template["status_mensagem"] != "active") {
                return NULL;
            }
            $receber_notificacao = $core->NOTIFICAZAP_Pega_Info_tblcustomfieldsvalues($core->NOTIFICAZAP_Pega_Info_tbladdonmodules("addon_notificazap_receber_whatsapp"), $invoice_MPAY->userid);
            if ($receber_notificacao != "Sim") {
                return NULL;
            }
            $linkautologin = $DOMINIO_WHMCS . "viewinvoice.php?id=" . $invoice_MPAY->id;
            if (WHMCS\Database\Capsule::table("tbladdonmodules")->where("module", "autologinmpay")->exists()) {
                $linkautologin = $core->NOTIFICAZAP_generateAutologinLink($invoice_MPAY->userid, $invoice_MPAY->id, "invoice");
            }
            $client = WHMCS\User\Client::find($invoice_MPAY->userid);
            $corpo_mensagem = str_replace(["%name%", "%invoiceid%", "%duedate%", "%value%", "%assinatura%", "%dominiowhmcs%", "%linkdainvoice%"], [$client->firstname, $invoice_MPAY->id, $core->NOTIFICAZAP_AdjustDate($invoice_MPAY->duedate), $core->NOTIFICAZAP_AdjustValue($invoice_MPAY->total), $core->NOTIFICAZAP_Assinatura_WhatsApp(), $DOMINIO_WHMCS, $linkautologin], $template["corpo_mensagem"]);
            $paymentDescription = WHMCS\Database\Capsule::table("tblpaymentgateways")->where("gateway", $invoice_MPAY->paymentmethod)->where("setting", "name")->value("value");
            if ($paymentDescription) {
                $corpo_mensagem = str_replace("%formapgto%", $paymentDescription, $corpo_mensagem);
            }
            $produto = $core->NOTIFICAZAP_getProductNameByInvoiceId($invoice_MPAY->id);
            $produto != "naoexiste" ? substr($produto, 0, 250) : "";
            $corpo_mensagem = str_replace();
            $phone = $core->NOTIFICAZAP_AdjustNumber($client);
            $phone_whatsapp = $whatsapp->Valida_Number_WhatsApp($phone);
            $existsPhone = $phone_whatsapp[0]["exists"] ?? NULL;
            if (!$existsPhone) {
                $core->NOTIFICAZAP_CadastrarLogFalhas($client->id, "Telefone " . $phone . " do Cliente não é um número de WhatsApp");
                $core->NOTIFICAZAP_LogWarning("[NOTIFICA ZAP] O número do Telefone [" . $phone . "] Não é um número de WhatsApp");
            } else {
                $vencendoHoje = false;
                if (!in_array($invoice_MPAY->paymentmethod, ["asaasboletompay", "asaaspixmpay", "asaascartaompay", "cobrancaasaasmpay", "paghiper", "efipixmpay", "paghiper_pix"])) {
                    if ($invoice_MPAY->paymentmethod == "mpagopixmpay") {
                        $mercadoPix = WHMCS\Database\Capsule::table("mod_mpagopixmpay")->where("fatura_id", $invoice_MPAY->id)->where("status", "pending")->where("notenviado", "=", "N")->first();
                        if ($mercadoPix) {
                            include "notifica.mpago.php";
                        }
                    } else {
                        $retorno = $core->NOTIFICAZAP_EnviarMensagem($phone, $corpo_mensagem);
                    }
                    if (isset($retorno["status"]) && $retorno["status"] === "success") {
                        $core->NOTIFICAZAP_CadastrarLogWhatsapp($client->id, $corpo_mensagem, "sendmessage", "createdinvoice", $invoice_MPAY->id);
                    } else {
                        $core->NOTIFICAZAP_LogWarning("Mensagem de WhatsApp não foi enviada", ["phone" => $phone, "cliente" => json_encode($client), "hook" => "envio de nota fiscal nota fácil", "invoice" => json_encode($invoice_MPAY)]);
                        $core->NOTIFICAZAP_CadastrarLogFalhas($client->id, "Falha ao Enviar Mensagem de Primeira Fatura - Phone " . $phone . " Retorno: " . json_encode($retorno), $invoice_MPAY->id);
                    }
                }
            }
        }
    }
});
add_hook("EmailPreSend", 1, function ($vars) {
    $invoiceId = $vars["relid"];
    $emailTemplate = $vars["messagename"];
    $systemUrl = rtrim(App::getSystemUrl(), "/");
    $invoice_MPAY = WHMCS\Database\Capsule::table("tblinvoices")->where("id", $invoiceId)->where("status", "Unpaid")->where("duedate", "<>", date("Y-m-d"))->first();
    if (!$invoice_MPAY) {
        return NULL;
    }
    $core = new notificazapCore();
    $onlineServer = $core->NOTIFICAZAP_Servidor_WhatsApp_Online(NZURLSERVIDORWHATSAPP);
    if (!$onlineServer) {
        $core->NOTIFICAZAP_LogWarning("Servidor do WhatsApp está offline.");
    } else {
        $onlineModulesPay = $core->NOTIFICAZAP_ModulesPay_Online(NZSITEMODULESPAY);
        if ($onlineModulesPay) {
            $license = NOTIFICAZAP_check_license($core->NOTIFICAZAP_Licenca());
            $statusdalicenca = $license["status"];
        } else {
            $core->NOTIFICAZAP_LogWarning("Servidor da MODULES PAY está offline ou inacessível.");
            $statusdalicenca = "Active";
        }
        if ($statusdalicenca !== "Active") {
            $core->NOTIFICAZAP_LogWarning("[NOTIFICA ZAP] Licença inválida ou expirada.");
        } else {
            $DOMINIO_WHMCS = $core->NOTIFICAZAP_Pega_Info_tblconfiguration("SystemURL");
            $whatsapp = new notificaWhatsAppMpay($core->NOTIFICAZAP_Server(), $core->NOTIFICAZAP_Descriptografar($core->NOTIFICAZAP_KEY()), $core->NOTIFICAZAP_Token_Geral(), $core->NOTIFICAZAP_Telefone_Origem());
            $userId = $invoice_MPAY->userid;
            $receberNotificacao = $core->NOTIFICAZAP_Pega_Info_tblcustomfieldsvalues($core->NOTIFICAZAP_Pega_Info_tbladdonmodules("addon_notificazap_receber_whatsapp"), $userId);
            if ($receberNotificacao !== "Sim") {
                return NULL;
            }
            $templateMap = ["Invoice Payment Reminder" => "invoicereminder", "First Invoice Overdue Notice" => "servicealerta", "Second Invoice Overdue Notice" => "servicealerta", "Third Invoice Overdue Notice" => "servicealerta"];
            if (!isset($templateMap[$emailTemplate])) {
                return NULL;
            }
            $templateData = $core->NOTIFICAZAP_Pegar_Status_Mensagem_Template($templateMap[$emailTemplate]);
            $corpo_mensagem = $templateData["corpo_mensagem"];
            if ($templateData["status_mensagem"] !== "active") {
                return NULL;
            }
            $nomedotemplate = $templateMap[$emailTemplate];
            $client = WHMCS\User\Client::find($invoice_MPAY->userid);
            $paymentMethodName = WHMCS\Database\Capsule::table("tblpaymentgateways")->where("gateway", $invoice_MPAY->paymentmethod)->where("setting", "name")->value("value");
            if ($paymentMethodName) {
                $corpo_mensagem = str_replace("%formapgto%", $paymentMethodName, $corpo_mensagem);
            }
            $productName = $core->NOTIFICAZAP_getProductNameByInvoiceId($invoice_MPAY->id);
            if ($productName) {
                $corpo_mensagem = str_replace("%produto%", substr($productName, 0, 250), $corpo_mensagem);
            }
            $linkautologin = NULL;
            if (WHMCS\Database\Capsule::table("tbladdonmodules")->where("module", "autologinmpay")->exists()) {
                $linkautologin = $core->NOTIFICAZAP_generateAutologinLink($userId, $invoice_MPAY->id, "invoice");
            }
            if ($linkautologin != NULL) {
                $corpo_mensagem = str_replace("%linkdainvoice%", $linkautologin, $corpo_mensagem);
            }
            $corpo_mensagem = str_replace(["%assinatura%", "%name%", "%invoiceid%", "%dominiowhmcs%", "%value%", "%duedate%", "%days%"], [$core->NOTIFICAZAP_Assinatura_WhatsApp(), $client->firstname, $invoice_MPAY->id, $DOMINIO_WHMCS, $core->NOTIFICAZAP_AdjustValue($invoice_MPAY->total), $core->NOTIFICAZAP_AdjustDate($invoice_MPAY->duedate), $core->NOTIFICAZAP_diasEntreDatas($invoice_MPAY->duedate)], $corpo_mensagem);
            $phone = $core->NOTIFICAZAP_AdjustNumber($client);
            $phone_whatsapp = $whatsapp->Valida_Number_WhatsApp($phone);
            $existsPhone = $phone_whatsapp[0]["exists"] ?? NULL;
            if (!$existsPhone) {
                $core->NOTIFICAZAP_CadastrarLogFalhas($client->id, "Telefone " . $phone . " do Cliente não é um número de WhatsApp");
                $core->NOTIFICAZAP_LogWarning("[NOTIFICA ZAP] O número do Telefone [" . $phone . "] Não é um número de WhatsApp");
            } else {
                $vencendoHoje = false;
                if (in_array($invoice_MPAY->paymentmethod, ["asaasboletompay", "asaaspixmpay", "asaascartaompay", "cobrancaasaasmpay"])) {
                    if (!WHMCS\Database\Capsule::schema()->hasTable("mod_cobrancaasaasmpay")) {
                        return NULL;
                    }
                    $cobrancaasaas = WHMCS\Database\Capsule::table("mod_cobrancaasaasmpay")->where("fatura_id", "=", $invoice_MPAY->id)->where("user_id", "=", $invoice_MPAY->userid)->where("status", "=", "unpaid")->first();
                    if ($cobrancaasaas) {
                        include "notifica.asaas.php";
                    }
                } else if ($invoice_MPAY->paymentmethod == "efipixmpay") {
                    $efipaypix = WHMCS\Database\Capsule::table("mod_efimpay")->where("fatura_id", "=", $invoice_MPAY->id)->where("status", "=", "ATIVA")->first();
                    if ($efipaypix) {
                        include "notifica.efipay.php";
                    }
                } else if (in_array($invoice_MPAY->paymentmethod, ["paghiper", "paghiper_pix"])) {
                    $paghiper = WHMCS\Database\Capsule::table("mod_paghiper")->where("order_id", "=", $invoice_MPAY->id)->first();
                    if ($paghiper) {
                        include "notifica.paghiper.php";
                    }
                } else if ($invoice_MPAY->paymentmethod == "mpagopixmpay") {
                    $mercadoPix = WHMCS\Database\Capsule::table("mod_mpagopixmpay")->where("fatura_id", $invoice_MPAY->id)->where("status", "pending")->first();
                    if ($mercadoPix) {
                        include "notifica.mpago.php";
                    }
                } else {
                    $retorno = $core->NOTIFICAZAP_EnviarMensagem($phone, $corpo_mensagem);
                }
                if (isset($retorno["status"]) && $retorno["status"] == "success") {
                    $core->NOTIFICAZAP_CadastrarLogWhatsapp($client->id, $corpo_mensagem, "sendmessage", $nomedotemplate, $invoice_MPAY->id);
                    $core->NOTIFICAZAP_LogInfo("Mensagem WhatsApp enviada com sucesso", ["phone" => $phone, "status" => $retorno["status"]]);
                } else {
                    $core->NOTIFICAZAP_LogWarning("Mensagem de WhatsApp não foi enviada", ["phone" => $phone, "cliente" => json_encode($client), "hook" => "enviar.notificacao_service", "dados do retorno" => json_encode($retorno)]);
                    $core->NOTIFICAZAP_CadastrarLogFalhas($client->id, "Falha ao Enviar Mensagem Lembretes de Pagmanetos - Phone " . $phone . " - Status: " . json_encode($retorno), $invoice_MPAY->id);
                }
            }
        }
    }
});
add_hook("DailyCronJob", 1, function ($vars) {
    $systemUrl = rtrim(App::getSystemUrl(), "/");
    $hoje = date("Y-m-d");
    $faturas = WHMCS\Database\Capsule::table("tblinvoices")->where("duedate", $hoje)->where("status", "Unpaid")->get();
    if ($faturas->isEmpty()) {
        return NULL;
    }
    $core = new notificazapCore();
    $onlineServer = $core->NOTIFICAZAP_Servidor_WhatsApp_Online(NZURLSERVIDORWHATSAPP);
    if (!$onlineServer) {
        $core->NOTIFICAZAP_LogWarning("Servidor do WhatsApp está offline.");
    } else {
        $onlineModulesPay = $core->NOTIFICAZAP_ModulesPay_Online(NZSITEMODULESPAY);
        if ($onlineModulesPay) {
            $license = NOTIFICAZAP_check_license($core->NOTIFICAZAP_Licenca());
            $statusdalicenca = $license["status"];
        } else {
            $core->NOTIFICAZAP_LogWarning("Servidor da MODULES PAY está offline ou inacessível.");
            $statusdalicenca = "Active";
        }
        if ($statusdalicenca !== "Active") {
            $core->NOTIFICAZAP_LogWarning("[NOTIFICA ZAP] Licença inválida ou expirada.");
        } else {
            $DOMINIO_WHMCS = $core->NOTIFICAZAP_Pega_Info_tblconfiguration("SystemURL");
            $whatsapp = new notificaWhatsAppMpay($core->NOTIFICAZAP_Server(), $core->NOTIFICAZAP_Descriptografar($core->NOTIFICAZAP_KEY()), $core->NOTIFICAZAP_Token_Geral(), $core->NOTIFICAZAP_Telefone_Origem());
            $vencendoHoje = true;
            foreach ($faturas as $invoice_MPAY) {
                $userId = $invoice_MPAY->userid;
                $receberNotificacao = $core->NOTIFICAZAP_Pega_Info_tblcustomfieldsvalues($core->NOTIFICAZAP_Pega_Info_tbladdonmodules("addon_notificazap_receber_whatsapp"), $userId);
                if ($receberNotificacao != "Sim") {
                } else {
                    $nomedotemplate = "faturavencendohoje";
                    $templateData = $core->NOTIFICAZAP_Pegar_Status_Mensagem_Template($nomedotemplate);
                    $corpo_mensagem = $templateData["corpo_mensagem"];
                    if ($templateData["status_mensagem"] !== "active") {
                    } else {
                        $client = WHMCS\User\Client::find($invoice_MPAY->userid);
                        if (!$client) {
                        } else {
                            $paymentMethodName = WHMCS\Database\Capsule::table("tblpaymentgateways")->where("gateway", $invoice_MPAY->paymentmethod)->where("setting", "name")->value("value");
                            if (!$paymentMethodName) {
                            } else {
                                $corpo_mensagem = str_replace("%formapgto%", $paymentMethodName, $corpo_mensagem);
                                $productName = $core->NOTIFICAZAP_getProductNameByInvoiceId($invoice_MPAY->id);
                                if (!$productName) {
                                } else {
                                    $corpo_mensagem = str_replace("%produto%", substr($productName, 0, 250), $corpo_mensagem);
                                    $linkautologin = NULL;
                                    if (WHMCS\Database\Capsule::table("tbladdonmodules")->where("module", "autologinmpay")->exists()) {
                                        $linkautologin = $core->NOTIFICAZAP_generateAutologinLink($userId, $invoice_MPAY->id, "invoice");
                                    }
                                    if ($linkautologin != NULL) {
                                        $corpo_mensagem = str_replace("%linkdainvoice%", $linkautologin, $corpo_mensagem);
                                    }
                                    $corpo_mensagem = str_replace(["%assinatura%", "%name%", "%invoiceid%", "%dominiowhmcs%", "%value%", "%duedate%"], [$core->NOTIFICAZAP_Assinatura_WhatsApp(), $client->firstname, $invoice_MPAY->id, $DOMINIO_WHMCS, $core->NOTIFICAZAP_AdjustValue($invoice_MPAY->total), $core->NOTIFICAZAP_AdjustDate($invoice_MPAY->duedate)], $corpo_mensagem);
                                    $phone = $core->NOTIFICAZAP_AdjustNumber($client);
                                    $phone_whatsapp = $whatsapp->Valida_Number_WhatsApp($phone);
                                    $existsPhone = $phone_whatsapp[0]["exists"] ?? NULL;
                                    if (!$existsPhone) {
                                        $core->NOTIFICAZAP_CadastrarLogFalhas($client->id, "Telefone " . $phone . " do Cliente não é um número de WhatsApp");
                                    } else {
                                        if (in_array($invoice_MPAY->paymentmethod, ["asaasboletompay", "asaaspixmpay", "asaascartaompay", "cobrancaasaasmpay"])) {
                                            if (!WHMCS\Database\Capsule::schema()->hasTable("mod_cobrancaasaasmpay")) {
                                            } else {
                                                $cobrancaasaas = WHMCS\Database\Capsule::table("mod_cobrancaasaasmpay")->where("fatura_id", "=", $invoice_MPAY->id)->where("user_id", "=", $invoice_MPAY->userid)->where("status", "=", "unpaid")->where("hojeenviado", "=", "N")->first();
                                                if ($cobrancaasaas) {
                                                    include "notifica.asaas.php";
                                                }
                                            }
                                        }
                                        if ($invoice_MPAY->paymentmethod == "efipixmpay") {
                                            if (!WHMCS\Database\Capsule::schema()->hasTable("mod_efimpay")) {
                                            } else {
                                                $efipaypix = WHMCS\Database\Capsule::table("mod_efimpay")->where("fatura_id", "=", $invoice_MPAY->id)->where("status", "=", "ATIVA")->where("hojeenviado", "=", "N")->first();
                                                if ($efipaypix) {
                                                    include "notifica.efipay.php";
                                                }
                                            }
                                        }
                                        if (in_array($invoice_MPAY->paymentmethod, ["paghiper", "paghiper_pix"])) {
                                            if (!WHMCS\Database\Capsule::schema()->hasTable("mod_paghiper")) {
                                            } else {
                                                $paghiper = WHMCS\Database\Capsule::table("mod_paghiper")->where("order_id", "=", $invoice_MPAY->id)->where("hojeenviado", "=", "N")->first();
                                                if ($paghiper) {
                                                    include "notifica.paghiper.php";
                                                }
                                            }
                                        }
                                        if ($invoice_MPAY->paymentmethod == "mpagopixmpay") {
                                            if (!WHMCS\Database\Capsule::schema()->hasTable("mod_mpagopixmpay")) {
                                            } else {
                                                $mercadoPix = WHMCS\Database\Capsule::table("mod_mpagopixmpay")->where("fatura_id", $invoice_MPAY->id)->where("status", "pending")->where("hojeenviado", "=", "N")->first();
                                                if ($mercadoPix) {
                                                    include "notifica.mpago.php";
                                                }
                                            }
                                        }
                                        if (isset($retorno["status"]) && $retorno["status"] == "success") {
                                            $core->NOTIFICAZAP_CadastrarLogWhatsapp($client->id, $corpo_mensagem, "sendmessage", $nomedotemplate, $invoice_MPAY->id);
                                            $core->NOTIFICAZAP_LogInfo("Mensagem WhatsApp enviada com sucesso", ["phone" => $phone, "status" => $retorno["status"]]);
                                        } else {
                                            $core->NOTIFICAZAP_LogWarning("Mensagem de WhatsApp não foi enviada", ["phone" => $phone, "cliente" => json_encode($client), "hook" => "lembretes de pagamentos via cron", "dados do retorno" => json_encode($retorno)]);
                                            $core->NOTIFICAZAP_CadastrarLogFalhas($client->id, "Falha ao Enviar Mensagem Lembretes de Pagmanetos no Cron - Phone " . $phone . " - Status: " . json_encode($retorno), $invoice_MPAY->id);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
});
add_hook("DailyCronJob", 1, function ($vars) {
    $core = new notificazapCore();
    $numberCampo = $core->NOTIFICAZAP_Pega_Info_tbladdonmodules("addon_notificazap_receber_whatsapp");
    if (is_null($numberCampo)) {
        return NULL;
    }
    $clients = WHMCS\Database\Capsule::table("tblclients")->where("status", "Active")->get();
    foreach ($clients as $client) {
        $receber_notificacao = $core->NOTIFICAZAP_Pega_Info_tblcustomfieldsvalues($numberCampo, $client->id);
        if ($receber_notificacao) {
        } else {
            $dados = WHMCS\Database\Capsule::table("tblcustomfields")->where("id", $numberCampo)->where("type", "client")->first();
            if (!$dados) {
            } else {
                $condicao = explode(",", $dados->fieldoptions);
                if (isset($condicao[0]) && !empty($condicao[0])) {
                    $core->NOTIFICAZAP_Inserir_Campo_Personalizado_Receber_WhatsApp($numberCampo, $client->id, $condicao[0]);
                }
            }
        }
    }
});
add_hook("DailyCronJob", 1, function ($vars) {
    $core = new notificazapCore();
    $hora_atual = date("H");
    if ($hora_atual < 8 || 17 <= $hora_atual) {
        $core->NOTIFICAZAP_LogInfo("[NOTIFICA ZAP] Fora do horário comercial. Disparos adiados.");
    } else {
        $onlineServer = $core->NOTIFICAZAP_Servidor_WhatsApp_Online(NZURLSERVIDORWHATSAPP);
        if (!$onlineServer) {
            $core->NOTIFICAZAP_LogWarning("Servidor do WhatsApp está offline.");
        } else {
            $onlineModulesPay = $core->NOTIFICAZAP_ModulesPay_Online(NZSITEMODULESPAY);
            if ($onlineModulesPay) {
                $license = NOTIFICAZAP_check_license($core->NOTIFICAZAP_Licenca());
                $statusdalicenca = $license["status"] ?? "Inactive";
            } else {
                $statusdalicenca = "Active";
            }
            if ($statusdalicenca !== "Active") {
                $core->NOTIFICAZAP_LogWarning("[NOTIFICA ZAP] Licença inválida ou expirada.");
            } else {
                $whatsapp = new notificaWhatsAppMpay($core->NOTIFICAZAP_Server(), $core->NOTIFICAZAP_Descriptografar($core->NOTIFICAZAP_KEY()), $core->NOTIFICAZAP_Token_Geral(), $core->NOTIFICAZAP_Telefone_Origem());
                $information = $whatsapp->getInformation();
                $connection = $whatsapp->connectionStatus();
                if (($information["status"] ?? 0) !== 200) {
                    $core->NOTIFICAZAP_LogWarning("Erro ao verificar status do servidor de WhatsApp.");
                } else if (($connection["instance"]["state"] ?? "") !== "open") {
                    $core->NOTIFICAZAP_LogWarning("Instância WhatsApp não está aberta.");
                } else {
                    $registro = WHMCS\Database\Capsule::table("mod_notificazap_controle_envios")->where("id", 1)->first();
                    if (!$registro) {
                        WHMCS\Database\Capsule::table("mod_notificazap_controle_envios")->insert(["id" => 1, "data" => date("Y-m-d"), "total_enviado" => 0]);
                        $registro = (object) ["data" => date("Y-m-d"), "total_enviado" => 0];
                    }
                    $dataBanco = $registro->data;
                    $enviosNoDia = (int) $registro->total_enviado;
                    $hoje = date("Y-m-d");
                    if ($dataBanco !== $hoje) {
                        $enviosNoDia = 0;
                        WHMCS\Database\Capsule::table("mod_notificazap_controle_envios")->where("id", 1)->update(["data" => $hoje, "total_enviado" => 0]);
                    }
                    $LIMITE_POR_MINUTO = 30;
                    $LIMITE_POR_DIA = 2000;
                    $PAUSA_A_CADA = 100;
                    $enviosNoMinuto = 0;
                    $contadorTotal = 0;
                    $inicioDoMinuto = time();
                    $inicioDoDia = strtotime(date("Y-m-d 00:00:00"));
                    $enviados = 0;
                    $erros = 0;
                    $tentativasConsecutivas = 0;
                    $ultimoErro = NULL;
                    $disparos = WHMCS\Database\Capsule::table("mod_notificazap_disparos")->where("status", "pending")->get();
                    foreach ($disparos as $disparo) {
                        WHMCS\Database\Capsule::table("mod_notificazap_disparos")->where("id", $disparo->id)->update(["status" => "processing"]);
                        $arquivo = $disparo->arquivo;
                        if (!file_exists($arquivo)) {
                            $core->NOTIFICAZAP_LogWarning("[NOTIFICA ZAP] Arquivo não encontrado: " . $arquivo);
                        } else {
                            $lines = file($arquivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                            $totalLinhas = count($lines);
                            foreach ($lines as $line) {
                                if ($LIMITE_POR_DIA <= $enviosNoDia) {
                                    $core->NOTIFICAZAP_LogWarning("[NOTIFICA ZAP] Limite diário de " . $LIMITE_POR_DIA . " mensagens atingido.");
                                } else {
                                    if (60 <= time() - $inicioDoMinuto) {
                                        $inicioDoMinuto = time();
                                        $enviosNoMinuto = 0;
                                    }
                                    if ($LIMITE_POR_MINUTO <= $enviosNoMinuto) {
                                        $espera = 60 - (time() - $inicioDoMinuto);
                                        if (0 < $espera) {
                                            $core->NOTIFICAZAP_LogInfo("[NOTIFICA ZAP] Aguardando " . $espera . "s para respeitar limite por minuto...");
                                            sleep($espera);
                                        }
                                        $inicioDoMinuto = time();
                                        $enviosNoMinuto = 0;
                                    }
                                    if ($PAUSA_A_CADA <= $contadorTotal) {
                                        $intervalo = rand(30, 60);
                                        $core->NOTIFICAZAP_LogInfo("[NOTIFICA ZAP] Pausa anti-bloqueio de " . $intervalo . "s.");
                                        sleep($intervalo);
                                        $contadorTotal = 0;
                                        $tentativasConsecutivas = 0;
                                    }
                                    $delayEntreMensagens = rand(3000, 10000) / 1000;
                                    $microVariacao = rand(100, 500) / 1000;
                                    $delayTotal = $delayEntreMensagens + $microVariacao;
                                    usleep((int) ($delayTotal * 1000000));
                                    $parts = explode(",", trim($line));
                                    if (count($parts) !== 2) {
                                        $erros++;
                                    } else {
                                        $name = trim($parts[0]);
                                        $phone = trim($parts[1]);
                                        $message_hash = md5($disparo->mensagem . $phone);
                                        $already_sent = WHMCS\Database\Capsule::table("mod_notificazap_sent_messages")->where("phone", $phone)->where("message_hash", $message_hash)->exists();
                                        if ($already_sent) {
                                        } else {
                                            $corpo_mensagem = str_replace(["%name%", "%telefone%", "%assinatura%"], [$name, $phone, $core->NOTIFICAZAP_Assinatura_WhatsApp()], $disparo->mensagem);
                                            $phone_whatsapp = $whatsapp->Valida_Number_WhatsApp($phone);
                                            $existsPhone = $phone_whatsapp[0]["exists"] ?? NULL;
                                            if (!$existsPhone) {
                                                $core->NOTIFICAZAP_LogWarning("Número inválido: " . $phone . " (" . $name . ")");
                                                $erros++;
                                            } else {
                                                if ($disparo->tipo === "imagem" && !empty($disparo->imagem)) {
                                                    $retorno = $core->NOTIFICAZAP_Envio_Imagem_Mensagem($phone, $disparo->imagem, $corpo_mensagem);
                                                } else {
                                                    $retorno = $core->NOTIFICAZAP_EnviarMensagem($phone, $corpo_mensagem);
                                                }
                                                if (($retorno["status"] ?? "") === "success") {
                                                    WHMCS\Database\Capsule::table("mod_notificazap_sent_messages")->insert(["phone" => $phone, "message_hash" => $message_hash, "sent_at" => date("Y-m-d H:i:s")]);
                                                    $enviosNoMinuto++;
                                                    $enviosNoDia++;
                                                    $contadorTotal++;
                                                    $enviados++;
                                                    $tentativasConsecutivas = 0;
                                                    if ($enviados % 10 === 0) {
                                                        WHMCS\Database\Capsule::table("mod_notificazap_controle_envios")->where("id", 1)->update(["total_enviado" => $enviosNoDia]);
                                                    }
                                                    $core->NOTIFICAZAP_LogInfo("Disparo Enviado para " . $phone);
                                                } else {
                                                    $core->NOTIFICAZAP_LogWarning("Erro ao enviar para " . $phone . ": " . ($retorno["message"] ?? "erro"));
                                                    $erros++;
                                                    $tentativasConsecutivas++;
                                                    $ultimoErro = $retorno["message"] ?? "erro_desconhecido";
                                                    if (5 <= $tentativasConsecutivas) {
                                                        $errosTemporarios = ["rate_limit", "429", "timeout", "server_error", "too_many_requests"];
                                                        $isErroTemporario = false;
                                                        foreach ($errosTemporarios as $erroTemp) {
                                                            if (stripos($ultimoErro, $erroTemp) !== false) {
                                                                $isErroTemporario = true;
                                                                if ($isErroTemporario) {
                                                                    $tempoEspera = min(pow(2, $tentativasConsecutivas), 300);
                                                                    $core->NOTIFICAZAP_LogWarning("[NOTIFICA ZAP] Backoff exponencial: aguardando " . $tempoEspera . "s após " . $tentativasConsecutivas . " erros consecutivos...");
                                                                    sleep($tempoEspera);
                                                                    $tentativasConsecutivas = 0;
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                            $statusFinal = $enviados === $totalLinhas ? "sent" : (0 < $enviados ? "partial" : "error");
                            WHMCS\Database\Capsule::table("mod_notificazap_disparos")->where("id", $disparo->id)->update(["status" => $statusFinal]);
                            $core->NOTIFICAZAP_LogInfo("Disparo de Mensagens enviadas. Total hoje: " . $enviosNoDia);
                            $core->NOTIFICAZAP_LogInfo("[NOTIFICA ZAP] Campanha '" . $disparo->nome_campanha . "' finalizada. Enviados: " . $enviados . ", Erros: " . $erros);
                        }
                    }
                }
            }
        }
    }
});
add_hook("DailyCronJob", 1, function ($vars) {
    $core = new notificazapCore();
    $dataLimite = date("Y-m-d H:i:s", strtotime("-14 days"));
    try {
        $quantidadeRegistros = WHMCS\Database\Capsule::table("mod_notificazap_registry")->where("created_at", "<", $dataLimite)->count();
        if (0 < $quantidadeRegistros) {
            $registrosExcluidos = WHMCS\Database\Capsule::table("mod_notificazap_registry")->where("created_at", "<", $dataLimite)->delete();
            $core->NOTIFICAZAP_LogWarning("[NOTIFICA ZAP] Limpeza automática de logs executada. Registros excluídos: " . $registrosExcluidos . " (logs anteriores a " . $dataLimite . ")");
            $core->NOTIFICAZAP_LogInfo("Limpeza automática de logs executada", ["registros_excluidos" => $registrosExcluidos, "data_limite" => $dataLimite, "acao" => "cleanup_old_logs"]);
        }
    } catch (Exception $e) {
        $core->NOTIFICAZAP_LogWarning("[NOTIFICA ZAP] Erro na limpeza automática de logs: " . $e->getMessage());
        $core->NOTIFICAZAP_LogError("Erro na limpeza automática de logs", ["erro" => $e->getMessage(), "data_limite" => $dataLimite, "acao" => "cleanup_old_logs"]);
    }
});
add_hook("AfterCronJob", 1, function ($vars) {
    $hoje = date("Y-m-d");
    $asaasMethods = ["asaasboletompay", "asaaspixmpay", "asaascartaompay", "cobrancaasaasmpay", "paghiper", "paghiper_pix", "efipixmpay"];
    $invoices = WHMCS\Database\Capsule::table("tblinvoices")->where("date", "=", $hoje)->where("status", "=", "Unpaid")->where("total", "!=", 0)->whereIn("paymentmethod", $asaasMethods)->get();
    if ($invoices->isEmpty()) {
        return NULL;
    }
    $core = new notificazapCore();
    $onlineServer = $core->NOTIFICAZAP_Servidor_WhatsApp_Online(NZURLSERVIDORWHATSAPP);
    if (!$onlineServer) {
        $core->NOTIFICAZAP_LogWarning("Servidor do WhatsApp está offline.");
    } else {
        $onlineModulesPay = $core->NOTIFICAZAP_ModulesPay_Online(NZSITEMODULESPAY);
        if (!$onlineModulesPay) {
            $core->NOTIFICAZAP_LogWarning("Servidor da MODULES PAY está offline ou inacessível.");
            $statusdalicenca = "Active";
        } else {
            $license = NOTIFICAZAP_check_license($core->NOTIFICAZAP_Licenca());
            $statusdalicenca = $license["status"];
        }
        if ($statusdalicenca !== "Active") {
            $core->NOTIFICAZAP_LogWarning("[NOTIFICA ZAP] Licença inválida ou expirada.");
        } else {
            $whatsapp = new notificaWhatsAppMpay($core->NOTIFICAZAP_Server(), $core->NOTIFICAZAP_Descriptografar($core->NOTIFICAZAP_KEY()), $core->NOTIFICAZAP_Token_Geral(), $core->NOTIFICAZAP_Telefone_Origem());
            $DOMINIO_WHMCS = $core->NOTIFICAZAP_Pega_Info_tblconfiguration("SystemURL");
            $id_campo_receber_notificacao = $core->NOTIFICAZAP_Pega_Info_tbladdonmodules("addon_notificazap_receber_whatsapp");
            $information = $whatsapp->getInformation();
            $Conexao = $whatsapp->connectionStatus();
            if ($information["status"] !== 200 || $Conexao["instance"]["state"] !== "open") {
                return NULL;
            }
            $templateData = $core->NOTIFICAZAP_Pegar_Status_Mensagem_Template("createdinvoice");
            $nomedotemplate = "createdinvoice";
            if ($templateData["status_mensagem"] != "active") {
                return NULL;
            }
            foreach ($invoices as $invoice_MPAY) {
                $receber_notificacao = $core->NOTIFICAZAP_Pega_Info_tblcustomfieldsvalues($id_campo_receber_notificacao, $invoice_MPAY->userid);
                if ($receber_notificacao != "Sim") {
                } else {
                    $client = WHMCS\User\Client::find($invoice_MPAY->userid);
                    $linkautologin = $DOMINIO_WHMCS . "viewinvoice.php?id=" . $invoice_MPAY->id;
                    if (WHMCS\Database\Capsule::table("tbladdonmodules")->where("module", "autologinmpay")->exists()) {
                        $linkautologin = $core->NOTIFICAZAP_generateAutologinLink($invoice_MPAY->userid, $invoice_MPAY->id, "invoice");
                    }
                    $corpo_mensagem = str_replace(["%name%", "%invoiceid%", "%duedate%", "%value%", "%assinatura%", "%dominiowhmcs%", "%linkdainvoice%"], [$client->firstname, $invoice_MPAY->id, $core->NOTIFICAZAP_AdjustDate($invoice_MPAY->duedate), $core->NOTIFICAZAP_AdjustValue($invoice_MPAY->total), $core->NOTIFICAZAP_Assinatura_WhatsApp(), $DOMINIO_WHMCS, $linkautologin], $templateData["corpo_mensagem"]);
                    $paymentDescription = WHMCS\Database\Capsule::table("tblpaymentgateways")->where("gateway", $invoice_MPAY->paymentmethod)->where("setting", "name")->value("value");
                    if ($paymentDescription) {
                        $corpo_mensagem = str_replace("%formapgto%", $paymentDescription, $corpo_mensagem);
                    }
                    $nomedoproduto = $core->NOTIFICAZAP_getProductNameByInvoiceId($invoice_MPAY->id);
                    $corpo_mensagem = str_replace("%produto%", $nomedoproduto !== "naoexiste" ? substr($nomedoproduto, 0, 250) : "", $corpo_mensagem);
                    $phone = $core->NOTIFICAZAP_AdjustNumber($client);
                    $phone_whatsapp = $whatsapp->Valida_Number_WhatsApp($phone);
                    $existsPhone = $phone_whatsapp[0]["exists"] ?? NULL;
                    if (!$existsPhone) {
                        $core->NOTIFICAZAP_CadastrarLogFalhas($client->id, "Telefone " . $phone . " do Cliente não é um número de WhatsApp");
                    } else {
                        $vencendoHoje = false;
                        if ($invoice_MPAY->paymentmethod == "paghiper" || $invoice_MPAY->paymentmethod == "paghiper_pix") {
                            $paghiper = WHMCS\Database\Capsule::table("mod_paghiper")->where("order_id", "=", $invoice_MPAY->id)->where("notenviado", "=", "N")->first();
                            if (!$paghiper) {
                            } else {
                                include "notifica.paghiper.php";
                            }
                        } else if ($invoice_MPAY->paymentmethod == "efipixmpay") {
                            $efipaypix = WHMCS\Database\Capsule::table("mod_efimpay")->where("fatura_id", "=", $invoice_MPAY->id)->where("status", "=", "ATIVA")->where("notenviado", "=", "N")->first();
                            if (!$efipaypix) {
                            } else {
                                include "notifica.efipay.php";
                            }
                        } else if (!WHMCS\Database\Capsule::schema()->hasTable("mod_cobrancaasaasmpay")) {
                            $core->NOTIFICAZAP_LogWarning("Tabela mod_cobrancaasaasmpay não foi encontrada", ["fatura" => $invoice_MPAY->id, "cliente" => $client->id, "hook" => "Primeiro Pagamento via cron"]);
                        } else {
                            $cobrancaasaas = WHMCS\Database\Capsule::table("mod_cobrancaasaasmpay")->where("fatura_id", "=", $invoice_MPAY->id)->where("user_id", "=", $invoice_MPAY->userid)->where("status", "=", "unpaid")->where("notenviado", "=", "N")->first();
                            if (!$cobrancaasaas) {
                            } else {
                                include "notifica.asaas.php";
                            }
                        }
                        if (isset($retorno["status"]) && $retorno["status"] == "success") {
                            $core->NOTIFICAZAP_CadastrarLogWhatsapp($client->id, $corpo_mensagem, "sendmessage", $nomedotemplate, $invoice_MPAY->id);
                            $core->NOTIFICAZAP_LogInfo("Mensagem WhatsApp enviada com sucesso", ["cliente id" => $client->id, "phone" => $phone, "status" => $retorno["status"]]);
                        } else {
                            $core->NOTIFICAZAP_LogWarning("Mensagem de WhatsApp não foi enviada", ["phone" => $phone, "cliente" => $client->id, "hook" => "Primeiro Pagamento via cron", "dados do retorno" => json_encode($retorno)]);
                            $core->NOTIFICAZAP_CadastrarLogFalhas($client->id, "Falha ao Enviar Mensagem Primeiro Pagamento no Cron - Phone " . $phone . " - Status: " . json_encode($retorno), $invoice_MPAY->id);
                        }
                    }
                }
            }
        }
    }
});
add_hook("ClientAdd", 1, function ($vars) {
    $clientId = $vars["userid"];
    $client = WHMCS\User\Client::find($clientId);
    if (!$client) {
        return NULL;
    }
    $core = new notificazapCore();
    $onlineServer = $core->NOTIFICAZAP_Servidor_WhatsApp_Online(NZURLSERVIDORWHATSAPP);
    if (!$onlineServer) {
        $core->NOTIFICAZAP_LogWarning("Servidor do WhatsApp está offline.");
        $core->NOTIFICAZAP_CadastrarLogFalhas($clientId, "Servidor do WhatsApp está offline.");
    } else {
        $onlineModulesPay = $core->NOTIFICAZAP_ModulesPay_Online(NZSITEMODULESPAY);
        if ($onlineModulesPay) {
            $license = NOTIFICAZAP_check_license($core->NOTIFICAZAP_Licenca());
            $statusdalicenca = $license["status"];
        } else {
            $core->NOTIFICAZAP_LogWarning("Servidor da MODULES PAY está offline ou inacessível.");
            $statusdalicenca = "Active";
        }
        if ($statusdalicenca !== "Active") {
            $core->NOTIFICAZAP_LogWarning("[NOTIFICA ZAP] Licença inválida ou expirada.");
            $core->NOTIFICAZAP_CadastrarLogFalhas($clientId, "Licença inválida ou expirada.");
        } else {
            $whatsapp = new notificaWhatsAppMpay($core->NOTIFICAZAP_Server(), $core->NOTIFICAZAP_Descriptografar($core->NOTIFICAZAP_KEY()), $core->NOTIFICAZAP_Token_Geral(), $core->NOTIFICAZAP_Telefone_Origem());
            $information = $whatsapp->getInformation();
            $conexao = $whatsapp->connectionStatus();
            if ($information["status"] !== 200 || $conexao["instance"]["state"] !== "open") {
                $core->NOTIFICAZAP_CadastrarLogFalhas($clientId, "Problemas na conexão do WhatsApp.");
            } else {
                $template = $core->NOTIFICAZAP_Pegar_Status_Mensagem_Template("welcome");
                if ($template["status_mensagem"] != "active") {
                    $core->NOTIFICAZAP_CadastrarLogFalhas($clientId, "Template de Bem-Vindo não está ativo.");
                } else {
                    $numberCampo = $core->NOTIFICAZAP_Pega_Info_tbladdonmodules("addon_notificazap_receber_whatsapp");
                    if ($numberCampo) {
                        $notificacao = $core->NOTIFICAZAP_Pega_Info_tblcustomfieldsvalues($numberCampo, $clientId);
                        if ($notificacao == NULL || empty($notificacao)) {
                            $dados = WHMCS\Database\Capsule::table("tblcustomfields")->where("id", $numberCampo)->where("type", "client")->first();
                            if ($dados) {
                                $condicao = explode(",", $dados->fieldoptions);
                                if (isset($condicao[0])) {
                                    $core->NOTIFICAZAP_Inserir_Campo_Personalizado_Receber_WhatsApp($numberCampo, $clientId, $condicao[0]);
                                    $receberNotificacao = $condicao[0];
                                }
                            }
                        } else {
                            $receberNotificacao = $notificacao;
                        }
                    } else {
                        $receberNotificacao = NULL;
                    }
                    if ($receberNotificacao != "Sim") {
                        $core->NOTIFICAZAP_CadastrarLogFalhas($clientId, "O Cliente não deseja receber mensagens de WhatsApp.");
                    } else {
                        $dominioWHMCS = $core->NOTIFICAZAP_Pega_Info_tblconfiguration("SystemURL");
                        $linkAutologin = NULL;
                        if (WHMCS\Database\Capsule::table("tbladdonmodules")->where("module", "autologinmpay")->exists()) {
                            $linkAutologin = $core->NOTIFICAZAP_generateAutologinLink($clientId, $vars["userid"], "painel");
                        }
                        $company = WHMCS\Database\Capsule::table("tblconfiguration")->where("setting", "CompanyName")->value("value");
                        $corpoMensagem = str_replace(["%name%", "%email%", "%assinatura%", "%company%", "%dominiowhmcs%"], [$client->firstname, $client->email, $core->NOTIFICAZAP_Assinatura_WhatsApp(), $company, $linkAutologin ?? $dominioWHMCS], $template["corpo_mensagem"]);
                        $phone = $core->NOTIFICAZAP_AdjustNumber($client);
                        $phone_whatsapp = $whatsapp->Valida_Number_WhatsApp($phone);
                        $existsPhone = $phone_whatsapp[0]["exists"] ?? NULL;
                        if (!$existsPhone) {
                            $core->NOTIFICAZAP_CadastrarLogFalhas($clientId, "Telefone " . $phone . " do Cliente não é um número de WhatsApp");
                            $core->NOTIFICAZAP_LogWarning("[NOTIFICA ZAP] O número do Telefone [" . $phone . "] Não é um número de WhatsApp");
                        } else {
                            $retorno = $core->NOTIFICAZAP_EnviarMensagem($phone, $corpoMensagem);
                            if (isset($retorno["status"]) && $retorno["status"] == "success") {
                                $core->NOTIFICAZAP_CadastrarLogWhatsapp($clientId, $corpoMensagem, "sendmessage", "welcome");
                                $core->NOTIFICAZAP_LogInfo("Notificação enviada com sucesso para o telefone: [" . $phone . "] Cliente #ID " . $clientId);
                            } else {
                                $core->NOTIFICAZAP_LogWarning("Mensagem de WhatsApp não foi enviada", ["phone" => $phone, "cliente" => json_encode($client), "hook" => "cliente novo", "dados do retorno" => json_encode($retorno)]);
                                $core->NOTIFICAZAP_CadastrarLogFalhas($clientId, "Falha ao Enviar Mensagem de Cliente Novo - Phone " . $phone . " Retorno: " . json_encode($retorno));
                            }
                        }
                    }
                }
            }
        }
    }
});
add_hook("AdminHomepage", 1, function ($vars) {
    $msg = "";
    $core = new notificazapCore();
    $onlineServer = $core->NOTIFICAZAP_Servidor_WhatsApp_Online(NZURLSERVIDORWHATSAPP);
    if (!$onlineServer) {
        $msg .= "<div style=\"margin: 10px\" class=\"alert alert-warning text-center\"><b>AVISO!</b> Contate a MODULESPAY, parece que o Servidor da API do WhatsApp está OFFLINE.</div>";
    } else {
        $onlineModulesPay = $core->NOTIFICAZAP_ModulesPay_Online(NZSITEMODULESPAY);
        if (!$onlineModulesPay) {
            $core->NOTIFICAZAP_LogWarning("Servidor da MODULES PAY está offline ou inacessível.");
        }
        $whatsapp = new notificaWhatsAppMpay($core->NOTIFICAZAP_Server(), $core->NOTIFICAZAP_Descriptografar($core->NOTIFICAZAP_KEY()), $core->NOTIFICAZAP_Token_Geral(), $core->NOTIFICAZAP_Telefone_Origem());
        $information = $whatsapp->getInformation();
        if ($information["status"] != "200") {
            $msg .= "<div style=\"margin: 10px\" class=\"alert alert-warning text-center\"><b>AVISO!</b> Contate a MODULESPAY, parece haver algum problema com API do WhatsApp.</div>";
        }
        $conexao = $whatsapp->connectionStatus();
        if ($conexao["instance"]["state"] != "open") {
            $msg .= "<div style=\"margin: 10px\" class=\"alert alert-danger text-center\"><b>AVISO!</b> Parece que seu dispositivo não está conectado. Verifique o QRCode em => Addons => NOTIFICA ZAP.</div>";
        }
        return $msg;
    }
});

?>