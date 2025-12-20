<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Seção de Notificações --}}
        <x-filament::section>
            <x-slot name="heading">
                🔔 Sistema de Notificações
            </x-slot>
            <x-slot name="description">
                Configure e teste o sistema de notificações por email e no painel.
            </x-slot>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Testar Notificações --}}
                <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                    <h4 class="font-semibold text-blue-700 dark:text-blue-300 mb-3">📧 Testar Envio de Email</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        Envie notificações de teste para verificar se o email está configurado corretamente.
                    </p>
                    <div class="flex flex-wrap gap-2">
                        <x-filament::button wire:click="sendTestServiceReminder" color="primary" size="sm">
                            Testar Lembrete de Serviço
                        </x-filament::button>
                        <x-filament::button wire:click="sendTestPaymentReminder" color="warning" size="sm">
                            Testar Lembrete de Pagamento
                        </x-filament::button>
                    </div>
                </div>

                {{-- Executar Comandos --}}
                <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                    <h4 class="font-semibold text-green-700 dark:text-green-300 mb-3">⚡ Executar Lembretes Manualmente</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        Execute os comandos de lembrete agora (normalmente são executados automaticamente).
                    </p>
                    <div class="flex flex-wrap gap-2">
                        <x-filament::button wire:click="runServiceReminders" color="success" size="sm">
                            Verificar Serviços Amanhã
                        </x-filament::button>
                        <x-filament::button wire:click="runPaymentReminders" color="success" size="sm">
                            Verificar Pagamentos
                        </x-filament::button>
                    </div>
                </div>
            </div>

            <div class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                <x-filament::button wire:click="clearNotifications" color="danger" size="sm" icon="heroicon-o-trash">
                    Limpar Todas as Notificações
                </x-filament::button>
            </div>
        </x-filament::section>

        {{-- Informações do Sistema --}}
        <x-filament::section>
            <x-slot name="heading">
                ℹ️ Informações do Sistema
            </x-slot>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    <div class="text-sm text-gray-500">Versão Laravel</div>
                    <div class="text-lg font-semibold">{{ app()->version() }}</div>
                </div>
                <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    <div class="text-sm text-gray-500">Versão PHP</div>
                    <div class="text-lg font-semibold">{{ phpversion() }}</div>
                </div>
                <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    <div class="text-sm text-gray-500">Ambiente</div>
                    <div class="text-lg font-semibold">{{ config('app.env') }}</div>
                </div>
            </div>
        </x-filament::section>

        {{-- Configuração do Scheduler --}}
        <x-filament::section>
            <x-slot name="heading">
                ⏰ Agendamento Automático
            </x-slot>
            <x-slot name="description">
                Para que os lembretes sejam enviados automaticamente, configure o crontab do servidor.
            </x-slot>

            <div class="p-4 bg-gray-900 text-green-400 rounded-lg font-mono text-sm overflow-x-auto">
                <code>* * * * * cd {{ base_path() }} && php artisan schedule:run >> /dev/null 2>&1</code>
            </div>

            <div class="mt-4 text-sm text-gray-600 dark:text-gray-400">
                <p><strong>Horários configurados:</strong></p>
                <ul class="list-disc list-inside mt-2 space-y-1">
                    <li>📋 <strong>08:00</strong> - Lembrete de serviços agendados para amanhã</li>
                    <li>💰 <strong>09:00</strong> - Lembrete de pagamentos (vencimento em 3 dias + atrasados)</li>
                </ul>
            </div>
        </x-filament::section>

        {{-- Configuração de Email --}}
        <x-filament::section>
            <x-slot name="heading">
                📬 Configuração de Email
            </x-slot>
            <x-slot name="description">
                O sistema usa o Mailpit para testes em desenvolvimento. Configure as variáveis de ambiente para produção.
            </x-slot>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded">
                    <span class="text-gray-500">MAIL_MAILER:</span>
                    <span class="font-semibold ml-2">{{ config('mail.default') }}</span>
                </div>
                <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded">
                    <span class="text-gray-500">MAIL_HOST:</span>
                    <span class="font-semibold ml-2">{{ config('mail.mailers.smtp.host') }}</span>
                </div>
                <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded">
                    <span class="text-gray-500">MAIL_PORT:</span>
                    <span class="font-semibold ml-2">{{ config('mail.mailers.smtp.port') }}</span>
                </div>
                <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded">
                    <span class="text-gray-500">MAIL_FROM:</span>
                    <span class="font-semibold ml-2">{{ config('mail.from.address') }}</span>
                </div>
            </div>

            <div class="mt-4">
                <a href="http://localhost:8025" target="_blank" class="text-primary-600 hover:underline">
                    📧 Abrir Mailpit (localhost:8025) para ver emails de teste
                </a>
            </div>
        </x-filament::section>

        {{-- Seção de Backup --}}
        <x-filament::section>
            <x-slot name="heading">
                💾 Backup do Sistema
            </x-slot>
            <x-slot name="description">
                Gerencie backups do banco de dados e arquivos do sistema.
            </x-slot>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                    <h4 class="font-semibold text-green-700 dark:text-green-300 mb-3">📦 Backup Manual</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        Execute backups manualmente quando necessário.
                    </p>
                    <div class="flex flex-wrap gap-2">
                        <x-filament::button wire:click="runBackupDb" color="success" size="sm">
                            Backup do Banco
                        </x-filament::button>
                        <x-filament::button wire:click="runBackupFull" color="primary" size="sm">
                            Backup Completo
                        </x-filament::button>
                    </div>
                </div>

                <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                    <h4 class="font-semibold text-blue-700 dark:text-blue-300 mb-3">⏰ Backup Automático</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        Horários configurados para backup automático:
                    </p>
                    <ul class="text-sm space-y-1 text-gray-600 dark:text-gray-400">
                        <li>• <strong>03:00</strong> - Backup diário do banco</li>
                        <li>• <strong>04:00 (Dom)</strong> - Backup completo semanal</li>
                        <li>• <strong>05:00 (Dom)</strong> - Limpeza de backups antigos</li>
                    </ul>
                </div>
            </div>

            <div class="mt-4 p-3 bg-gray-50 dark:bg-gray-800 rounded text-sm">
                <span class="text-gray-500">Local dos backups:</span>
                <code class="ml-2 text-primary-600">storage/app/{{ config('backup.backup.name') }}</code>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
