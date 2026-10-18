<?php

namespace FmhMonitor;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use Illuminate\Database\Capsule\Manager as Capsule;

class Notifications
{
    private $settings;

    public function __construct($settings = null)
    {
        if ($settings === null) {
            $settingsRaw = Capsule::table('mod_fmh_monitor_settings')->get();
            $this->settings = [];
            foreach ($settingsRaw as $s) {
                $this->settings[$s->setting_key] = $s->setting_value;
            }
        } else {
            $this->settings = $settings;
        }
    }

    private function getSetting($key, $default = '')
    {
        return isset($this->settings[$key]) ? $this->settings[$key] : $default;
    }

    public function sendAlert($service, $status, $timestamp, $customTemplateKey = null, $customReplaces = [])
    {
        $serviceName = $service->name;
        $ip = $service->ip;
        $port = $service->port;
        $time = date('d/m/Y H:i:s', strtotime($timestamp));

        // Pega os templates configurados
        if ($customTemplateKey !== null) {
            $defaultTpl = "Alerta: {nome_servico}";
            if ($customTemplateKey == 'template_alert_ssh') {
                $defaultTpl = "🚨 *NOVO LOGIN SSH DETECTADO* 🚨\nServidor: {nome_servico}\nUsuário: {usuario}\nIP: {ip}\nLocalização: {localidade}\nData/Hora: {hora}";
            }
            $template = $this->getSetting($customTemplateKey, $defaultTpl);
            $subject = "[ALERTA] Monitoramento: {$serviceName}";
        } elseif ($status == 'DOWN') {
            $template = $this->getSetting('template_down', "Serviço {nome_servico} caiu. ({ip}:{porta})");
            $subject = "[DOWN] Alerta de Serviço: {$serviceName}";
        } else {
            $template = $this->getSetting('template_up', "Serviço {nome_servico} voltou. ({ip}:{porta})");
            $subject = "[UP] Serviço Restabelecido: {$serviceName}";
        }

        // Substituição das tags básicas
        $replaces = [
            '{nome_servico}' => $serviceName,
            '{ip}' => $ip,
            '{porta}' => $port,
            '{hora}' => $time,
            '{status}' => $status
        ];
        
        // Merge with custom replaces like {valor}, {top_senders}
        $replaces = array_merge($replaces, $customReplaces);

        $message = str_replace(array_keys($replaces), array_values($replaces), $template);
        
        // Verifica os canais habilitados para este serviço
        $enabledChannels = isset($service->channels) ? explode(',', $service->channels) : ['whatsapp', 'telegram', 'email', 'webhooks'];

        if (in_array('whatsapp', $enabledChannels)) {
            $this->sendWhatsApp($message);
        }
        if (in_array('telegram', $enabledChannels)) {
            $this->sendTelegram($message);
        }
        if (in_array('email', $enabledChannels)) {
            $this->sendEmail($subject, $message);
        }
        if (in_array('webhooks', $enabledChannels)) {
            $this->sendWebhooks($service, $status, $timestamp, $message);
        }

        // Verifica se o serviço está associado a um cliente e se a notificação direta está ativa
        if (!empty($service->client_id) && !empty($service->notify_client)) {
            $this->sendAlertToClient($service->client_id, $message);
        }
    }

    private function sendAlertToClient($clientId, $message)
    {
        if ($this->getSetting('enable_whatsapp', '1') == '0') return false;

        try {
            $client = \WHMCS\Database\Capsule::table('tblclients')->where('id', $clientId)->first();
            if (!$client) {
                return false;
            }

            // Remove todos os caracteres não numéricos
            $phone = preg_replace('/[^0-9]/', '', $client->phonenumber);
            $cc = isset($client->phonecc) ? preg_replace('/[^0-9]/', '', $client->phonecc) : '';

            // Se o WHMCS não tiver o phonecc preenchido, tenta descobrir pelo DDI do país
            if (empty($cc) && !empty($client->country)) {
                $countryToCc = [
                    'BR' => '55',
                    'US' => '1',
                    'PT' => '351',
                    'AO' => '244',
                    'MZ' => '258',
                    'CV' => '238',
                    'AR' => '54',
                    'CO' => '57',
                    'CL' => '56',
                    'PE' => '51',
                    'UY' => '598',
                    'PY' => '595',
                    'MX' => '52',
                    'ES' => '34'
                ];
                $countryCode = strtoupper($client->country);
                if (isset($countryToCc[$countryCode])) {
                    $cc = $countryToCc[$countryCode];
                }
            }

            // Se o WHMCS usa phonecc/country e o número já não começa com ele, nós adicionamos
            if (!empty($cc)) {
                if (strpos($phone, $cc) !== 0) {
                    $phone = $cc . $phone;
                }
            } else {
                // Caso não tenha DDI nenhum, assumimos 55 se tiver 10 ou 11 dígitos
                if (strlen($phone) == 10 || strlen($phone) == 11) {
                    $phone = '55' . $phone;
                }
            }

            // Dispara via WhatsApp se for um número válido (12 ou mais dígitos: 55 + DDD + Numero)
            if (strlen($phone) >= 12) {
                usleep(500000); // 500ms delay to prevent Evolution API rate limit / idempotency drop when sending to Admin and Client concurrently
                $this->dispatchWhatsAppToNumber($phone, $message);
            }
        } catch (\Exception $e) {
            // Fallback silencioso
        }
    }

