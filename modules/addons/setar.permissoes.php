<?php
/*
 * @ https://EasyToYou.eu - IonCube v14 Decoder Online
 * @ PHP 7.4
 * @ Decoder version: 1.0.2
 * @ Release: 10/08/2022
 */

// Decoded file for php version 74.
define("DIR_PERMISSIONS", 493);
define("FILE_PERMISSIONS", 420);
$resultados = ["diretorios_verificados" => 0, "diretorios_corrigidos" => 0, "arquivos_verificados" => 0, "arquivos_corrigidos" => 0, "erros" => []];
incluircss();
exibircabecalho();
$via_ajax = isset($_GET["ajax"]) && $_GET["ajax"] === "1";
if ($via_ajax) {
    $diretorio_raiz = __DIR__;
    processarpermissoes($diretorio_raiz, $resultados);
    header("Content-Type: application/json");
    echo json_encode($resultados);
    exit;
}
$diretorio_raiz = __DIR__;
processarpermissoes($diretorio_raiz, $resultados);
exibirrelatorio($resultados);
if (PHP_SAPI === "cli") {
    echo PHP_EOL;
    echo "=== RELATÓRIO DE PERMISSÕES - NOTIFICA ZAP ===\n";
    echo "Diretórios verificados: " . $resultados["diretorios_verificados"] . "\n";
    echo "Diretórios corrigidos: " . $resultados["diretorios_corrigidos"] . "\n";
    echo "Arquivos verificados: " . $resultados["arquivos_verificados"] . "\n";
    echo "Arquivos corrigidos: " . $resultados["arquivos_corrigidos"] . "\n";
    if (!empty($resultados["erros"])) {
        echo "\nERROS ENCONTRADOS:\n";
        foreach ($resultados["erros"] as $erro) {
            echo "- " . $erro . "\n";
        }
    }
    echo "\nProcesso concluído!\n";
}
function processarPermissoes($caminho, &$resultados)
{
    if (!file_exists($caminho)) {
        $resultados["erros"][] = "Caminho não encontrado: " . $caminho;
    } else if (is_file($caminho)) {
        $resultados["arquivos_verificados"]++;
        $permissoes_atuais = substr(sprintf("%o", fileperms($caminho)), -4);
        $permissoes_desejadas = sprintf("%04o", FILE_PERMISSIONS);
        $extensao = strtolower(pathinfo($caminho, PATHINFO_EXTENSION));
        $icones = ["php" => "🔷", "js" => "🟨", "css" => "🎨", "html" => "📄", "htm" => "📄", "json" => "📋", "xml" => "📊", "tpl" => "📝", "sql" => "🗄️", "log" => "📜", "md" => "📖"];
        $icone = $icones[$extensao] ?? "📄";
        echo "<div class='item-arquivo'>";
        echo "<span class='icone'>" . $icone . "</span>";
        echo "<div class='info'>";
        echo "<div class='nome'>" . $caminho . "</div>";
        echo "<div class='status'>Verificando... (atual: " . $permissoes_atuais . ")</div>";
        echo "</div>";
        if ($permissoes_atuais !== $permissoes_desejadas) {
            if (chmod($caminho, FILE_PERMISSIONS)) {
                $resultados["arquivos_corrigidos"]++;
                echo "<div class='resultado corrigido'>✅ CORRIGIDO para " . $permissoes_desejadas . "</div>";
            } else {
                $resultados["erros"][] = "Erro ao alterar permissões do arquivo: " . $caminho;
                echo "<div class='resultado erro'>❌ ERRO</div>";
            }
        } else {
            echo "<div class='resultado ok'>✅ OK</div>";
        }
        echo "</div>";
    } else if (is_dir($caminho)) {
        $nome_dir = basename($caminho);
        if (in_array($nome_dir, [".git", ".svn", "node_modules", "__pycache__"])) {
            return NULL;
        }
        $resultados["diretorios_verificados"]++;
        $permissoes_atuais = substr(sprintf("%o", fileperms($caminho)), -4);
        $permissoes_desejadas = sprintf("%04o", DIR_PERMISSIONS);
        $icone = strpos($caminho, "includes") !== false ? "📁" : "🗂️";
        echo "<div class='item-diretorio'>";
        echo "<span class='icone'>" . $icone . "</span>";
        echo "<div class='info'>";
        echo "<div class='nome'>" . $caminho . "</div>";
        echo "<div class='status'>Verificando... (atual: " . $permissoes_atuais . ")</div>";
        echo "</div>";
        if ($permissoes_atuais !== $permissoes_desejadas) {
            if (chmod($caminho, DIR_PERMISSIONS)) {
                $resultados["diretorios_corrigidos"]++;
                echo "<div class='resultado corrigido'>✅ CORRIGIDO para " . $permissoes_desejadas . "</div>";
            } else {
                $resultados["erros"][] = "Erro ao alterar permissões do diretório: " . $caminho;
                echo "<div class='resultado erro'>❌ ERRO</div>";
            }
        } else {
            echo "<div class='resultado ok'>✅ OK</div>";
        }
        echo "</div>";
        if ($handle = opendir($caminho)) {
            while (($item = readdir($handle)) !== false) {
                if ($item != "." && $item != "..") {
                    $caminho_completo = $caminho . DIRECTORY_SEPARATOR . $item;
                    processarPermissoes($caminho_completo, $resultados);
                }
            }
            closedir($handle);
        }
    }
}
function exibirCabecalho()
{
    echo "<div class='header'><div class='container'><h1>🔧 Verificador de Permissões - NotificaZap</h1><p class='subtitle'>Sistema de verificação e correção automática de permissões</p></div></div>";
}
function exibirRelatorio($resultados)
{
    echo "<div class='relatorio'><div class='container'><div class='card'><h2>📊 Relatório Final</h2><div class='stats-grid'><div class='stat-card'><div class='stat-icone'>📁</div><div class='stat-info'>";
    echo "<div class='stat-numero'>" . $resultados["diretorios_verificados"] . "</div>";
    echo "<div class='stat-label'>Diretórios Verificados</div></div></div><div class='stat-card'><div class='stat-icone'>🔧</div><div class='stat-info'>";
    echo "<div class='stat-numero'>" . $resultados["diretorios_corrigidos"] . "</div>";
    echo "<div class='stat-label'>Diretórios Corrigidos</div></div></div><div class='stat-card'><div class='stat-icone'>📄</div><div class='stat-info'>";
    echo "<div class='stat-numero'>" . $resultados["arquivos_verificados"] . "</div>";
    echo "<div class='stat-label'>Arquivos Verificados</div></div></div><div class='stat-card'><div class='stat-icone'>✅</div><div class='stat-info'>";
    echo "<div class='stat-numero'>" . $resultados["arquivos_corrigidos"] . "</div>";
    echo "<div class='stat-label'>Arquivos Corrigidos</div></div></div></div>";
    if (!empty($resultados["erros"])) {
        echo "<div class='erros'><h3>⚠️ Erros Encontrados</h3><ul>";
        foreach ($resultados["erros"] as $erro) {
            echo "<li>" . $erro . "</li>";
        }
        echo "</ul></div>";
    }
    echo "<div class='success-message'><h3>🎉 Processo Concluído!</h3><p>Todas as permissões foram verificadas e corrigidas conforme necessário.</p></div></div></div></div>";
}
function incluirCSS()
{
    echo "<style>\r\n        * {\r\n            margin: 0;\r\n            padding: 0;\r\n            box-sizing: border-box;\r\n        }\r\n\r\n        body {\n            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;\n            background: #f4f7fb;\n            color: #243447;\n            min-height: 100vh;\n            padding: 20px;\n        }\n\r\n        .container {\r\n            max-width: 1200px;\r\n            margin: 0 auto;\r\n        }\r\n\r\n        .header {\n            background: linear-gradient(135deg, #128c7e 0%, #075e54 100%);\n            border-radius: 8px;\n            box-shadow: 0 10px 30px rgba(18, 38, 63, .12);\n            padding: 28px 20px;\n            text-align: center;\n            margin-bottom: 30px;\n        }\n\r\n        .header h1 {\n            color: white;\n            font-size: 2.5rem;\n            margin-bottom: 10px;\n            text-shadow: none;\n        }\n\r\n        .subtitle {\r\n            color: rgba(255,255,255,0.9);\r\n            font-size: 1.1rem;\r\n        }\r\n\r\n        .card {\n            background: white;\n            border: 1px solid #d9e2ec;\n            border-radius: 8px;\n            padding: 30px;\n            box-shadow: 0 10px 30px rgba(18, 38, 63, .10);\n            margin-bottom: 20px;\n        }\n\r\n        .progresso {\r\n            margin-bottom: 30px;\r\n        }\r\n\r\n        .loading {\r\n            text-align: center;\r\n            margin: 20px 0;\r\n        }\r\n\r\n        .spinner {\n            width: 50px;\n            height: 50px;\n            border: 4px solid #f3f3f3;\n            border-top: 4px solid #128c7e;\n            border-radius: 50%;\n            animation: spin 1s linear infinite;\n            margin: 0 auto;\n        }\r\n\r\n        @keyframes spin {\r\n            0% { transform: rotate(0deg); }\r\n            100% { transform: rotate(360deg); }\r\n        }\r\n\r\n        .stats-grid {\r\n            display: grid;\r\n            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));\r\n            gap: 20px;\r\n            margin: 30px 0;\r\n        }\r\n\r\n        .stat-card {\n            background: #fbfdfe;\n            border-radius: 8px;\n            padding: 25px;\n            text-align: center;\n            border: 1px solid #d9e2ec;\n            transition: transform 0.2s ease, box-shadow 0.2s ease;\n        }\n\n        .stat-card:hover {\n            box-shadow: 0 8px 20px rgba(18, 38, 63, .08);\n            transform: translateY(-5px);\n        }\n\r\n        .stat-icone {\r\n            font-size: 2.5rem;\r\n            margin-bottom: 15px;\r\n        }\r\n\r\n        .stat-numero {\r\n            font-size: 2rem;\r\n            font-weight: bold;\r\n            color: #495057;\r\n            margin-bottom: 5px;\r\n        }\r\n\r\n        .stat-label {\r\n            color: #6c757d;\r\n            font-size: 0.9rem;\r\n        }\r\n\r\n        .erros {\r\n            background: #f8d7da;\r\n            border: 1px solid #f5c6cb;\r\n            border-radius: 10px;\r\n            padding: 20px;\r\n            margin: 20px 0;\r\n        }\r\n\r\n        .erros h3 {\r\n            color: #721c24;\r\n            margin-bottom: 15px;\r\n        }\r\n\r\n        .erros ul {\r\n            list-style: none;\r\n        }\r\n\r\n        .erros li {\r\n            color: #721c24;\r\n            margin: 8px 0;\r\n            padding-left: 20px;\r\n            position: relative;\r\n        }\r\n\r\n        .erros li:before {\r\n            content: '❌';\r\n            position: absolute;\r\n            left: 0;\r\n        }\r\n\r\n        .success-message {\n            background: linear-gradient(135deg, #21a67a 0%, #128c7e 100%);\n            color: white;\n            padding: 25px;\n            border-radius: 8px;\n            text-align: center;\n        }\n\r\n        .success-message h3 {\r\n            margin-bottom: 10px;\r\n        }\r\n\r\n        .item-arquivo, .item-diretorio {\n            display: flex;\n            align-items: center;\n            padding: 15px;\n            margin: 8px 0;\n            background: #fbfdfe;\n            border-radius: 8px;\n            border: 1px solid #d9e2ec;\n            border-left: 4px solid #128c7e;\n            box-shadow: 0 4px 12px rgba(18, 38, 63, .04);\n        }\n\n        .item-diretorio {\n            border-left-color: #21a67a;\n            background: #f8fffc;\n        }\n\r\n        .icone {\r\n            font-size: 1.5rem;\r\n            margin-right: 15px;\r\n            width: 30px;\r\n            text-align: center;\r\n        }\r\n\r\n        .info {\r\n            flex: 1;\r\n        }\r\n\r\n        .nome {\r\n            font-weight: 600;\r\n            color: #495057;\r\n            margin-bottom: 5px;\r\n            font-size: 0.9rem;\r\n        }\r\n\r\n        .status {\r\n            color: #6c757d;\r\n            font-size: 0.8rem;\r\n        }\r\n\r\n        .resultado {\r\n            margin-top: 8px;\r\n            font-weight: bold;\r\n            font-size: 0.85rem;\r\n        }\r\n\r\n        .resultado.ok {\r\n            color: #28a745;\r\n        }\r\n\r\n        .resultado.corrigido {\r\n            color: #ffc107;\r\n        }\r\n\r\n        .resultado.erro {\r\n            color: #dc3545;\r\n        }\r\n\r\n        /* Responsividade */\r\n        @media (max-width: 768px) {\r\n            body {\r\n                padding: 10px;\r\n            }\r\n\r\n            .header h1 {\r\n                font-size: 2rem;\r\n            }\r\n\r\n            .card {\r\n                padding: 20px;\r\n            }\r\n\r\n            .stats-grid {\r\n                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));\r\n                gap: 15px;\r\n            }\r\n\r\n            .stat-card {\r\n                padding: 20px;\r\n            }\r\n\r\n            .item-arquivo, .item-diretorio {\r\n                flex-direction: column;\r\n                align-items: flex-start;\r\n                text-align: left;\r\n            }\r\n\r\n            .icone {\r\n                margin-right: 0;\r\n                margin-bottom: 10px;\r\n            }\r\n        }\r\n\r\n        @media (max-width: 480px) {\r\n            .header h1 {\r\n                font-size: 1.5rem;\r\n            }\r\n\r\n            .stats-grid {\r\n                grid-template-columns: 1fr;\r\n            }\r\n        }\r\n    </style>";
}

?>