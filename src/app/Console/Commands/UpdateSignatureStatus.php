<?php

namespace App\Console\Commands;

use App\Services\DigitalSignatureService;
use Illuminate\Console\Command;

class UpdateSignatureStatus extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'signatures:update-status 
                            {--send-reminders : Também enviar lembretes para assinaturas pendentes}';

    /**
     * The console command description.
     */
    protected $description = 'Atualiza status de certificados expirados e solicitações de assinatura';

    /**
     * Execute the console command.
     */
    public function handle(DigitalSignatureService $service): int
    {
        $this->info('=== Atualizando Status de Assinaturas ===');
        $this->newLine();

        // 1. Atualizar certificados expirados
        $this->info('Verificando certificados expirados...');
        $expiredCertificates = $service->updateExpiredCertificates();
        $this->line("  ✓ {$expiredCertificates} certificado(s) marcado(s) como expirado(s)");

        // 2. Notificar sobre certificados expirando
        $expiringCertificates = $service->getExpiringCertificates(30);
        if ($expiringCertificates->count() > 0) {
            $this->warn("  ⚠ {$expiringCertificates->count()} certificado(s) expirando nos próximos 30 dias:");
            foreach ($expiringCertificates as $cert) {
                $this->line("    - {$cert->name}: expira em {$cert->days_remaining} dias ({$cert->valid_until->format('d/m/Y')})");
            }
        }

        $this->newLine();

        // 3. Atualizar solicitações expiradas
        $this->info('Verificando solicitações expiradas...');
        $expiredRequests = $service->updateExpiredRequests();
        $this->line("  ✓ {$expiredRequests} solicitação(ões) marcada(s) como expirada(s)");

        $this->newLine();

        // 4. Enviar lembretes (opcional)
        if ($this->option('send-reminders')) {
            $this->info('Enviando lembretes para assinaturas pendentes...');
            $reminders = $service->sendReminders();
            $this->line("  ✓ {$reminders} lembrete(s) enviado(s)");
        }

        $this->newLine();
        $this->info('=== Processo concluído ===');

        return Command::SUCCESS;
    }
}
