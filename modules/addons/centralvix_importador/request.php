<?php
http_response_code(410);
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'success' => false,
    'message' => 'Este endpoint foi desativado. Atualize o módulo (lib/ModuleHelper.php + centralvix_importador.php), limpe o cache do navegador (Ctrl+F5) e use addonmodules.php.',
], JSON_UNESCAPED_UNICODE);
