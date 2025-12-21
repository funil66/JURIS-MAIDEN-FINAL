<?php

namespace App\Observers;

use App\Models\Service;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Log;

class ServiceObserver
{
    protected WhatsAppService $whatsAppService;

    public function __construct(WhatsAppService $whatsAppService)
    {
        $this->whatsAppService = $whatsAppService;
    }

    /**
     * Handle the Service "created" event.
     */
    public function created(Service $service): void
    {
        // Notificar cliente sobre novo serviço
        if ($this->whatsAppService->isConfigured() && $service->client) {
            try {
                $this->whatsAppService->notifyNewService($service->client, $service);
            } catch (\Exception $e) {
                Log::warning("Erro ao enviar WhatsApp para novo serviço: " . $e->getMessage());
            }
        }
    }

    /**
     * Handle the Service "updated" event.
     */
    public function updated(Service $service): void
    {
        // Verificar se o status mudou
        if ($service->isDirty('status') && $this->whatsAppService->isConfigured() && $service->client) {
            try {
                $this->whatsAppService->notifyServiceStatusUpdate($service->client, $service);
            } catch (\Exception $e) {
                Log::warning("Erro ao enviar WhatsApp para atualização de status: " . $e->getMessage());
            }
        }
    }
}