    private function sendWhatsApp($message)
    {
        if ($this->getSetting('enable_whatsapp', '1') == '0') return false;

        $numbers = $this->getSetting('wa_numbers');
        if (empty($numbers)) {
            return false;
        }

        $numberList = explode(',', $numbers);
        foreach ($numberList as $num) {
            $num = trim($num);
            if (empty($num)) continue;
            $this->dispatchWhatsAppToNumber($num, $message);
        }
    }

    private function dispatchWhatsAppToNumber($number, $message)
    {
        $url = $this->getSetting('wa_api_url');
        $token = $this->getSetting('wa_api_token');

        if (empty($url)) {
            return false;
        }

        $payload = [
            "number" => $number,
            "text" => $message
        ];

        $baseUrl = rtrim($url, '/');
        // Se o usuário já não colocou o endpoint no final, nós adicionamos
        if (strpos($baseUrl, '/send/text') === false && strpos($baseUrl, '/message/sendText') === false) {
            $endpoint = $baseUrl . '/send/text';
        } else {
            $endpoint = $baseUrl;
        }

        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        
        $headers = [
            'Content-Type: application/json'
        ];
        
        if (!empty($token)) {
            $headers[] = 'apikey: ' . $token;
        }
        
        $headers[] = 'User-Agent: FMH-Monitor/2.0';
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_exec($ch);
        curl_close($ch);
    }

    private function sendTelegram($message)
    {
        if ($this->getSetting('enable_telegram', '1') == '0') return false;

        $token = $this->getSetting('telegram_token');
        $chatId = $this->getSetting('telegram_chat_id');

        if (empty($token) || empty($chatId)) {
            return false;
        }

        $url = "https://api.telegram.org/bot{$token}/sendMessage";
        $payload = [
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => 'Markdown'
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_exec($ch);
        curl_close($ch);
    }

    private function sendEmail($subject, $message)
    {
        if ($this->getSetting('enable_email', '1') == '0') return false;

        $emails = $this->getSetting('notify_email');
        if (empty($emails)) {
            return false;
        }

        $emailList = explode(',', $emails);
        $cleanMessage = str_replace(['*', '_'], '', $message);

        foreach ($emailList as $email) {
            $email = trim($email);
            if (empty($email)) continue;

            $headers = "From: WHMCS Monitor <noreply@" . $_SERVER['SERVER_NAME'] . ">\r\n";
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
            
            @mail($email, $subject, $cleanMessage, $headers);
        }
    }

    private function sendWebhooks($service, $status, $timestamp, $formattedMessage)
    {
        $webhooks = Capsule::table('mod_fmh_monitor_webhooks')->where('status', 'ACTIVE')->get();

        foreach ($webhooks as $wh) {
            $url = $wh->url;
            $method = strtoupper($wh->method);
            
            // Tenta processar headers personalizados
            $httpHeaders = ['Content-Type: application/json'];
            if (!empty($wh->headers)) {
                $customHeaders = explode("\n", $wh->headers);
                foreach ($customHeaders as $h) {
                    $h = trim($h);
                    if (!empty($h)) {
                        $httpHeaders[] = $h;
                    }
                }
            }

            // Payload padrão rico que permite integrações flexíveis (zapier, n8n, etc)
            $payload = [
                'event' => 'service_status_changed',
                'service' => [
                    'id' => $service->id,
                    'name' => $service->name,
                    'ip' => $service->ip,
                    'port' => $service->port,
                ],
                'status' => $status,
                'timestamp' => $timestamp,
                'message' => $formattedMessage // Permite que o webhook simplesmente propague o texto já formatado ex: Discord
            ];

            // Para integrações com Discord ou Slack que esperam a chave 'content' ou 'text'
            // enviamos junto por conveniência
            $payload['content'] = $formattedMessage;
            $payload['text'] = $formattedMessage;

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            
            if ($method == 'POST') {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            } else {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
                $url .= '?' . http_build_query($payload);
                curl_setopt($ch, CURLOPT_URL, $url);
            }
            
            curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeaders);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_exec($ch);
            curl_close($ch);
        }
    }
}
