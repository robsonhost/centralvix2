<?php
/*
 * @ https://EasyToYou.eu - IonCube v14 Decoder Online
 * @ PHP 7.4
 * @ Decoder version: 1.0.2
 * @ Release: 10/08/2022
 */

// Decoded file for php version 74.
date_default_timezone_set("America/Sao_Paulo");
include_once __DIR__ . "/../../../init.php";
include_once __DIR__ . "/../../../modules/addons/notificazap/config.php";
include_once __DIR__ . "/../../../modules/addons/notificazap/lib/notificazapCore.php";
if (!isset($_SESSION["adminid"])) {
    exit("Acesso negado");
}
$core = new notificazapCore();
$page = isset($_GET["page"]) ? (int) $_GET["page"] : 1;
$limit = isset($_GET["limit"]) ? (int) $_GET["limit"] : 50;
$client_id = isset($_GET["client_id"]) ? $_GET["client_id"] : "";
$invoice_id = isset($_GET["invoice_id"]) ? $_GET["invoice_id"] : "";
$date_to = isset($_GET["date_to"]) ? $_GET["date_to"] : "";
$offset = ($page - 1) * $limit;
$filters = [];
if (!empty($client_id)) {
    $filters["client_id"] = $client_id;
}
if (!empty($invoice_id)) {
    $filters["invoice_id"] = $invoice_id;
}
if (!empty($date_to)) {
    $filters["date_to"] = $date_to;
}
$logsData = $core->NOTIFICAZAP_GetLogsFalhas($filters, $limit, $offset);
$logs = $logsData["logs"];
$total = $logsData["total"];
$totalPages = ceil($total / $limit);
if (isset($_POST["action"]) && $_POST["action"] === "get_log_details" && isset($_POST["id"])) {
    $logId = (int) $_POST["id"];
    try {
        $log = WHMCS\Database\Capsule::table("mod_notificazap_logs_falhas")->where("id", $logId)->first();
        if ($log) {
            echo json_encode(["success" => true, "log" => $log]);
        } else {
            echo json_encode(["success" => false, "message" => "Log não encontrado"]);
        }
    } catch (Exception $e) {
        echo json_encode(["success" => false, "message" => "Erro ao buscar log: " . $e->getMessage()]);
    }
    exit;
}
if (isset($_POST["action"]) && $_POST["action"] === "delete_logs_by_period" && isset($_POST["date_to"])) {
    $dateTo = $_POST["date_to"];
    try {
        $query = WHMCS\Database\Capsule::table("mod_notificazap_logs_falhas");
        if (!empty($dateTo)) {
            $query->where("created_at", "<=", $dateTo . " 23:59:59");
        }
        $deleted = $query->delete();
        echo json_encode(["success" => true, "message" => $deleted . " logs deletados com sucesso."]);
    } catch (Exception $e) {
        echo json_encode(["success" => false, "message" => "Erro ao deletar logs: " . $e->getMessage()]);
    }
    exit;
}
echo "\r\n<!DOCTYPE html>\r\n<html lang=\"pt-BR\">\r\n<head>\r\n    <meta charset=\"UTF-8\">\r\n    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\r\n    <title>Sistema de Logs de Falhas - NotificaZap</title>\r\n\r\n    <!-- Bootstrap CSS -->\r\n    <link href=\"https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css\" rel=\"stylesheet\">\r\n    <!-- Font Awesome -->\r\n    <link href=\"https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css\" rel=\"stylesheet\">\r\n    <!-- DataTables CSS -->\r\n    <link href=\"https://cdn.datatables.net/1.10.24/css/dataTables.bootstrap4.min.css\" rel=\"stylesheet\">\r\n\r\n    <style>\n        :root {\n            --nz-bg: #f4f7fb;\n            --nz-surface: #ffffff;\n            --nz-border: #d9e2ec;\n            --nz-border-strong: #bdccdb;\n            --nz-text: #243447;\n            --nz-primary: #128c7e;\n            --nz-primary-dark: #075e54;\n            --nz-danger: #d94a4a;\n            --nz-radius: 8px;\n            --nz-shadow: 0 10px 30px rgba(18, 38, 63, .10);\n        }\n        body {\n            background: var(--nz-bg);\n            color: var(--nz-text);\n        }\n        .container-fluid {\n            max-width: 1360px;\n            padding-bottom: 32px;\n        }\n        h2 {\n            color: var(--nz-text);\n            font-weight: 800;\n            letter-spacing: 0;\n        }\n        .d-flex.justify-content-between {\n            background: var(--nz-surface);\n            border: 1px solid var(--nz-border);\n            border-radius: var(--nz-radius);\n            box-shadow: var(--nz-shadow);\n            gap: 12px;\n            padding: 18px;\n        }\n        .btn {\n            border-radius: 7px;\n            font-weight: 800;\n            line-height: 1.25;\n            transition: transform .2s ease, box-shadow .2s ease, background .2s ease;\n        }\n        .btn:hover,\n        .btn:focus {\n            transform: translateY(-1px);\n        }\n        .btn-primary {\n            background: var(--nz-primary);\n            border-color: var(--nz-primary);\n            box-shadow: 0 7px 16px rgba(18, 140, 126, .18);\n        }\n        .btn-primary:hover,\n        .btn-primary:focus {\n            background: var(--nz-primary-dark);\n            border-color: var(--nz-primary-dark);\n        }\n        .btn-danger {\n            background: var(--nz-danger);\n            border-color: var(--nz-danger);\n            box-shadow: 0 7px 16px rgba(217, 74, 74, .16);\n        }\n        .btn-outline-secondary {\n            background: #fff;\n            border-color: var(--nz-border-strong);\n            color: var(--nz-primary-dark);\n        }\n        .log-message {\n            max-width: 400px;\n            word-wrap: break-word;\n        }\n        .filter-card,\n        .card {\n            background: var(--nz-surface);\n            border: 1px solid var(--nz-border);\n            border-radius: var(--nz-radius);\n            box-shadow: var(--nz-shadow);\n            margin-bottom: 20px;\n        }\n        .filter-card {\n            padding: 20px;\n        }\n        .filter-card label {\n            color: #506174;\n            font-size: 12px;\n            font-weight: 800;\n            text-transform: uppercase;\n        }\n        .form-control {\n            border: 1px solid var(--nz-border-strong);\n            border-radius: 7px;\n            box-shadow: none;\n            color: var(--nz-text);\n            min-height: 40px;\n        }\n        .form-control:focus {\n            border-color: var(--nz-primary);\n            box-shadow: 0 0 0 3px rgba(18, 140, 126, .14);\n        }\n        .stats-card {\n            background: linear-gradient(135deg, #d94a4a 0%, #8f2d2d 100%);\n            border: 1px solid rgba(255,255,255,.28);\n            border-radius: var(--nz-radius);\n            box-shadow: 0 10px 24px rgba(18, 38, 63, .12);\n            color: white;\n            margin-bottom: 20px;\n            min-height: 104px;\n            padding: 18px 14px;\n        }\n        .stats-number {\n            font-size: 2em;\n            font-weight: 900;\n            line-height: 1;\n        }\n        .card-header {\n            background: #edf3f8;\n            border-bottom: 1px solid var(--nz-border);\n            color: #405166;\n            font-weight: 800;\n        }\n        .card-header h5 {\n            font-weight: 800;\n            margin: 0;\n        }\n        .table {\n            color: var(--nz-text);\n            margin-bottom: 0;\n        }\n        .table thead th,\n        .table .thead-dark th {\n            background: #243447;\n            border-color: #243447;\n            color: #fff;\n            font-size: 12px;\n            text-transform: uppercase;\n            vertical-align: middle;\n        }\n        .table td {\n            border-top-color: #edf2f7;\n            vertical-align: middle;\n            word-break: break-word;\n        }\n        .table-hover tbody tr:hover {\n            background: #f7fbfa;\n        }\n        .pagination .page-link {\n            border-color: var(--nz-border);\n            color: var(--nz-primary-dark);\n            font-weight: 700;\n        }\n        .pagination .active .page-link {\n            background: var(--nz-primary);\n            border-color: var(--nz-primary);\n        }\n        .modal-content {\n            border: 0;\n            border-radius: var(--nz-radius);\n            box-shadow: var(--nz-shadow);\n        }\n        @media (max-width: 768px) {\n            .container-fluid {\n                padding-left: 12px;\n                padding-right: 12px;\n            }\n            .d-flex.justify-content-between {\n                align-items: stretch !important;\n                flex-direction: column;\n            }\n            .d-flex.justify-content-between > div {\n                display: grid;\n                gap: 8px;\n                grid-template-columns: 1fr;\n            }\n            h2 {\n                font-size: 22px;\n            }\n            .filter-card .form-row > [class*=\"col-\"] {\n                margin-bottom: 12px;\n            }\n            .stats-card {\n                min-height: auto;\n            }\n        }\n    </style>\n</head>\r\n<body>\r\n    <div class=\"container-fluid mt-4\">\r\n        <div class=\"row\">\r\n            <div class=\"col-12\">\r\n                <div class=\"d-flex justify-content-between align-items-center mb-4\">\r\n                    <h2><i class=\"fa fa-exclamation-triangle text-danger\"></i> Sistema de Logs de Falhas - NotificaZap</h2>\r\n                    <div>\r\n                        <button type=\"button\" class=\"btn btn-danger mr-2\" onclick=\"deleteLogsByPeriod()\">\r\n                            <i class=\"fa fa-trash\"></i> Deletar Logs por Período\r\n                        </button>\r\n                        <button type=\"button\" class=\"btn btn-primary\" onclick=\"window.location.reload()\">\r\n                            <i class=\"fa fa-sync\"></i> Atualizar\r\n                        </button>\r\n                    </div>\r\n                </div>\r\n\r\n                <!-- Cards de Estatísticas -->\r\n                <div class=\"row mb-4\">\r\n                    <div class=\"col-md-4\">\r\n                        <div class=\"stats-card text-center\">\r\n                            <div class=\"stats-number\">";
echo $total;
echo "</div>\r\n                            <div>Total de Falhas</div>\r\n                        </div>\r\n                    </div>\r\n                </div>\r\n\r\n                <!-- Filtros -->\r\n                <div class=\"filter-card\">\r\n                    <form method=\"GET\" class=\"form-row\">\r\n                        <div class=\"col-md-3\">\r\n                            <label for=\"client_id\">Cliente ID:</label>\r\n                            <input type=\"number\" name=\"client_id\" id=\"client_id\" class=\"form-control\" placeholder=\"ID do cliente\" value=\"";
echo htmlspecialchars($client_id);
echo "\">\r\n                        </div>\r\n                        <div class=\"col-md-3\">\r\n                            <label for=\"invoice_id\">Invoice ID:</label>\r\n                            <input type=\"number\" name=\"invoice_id\" id=\"invoice_id\" class=\"form-control\" placeholder=\"ID da fatura\" value=\"";
echo htmlspecialchars($invoice_id);
echo "\">\r\n                        </div>\r\n                        <div class=\"col-md-3\">\r\n                            <label for=\"date_to\">Data Até:</label>\r\n                            <input type=\"date\" name=\"date_to\" id=\"date_to\" class=\"form-control\" value=\"";
echo htmlspecialchars($date_to);
echo "\">\r\n                        </div>\r\n                        <div class=\"col-md-2\">\r\n                            <label for=\"limit\">Registros:</label>\r\n                            <select name=\"limit\" id=\"limit\" class=\"form-control\">\r\n                                <option value=\"25\" ";
echo $limit === 25 ? "selected" : "";
echo ">25</option>\r\n                                <option value=\"50\" ";
echo $limit === 50 ? "selected" : "";
echo ">50</option>\r\n                                <option value=\"100\" ";
echo $limit === 100 ? "selected" : "";
echo ">100</option>\r\n                                <option value=\"200\" ";
echo $limit === 200 ? "selected" : "";
echo ">200</option>\r\n                            </select>\r\n                        </div>\r\n                        <div class=\"col-md-1\">\r\n                            <label>&nbsp;</label>\r\n                            <button type=\"submit\" class=\"btn btn-primary btn-block\">\r\n                                <i class=\"fa fa-search\"></i> Filtrar\r\n                            </button>\r\n                        </div>\r\n                    </form>\r\n                </div>\r\n\r\n                <!-- Tabela de Logs de Falhas -->\r\n                <div class=\"card\">\r\n                    <div class=\"card-header\">\r\n                        <h5><i class=\"fa fa-table\"></i> Registros de Falhas</h5>\r\n                    </div>\r\n                    <div class=\"card-body\">\r\n                        <div class=\"table-responsive\">\r\n                            <table class=\"table table-striped table-hover\" id=\"logsTable\">\r\n                                <thead class=\"thead-dark\">\r\n                                    <tr>\r\n                                        <th width=\"5%\">ID</th>\r\n                                        <th width=\"15%\">Data/Hora</th>\r\n                                        <th width=\"30%\">Mensagem</th>\r\n                                        <th width=\"10%\">Cliente</th>\r\n                                        <th width=\"10%\">Invoice</th>\r\n                                        <th width=\"5%\">Ações</th>\r\n                                    </tr>\r\n                                </thead>\r\n                                <tbody>\r\n                                    ";
if (empty($logs)) {
    echo "                                        <tr>\r\n                                            <td colspan=\"6\" class=\"text-center py-4\">\r\n                                                <i class=\"fa fa-inbox fa-3x text-muted\"></i>\r\n                                                <p class=\"mt-2 text-muted\">Nenhum log de falha encontrado</p>\r\n                                            </td>\r\n                                        </tr>\r\n                                    ";
} else {
    echo "                                        ";
    foreach ($logs as $log) {
        echo "                                            <tr>\r\n                                                <td>";
        echo $log->id;
        echo "</td>\r\n                                                <td>\r\n                                                    ";
        echo date("d/m/Y H:i:s", strtotime($log->created_at));
        echo "                                                </td>\r\n                                                <td>\r\n                                                    <div class=\"log-message\" title=\"";
        echo htmlspecialchars($log->message);
        echo "\">\r\n                                                        ";
        echo htmlspecialchars(substr($log->message, 0, 100) . (100 < strlen($log->message) ? "..." : ""));
        echo "                                                    </div>\r\n                                                </td>\r\n                                                <td>\r\n                                                    ";
        if (!empty($log->clientid)) {
            echo "                                                        <span class=\"badge badge-light\">#";
            echo $log->clientid;
            echo "</span>\r\n                                                    ";
        } else {
            echo "                                                        <span class=\"text-muted\">-</span>\r\n                                                    ";
        }
        echo "                                                </td>\r\n                                                <td>\r\n                                                    ";
        if (!empty($log->invoiceid)) {
            echo "                                                        <span class=\"badge badge-info\">#";
            echo $log->invoiceid;
            echo "</span>\r\n                                                    ";
        } else {
            echo "                                                        <span class=\"text-muted\">-</span>\r\n                                                    ";
        }
        echo "                                                </td>\r\n                                                <td>\r\n                                                    <button class=\"btn btn-sm btn-outline-secondary\" onclick=\"viewLogDetails(";
        echo $log->id;
        echo ")\">\r\n                                                        <i class=\"fa fa-eye\"></i>\r\n                                                    </button>\r\n                                                </td>\r\n                                            </tr>\r\n                                        ";
    }
    echo "                                    ";
}
echo "                                </tbody>\r\n                            </table>\r\n                        </div>\r\n\r\n                        <!-- Paginação -->\r\n                        ";
if (1 < $totalPages) {
    echo "                            <nav aria-label=\"Navegação de páginas\">\r\n                                <ul class=\"pagination justify-content-center\">\r\n                                    ";
    if (1 < $page) {
        echo "                                        <li class=\"page-item\">\r\n                                            <a class=\"page-link\" href=\"?";
        echo http_build_query(array_merge($_GET, ["page" => $page - 1]));
        echo "\">\r\n                                                <i class=\"fa fa-chevron-left\"></i> Anterior\r\n                                            </a>\r\n                                        </li>\r\n                                    ";
    }
    echo "\r\n                                    ";
    $start = max(1, $page - 2);
    $end = min($totalPages, $page + 2);
    for ($i = $start; $i <= $end; $i++) {
        echo "                                        <li class=\"page-item ";
        echo $i === $page ? "active" : "";
        echo "\">\r\n                                            <a class=\"page-link\" href=\"?";
        echo http_build_query(array_merge($_GET, ["page" => $i]));
        echo "\">\r\n                                                ";
        echo $i;
        echo "                                            </a>\r\n                                        </li>\r\n                                    ";
    }
    echo "\r\n                                    ";
    if ($page < $totalPages) {
        echo "                                        <li class=\"page-item\">\r\n                                            <a class=\"page-link\" href=\"?";
        echo http_build_query(array_merge($_GET, ["page" => $page + 1]));
        echo "\">\r\n                                                Próxima <i class=\"fa fa-chevron-right\"></i>\r\n                                            </a>\r\n                                        </li>\r\n                                    ";
    }
    echo "                                </ul>\r\n                            </nav>\r\n                        ";
}
echo "                    </div>\r\n                </div>\r\n            </div>\r\n        </div>\r\n    </div>\r\n\r\n    <!-- Modal de Detalhes do Log -->\r\n    <div class=\"modal fade\" id=\"logModal\" tabindex=\"-1\" role=\"dialog\" aria-labelledby=\"logModalLabel\" aria-hidden=\"true\">\r\n        <div class=\"modal-dialog modal-lg\" role=\"document\">\r\n            <div class=\"modal-content\">\r\n                <div class=\"modal-header\">\r\n                    <h5 class=\"modal-title\" id=\"logModalLabel\"><i class=\"fa fa-eye\"></i> Detalhes do Log de Falha</h5>\r\n                    <button type=\"button\" class=\"close\" data-dismiss=\"modal\" aria-label=\"Close\">\r\n                        <span aria-hidden=\"true\">&times;</span>\r\n                    </button>\r\n                </div>\r\n                <div class=\"modal-body\" id=\"logModalBody\">\r\n                    <!-- Conteúdo será preenchido dinamicamente -->\r\n                </div>\r\n                <div class=\"modal-footer\">\r\n                    <button type=\"button\" class=\"btn btn-secondary\" data-dismiss=\"modal\">Fechar</button>\r\n                </div>\r\n            </div>\r\n        </div>\r\n    </div>\r\n\r\n    <!-- Bootstrap JS -->\r\n    <script src=\"https://code.jquery.com/jquery-3.5.1.min.js\"></script>\r\n    <script src=\"https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js\"></script>\r\n\r\n    <script>\r\n        function viewLogDetails(logId) {\r\n            try {\r\n                console.log('viewLogDetails called with ID:', logId);\r\n                const modalBody = document.getElementById('logModalBody');\r\n\r\n                // Buscar dados do log via AJAX\r\n                fetch(window.location.href, {\r\n                    method: 'POST',\r\n                    headers: {\r\n                        'Content-Type': 'application/x-www-form-urlencoded',\r\n                    },\r\n                    body: 'action=get_log_details&id=' + logId\r\n                })\r\n                .then(response => response.json())\r\n                .then(data => {\r\n                    if (data.success) {\r\n                        showLogDetails(data.log);\r\n                    } else {\r\n                        throw new Error(data.message || 'Erro ao buscar dados do log');\r\n                    }\r\n                })\r\n                .catch(error => {\r\n                    console.error('Erro na requisição AJAX:', error);\r\n                    alert('Erro ao buscar detalhes do log: ' + error.message);\r\n                });\r\n            } catch (error) {\r\n                console.error('Erro ao processar detalhes do log:', error);\r\n                alert('Erro ao abrir detalhes do log: ' + error.message);\r\n            }\r\n        }\r\n\r\n        function showLogDetails(log) {\r\n            const modalBody = document.getElementById('logModalBody');\r\n\r\n            let html = `\r\n                <div class=\"row\">\r\n                    <div class=\"col-md-6\">\r\n                        <h6><i class=\"fa fa-info-circle\"></i> Informações Gerais</h6>\r\n                        <table class=\"table table-sm\">\r\n                            <tr>\r\n                                <td><strong>ID:</strong></td>\r\n                                <td>\${log.id}</td>\r\n                            </tr>\r\n                            <tr>\r\n                                <td><strong>Data/Hora:</strong></td>\r\n                                <td>\${new Date(log.created_at).toLocaleString('pt-BR')}</td>\r\n                            </tr>\r\n                            <tr>\r\n                                <td><strong>Cliente ID:</strong></td>\r\n                                <td>\${log.clientid || '-'}</td>\r\n                            </tr>\r\n                            <tr>\r\n                                <td><strong>Invoice ID:</strong></td>\r\n                                <td>\${log.invoiceid || '-'}</td>\r\n                            </tr>\r\n                        </table>\r\n                    </div>\r\n                </div>\r\n                <div class=\"row mt-3\">\r\n                    <div class=\"col-12\">\r\n                        <h6><i class=\"fa fa-comment\"></i> Mensagem de Falha</h6>\r\n                        <div class=\"alert alert-danger\">\r\n                            \${log.message}\r\n                        </div>\r\n                    </div>\r\n                </div>\r\n            `;\r\n\r\n            modalBody.innerHTML = html;\r\n            \$('#logModal').modal('show');\r\n        }\r\n\r\n        function deleteLogsByPeriod() {\r\n            const dateTo = document.getElementById('date_to').value;\r\n            if (!dateTo) {\r\n                alert('Por favor, selecione uma data até para deletar os logs.');\r\n                return;\r\n            }\r\n\r\n            if (confirm('Tem certeza de que deseja deletar todos os logs de falhas até ' + dateTo + '? Esta ação não pode ser desfeita.')) {\r\n                fetch(window.location.href, {\r\n                    method: 'POST',\r\n                    headers: {\r\n                        'Content-Type': 'application/x-www-form-urlencoded',\r\n                    },\r\n                    body: 'action=delete_logs_by_period&date_to=' + dateTo\r\n                })\r\n                .then(response => response.json())\r\n                .then(data => {\r\n                    if (data.success) {\r\n                        alert(data.message);\r\n                        window.location.reload();\r\n                    } else {\r\n                        alert('Erro: ' + data.message);\r\n                    }\r\n                })\r\n                .catch(error => {\r\n                    alert('Erro ao deletar logs: ' + error.message);\r\n                });\r\n            }\r\n        }\r\n    </script>\r\n</body>\r\n</html>\n";

?>