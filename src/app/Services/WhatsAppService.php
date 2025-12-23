<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected string $apiUrl;
    protected string $token;
    protected string $phoneNumberId;
    protected bool $enabled;

    public function __construct()
    {
        $this->apiUrl = config('services.whatsapp.api_url', 'https://graph.facebook.com/v18.0');
        $this->token = config('services.whatsapp.token', '');
        $this->phoneNumberId = config('services.whatsapp.phone_number_id', '');
        $this->enabled = config('services.whatsapp.enabled', false);
    }

    /**
     * Verificar se o serviço está configurado
     */
    public function isConfigured(): bool
    {
        return $this->enabled && !empty($this->token) && !empty($this->phoneNumberId);
    }

    /**
     * Formatar número de telefone para formato WhatsApp
     */
    public function formatPhoneNumber(string $phone): string
    {
        // Remover caracteres não numéricos
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Adicionar código do país (Brasil) se não tiver
        if (strlen($phone) === 10 || strlen($phone) === 11) {
            $phone = '55' . $phone;
        }
        
        return $phone;
    }

    /**
     * Enviar mensagem de texto simples
     */
    public function sendText(string $to, string $message): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'WhatsApp não configurado'];
        }

        try {
            $phone = $this->formatPhoneNumber($to);

            $response = Http::withToken($this->token)
                ->post("{$this->apiUrl}/{$this->phoneNumberId}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to' => $phone,
                    'type' => 'text',
                    'text' => [
                        'body' => $message,
                    ],
                ]);

            if ($response->successful()) {
                Log::info("WhatsApp: Mensagem enviada para {$phone}");
                return [
                    'success' => true,
                    'message_id' => $response->json('messages.0.id'),
                ];
            }

            Log::error("WhatsApp Error: " . $response->body());
            return [
                'success' => false,
                'error' => $response->json('error.message', 'Erro desconhecido'),
            ];
        } catch (\Exception $e) {
            Log::error("WhatsApp Exception: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Enviar mensagem usando template
     */
    public function sendTemplate(string $to, string $templateName, array $components = [], string $language = 'pt_BR'): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'WhatsApp não configurado'];
        }

        try {
            $phone = $this->formatPhoneNumber($to);

            $payload = [
                'messaging_product' => 'whatsapp',
                'to' => $phone,
                'type' => 'template',
                'template' => [
                    'name' => $templateName,
                    'language' => [
                        'code' => $language,
                    ],
                ],
            ];

            if (!empty($components)) {
                $payload['template']['components'] = $components;
            }

            $response = Http::withToken($this->token)
                ->post("{$this->apiUrl}/{$this->phoneNumberId}/messages", $payload);

            if ($response->successful()) {
                Log::info("WhatsApp: Template {$templateName} enviado para {$phone}");
                return [
                    'success' => true,
                    'message_id' => $response->json('messages.0.id'),
                ];
            }

            Log::error("WhatsApp Template Error: " . $response->body());
            return [
                'success' => false,
                'error' => $response->json('error.message', 'Erro desconhecido'),
            ];
        } catch (\Exception $e) {
            Log::error("WhatsApp Template Exception: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Notificar cliente sobre novo serviço
     */
    public function notifyNewService(\App\Models\Client $client, \App\Models\Service $service): array
    {
        $phone = $client->whatsapp ?? $client->phone;
        
        if (empty($phone)) {
            return ['success' => false, 'error' => 'Cliente sem telefone'];
        }

        $message = "🔔 *Novo Serviço Cadastrado*\n\n";
        $message .= "Olá, {$client->name}!\n\n";
        $message .= "Um novo serviço foi registrado para você:\n\n";
        $message .= "📋 *Código:* {$service->code}\n";
        $message .= "📂 *Tipo:* " . ($service->serviceType?->name ?? 'N/A') . "\n";
        
        if ($service->process_number) {
            $message .= "📑 *Processo:* {$service->process_number}\n";
        }
        
        if ($service->scheduled_datetime) {
            $message .= "📅 *Agendado:* " . $service->scheduled_datetime->format('d/m/Y H:i') . "\n";
        }
        
        $message .= "\n✅ Acesse o Portal do Cliente para mais detalhes.";

        return $this->sendText($phone, $message);
    }

    /**
     * Notificar cliente sobre atualização de status do serviço
     */
    public function notifyServiceStatusUpdate(\App\Models\Client $client, \App\Models\Service $service): array
    {
        $phone = $client->whatsapp ?? $client->phone;
        
        if (empty($phone)) {
            return ['success' => false, 'error' => 'Cliente sem telefone'];
        }

        $statusLabels = \App\Models\Service::getStatusOptions();
        $statusLabel = $statusLabels[$service->status] ?? $service->status;

        $emoji = match ($service->status) {
            'pendente' => '⏳',
            'agendado' => '📅',
            'em_andamento' => '🔄',
            'concluido' => '✅',
            'cancelado' => '❌',
            default => '📋',
        };

        $message = "{$emoji} *Atualização de Serviço*\n\n";
        $message .= "Olá, {$client->name}!\n\n";
        $message .= "O status do seu serviço foi atualizado:\n\n";
        $message .= "📋 *Código:* {$service->code}\n";
        $message .= "📊 *Novo Status:* {$statusLabel}\n";
        
        if ($service->status === 'completed' && $service->result_summary) {
            $message .= "\n📝 *Resultado:* {$service->result_summary}\n";
        }

        return $this->sendText($phone, $message);
    }

    /**
     * Enviar lembrete de evento
     */
    public function sendEventReminder(\App\Models\Client $client, \App\Models\Event $event): array
    {
        $phone = $client->whatsapp ?? $client->phone;
        
        if (empty($phone)) {
            return ['success' => false, 'error' => 'Cliente sem telefone'];
        }

        $typeLabels = \App\Models\Event::getTypeOptions();
        $typeLabel = $typeLabels[$event->type] ?? $event->type;

        $emoji = match ($event->type) {
            'hearing' => '⚖️',
            'deadline' => '⏰',
            'meeting' => '🤝',
            'task' => '📌',
            'reminder' => '🔔',
            'appointment' => '📅',
            default => '📋',
        };

        $message = "{$emoji} *Lembrete: {$typeLabel}*\n\n";
        $message .= "Olá, {$client->name}!\n\n";
        $message .= "Você tem um compromisso agendado:\n\n";
        $message .= "📋 *{$event->title}*\n";
        $message .= "📅 *Data:* " . $event->starts_at->format('d/m/Y') . "\n";
        $message .= "🕐 *Horário:* " . $event->starts_at->format('H:i') . "\n";
        
        if ($event->location) {
            $message .= "📍 *Local:* {$event->location}\n";
        }
        
        if ($event->location_address) {
            $message .= "🗺️ *Endereço:* {$event->location_address}\n";
        }

        if ($event->description) {
            $message .= "\n📝 {$event->description}\n";
        }

        return $this->sendText($phone, $message);
    }

    /**
     * Enviar lembrete de pagamento
     */
    public function sendPaymentReminder(\App\Models\Client $client, \App\Models\Transaction $transaction): array
    {
        $phone = $client->whatsapp ?? $client->phone;
        
        if (empty($phone)) {
            return ['success' => false, 'error' => 'Cliente sem telefone'];
        }

        $isOverdue = $transaction->due_date && $transaction->due_date->isPast();
        $emoji = $isOverdue ? '🚨' : '💰';

        $message = "{$emoji} *" . ($isOverdue ? 'Pagamento Atrasado' : 'Lembrete de Pagamento') . "*\n\n";
        $message .= "Olá, {$client->name}!\n\n";
        $message .= "Informamos sobre o seguinte pagamento:\n\n";
        $message .= "📋 *{$transaction->description}*\n";
        $message .= "💵 *Valor:* R$ " . number_format($transaction->amount, 2, ',', '.') . "\n";
        
        if ($transaction->due_date) {
            $message .= "📅 *Vencimento:* " . $transaction->due_date->format('d/m/Y') . "\n";
        }
        
        if ($transaction->service) {
            $message .= "🔗 *Serviço:* {$transaction->service->code}\n";
        }

        if ($isOverdue) {
            $message .= "\n⚠️ Este pagamento está vencido. Por favor, regularize sua situação.";
        }

        return $this->sendText($phone, $message);
    }

    /**
     * Enviar mensagem de boas-vindas
     */
    public function sendWelcomeMessage(\App\Models\Client $client, ?string $portalPassword = null): array
    {
        $phone = $client->whatsapp ?? $client->phone;
        
        if (empty($phone)) {
            return ['success' => false, 'error' => 'Cliente sem telefone'];
        }

        $message = "👋 *Bem-vindo ao LogísticaJus!*\n\n";
        $message .= "Olá, {$client->name}!\n\n";
        $message .= "Seja bem-vindo ao nosso escritório de serviços jurídicos.\n\n";
        
        if ($client->portal_access && $portalPassword) {
            $message .= "🔐 *Acesso ao Portal do Cliente:*\n";
            $message .= "🌐 URL: " . config('app.url') . "/portal\n";
            $message .= "📧 Login: {$client->email}\n";
            $message .= "🔑 Senha: {$portalPassword}\n\n";
            $message .= "_Recomendamos alterar sua senha no primeiro acesso._\n\n";
        }
        
        $message .= "Qualquer dúvida, estamos à disposição! 📲";

        return $this->sendText($phone, $message);
    }

    /**
     * Enviar mensagem genérica personalizada
     */
    public function sendCustomMessage(string $to, string $subject, string $body): array
    {
        $message = "📢 *{$subject}*\n\n";
        $message .= $body;

        return $this->sendText($to, $message);
    }
}
