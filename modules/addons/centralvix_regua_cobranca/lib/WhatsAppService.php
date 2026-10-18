<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

class CentralVixReguaWhatsAppService
{
    public static function send($phone, $message)
    {
        $endpoint = CentralVixReguaModule::setting('whatsapp_endpoint', '');
        $token = CentralVixReguaModule::setting('whatsapp_token', '');
        $instance = CentralVixReguaModule::setting('whatsapp_instance', 'CentralVix');
        $method = strtoupper(CentralVixReguaModule::setting('whatsapp_method', 'POST'));

        if ($endpoint === '' || $token === '') {
            return ['success' => false, 'message' => 'WhatsApp não configurado.'];
        }

        $phone = preg_replace('/\D/', '', (string) $phone);
        if ($phone === '') {
            $phone = preg_replace('/\D/', '', CentralVixReguaModule::setting('whatsapp_default_phone', ''));
        }
        if ($phone === '') {
            return ['success' => false, 'message' => 'Telefone do cliente não informado.'];
        }

        $payload = [
            'phone' => $phone,
            'message' => CentralVixReguaSecurity::sanitizeText($message, 4000),
            'instance' => $instance,
        ];

        $headers = self::buildHeaders($token);
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE);

        $ch = curl_init($endpoint);
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER => $headers,
        ];

        if ($method === 'GET') {
            $url = $endpoint . (strpos($endpoint, '?') === false ? '?' : '&') . http_build_query($payload);
            curl_setopt($ch, CURLOPT_URL, $url);
        } else {
            curl_setopt($ch, CURLOPT_URL, $endpoint);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        curl_setopt_array($ch, $opts);
        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['success' => false, 'message' => 'Erro HTTP: ' . $error];
        }

        if ($httpCode >= 200 && $httpCode < 300) {
            return ['success' => true, 'message' => 'Enviado.', 'response' => CentralVixReguaSecurity::sanitizeLogResponse((string) $response)];
        }

        return [
            'success' => false,
            'message' => 'HTTP ' . $httpCode,
            'response' => CentralVixReguaSecurity::sanitizeLogResponse((string) $response),
        ];
    }

    public static function test($phone, $message = null)
    {
        $msg = $message ?: 'Teste CentralVix Régua de Cobrança — ' . date('d/m/Y H:i');
        return self::send($phone, $msg);
    }

    private static function buildHeaders($token)
    {
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token,
        ];

        $custom = CentralVixReguaModule::setting('whatsapp_headers', '');
        if ($custom !== '') {
            $decoded = json_decode($custom, true);
            if (is_array($decoded)) {
                $headers = [];
                foreach ($decoded as $k => $v) {
                    if (is_int($k)) {
                        $headers[] = $v;
                    } else {
                        $headers[] = $k . ': ' . $v;
                    }
                }
                $hasAuth = false;
                foreach ($headers as $h) {
                    if (stripos($h, 'Authorization:') === 0) {
                        $hasAuth = true;
                        break;
                    }
                }
                if (!$hasAuth) {
                    $headers[] = 'Authorization: Bearer ' . $token;
                }
            }
        }

        return $headers;
    }
}
