<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Status da Conexão --}}
        <x-filament::section>
            <x-slot name="heading">
                Status da Integração
            </x-slot>

            @if($this->isConfigured)
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-12 h-12 bg-success-100 dark:bg-success-900 rounded-full">
                        <x-heroicon-o-check-circle class="w-6 h-6 text-success-600 dark:text-success-400" />
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">WhatsApp Configurado</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Phone Number ID: {{ $this->phoneNumberId }}
                        </p>
                    </div>
                </div>
            @else
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-12 h-12 bg-gray-100 dark:bg-gray-800 rounded-full">
                        <x-heroicon-o-x-circle class="w-6 h-6 text-gray-400" />
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Não Configurado</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Configure as credenciais do WhatsApp Business API no arquivo .env
                        </p>
                    </div>
                </div>
            @endif
        </x-filament::section>

        @if($this->isConfigured)
            {{-- Enviar Mensagem de Teste --}}
            <x-filament::section>
                <x-slot name="heading">
                    Enviar Mensagem
                </x-slot>
                <x-slot name="description">
                    Envie uma mensagem de teste ou notificação para um cliente
                </x-slot>

                <form wire:submit="sendTestMessage" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Selecionar Cliente
                            </label>
                            <select wire:model="selectedClientId" 
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                <option value="">-- Selecione um cliente --</option>
                                @foreach($this->getClients() as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Ou Informe um Número
                            </label>
                            <input type="text" 
                                   wire:model="customPhone"
                                   placeholder="(11) 99999-9999"
                                   class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Assunto
                        </label>
                        <input type="text" 
                               wire:model="messageSubject"
                               placeholder="Assunto da mensagem (opcional)"
                               class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Mensagem *
                        </label>
                        <textarea wire:model="messageBody"
                                  rows="4"
                                  placeholder="Digite o conteúdo da mensagem..."
                                  required
                                  class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500"></textarea>
                    </div>

                    <div class="flex justify-end">
                        <x-filament::button type="submit" icon="heroicon-o-paper-airplane">
                            Enviar Mensagem
                        </x-filament::button>
                    </div>
                </form>
            </x-filament::section>

            {{-- Notificações Automáticas --}}
            <x-filament::section>
                <x-slot name="heading">
                    Notificações Automáticas
                </x-slot>
                <x-slot name="description">
                    O sistema pode enviar notificações automáticas via WhatsApp
                </x-slot>

                <div class="space-y-3">
                    <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <x-heroicon-o-briefcase class="w-6 h-6 text-primary-500" />
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">Novo Serviço</p>
                            <p class="text-sm text-gray-500">Cliente é notificado quando um novo serviço é cadastrado</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <x-heroicon-o-arrow-path class="w-6 h-6 text-primary-500" />
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">Atualização de Status</p>
                            <p class="text-sm text-gray-500">Cliente é notificado quando o status do serviço muda</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <x-heroicon-o-calendar class="w-6 h-6 text-primary-500" />
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">Lembrete de Evento</p>
                            <p class="text-sm text-gray-500">Cliente recebe lembrete de audiências e compromissos</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <x-heroicon-o-banknotes class="w-6 h-6 text-primary-500" />
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">Lembrete de Pagamento</p>
                            <p class="text-sm text-gray-500">Cliente é avisado sobre pagamentos próximos ou vencidos</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <x-heroicon-o-user-plus class="w-6 h-6 text-primary-500" />
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">Boas-vindas</p>
                            <p class="text-sm text-gray-500">Mensagem de boas-vindas com dados de acesso ao portal</p>
                        </div>
                    </div>
                </div>
            </x-filament::section>
        @endif

        {{-- Instruções de Configuração --}}
        <x-filament::section collapsible collapsed>
            <x-slot name="heading">
                Instruções de Configuração
            </x-slot>

            <div class="prose dark:prose-invert max-w-none">
                <h4>Para configurar a integração com WhatsApp Business API:</h4>
                <ol>
                    <li>Acesse o <a href="https://developers.facebook.com" target="_blank" class="text-primary-600">Meta for Developers</a></li>
                    <li>Crie um novo App ou selecione um existente</li>
                    <li>Adicione o produto <strong>WhatsApp</strong></li>
                    <li>Configure o <strong>WhatsApp Business Account</strong></li>
                    <li>Obtenha o <strong>Phone Number ID</strong> e <strong>Access Token</strong></li>
                    <li>Adicione no arquivo <code>.env</code>:
                        <pre>WHATSAPP_ENABLED=true
WHATSAPP_TOKEN=seu_access_token
WHATSAPP_PHONE_NUMBER_ID=seu_phone_number_id</pre>
                    </li>
                </ol>

                <h4>Importante:</h4>
                <ul>
                    <li>É necessário ter uma conta <strong>WhatsApp Business API</strong></li>
                    <li>Números de teste podem ser usados gratuitamente</li>
                    <li>Para produção, é necessário verificar o business</li>
                    <li>Templates de mensagem precisam ser aprovados pela Meta</li>
                </ul>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
