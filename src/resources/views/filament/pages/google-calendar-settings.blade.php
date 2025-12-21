<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Status da Conexão --}}
        <x-filament::section>
            <x-slot name="heading">
                Status da Conexão
            </x-slot>

            @if($this->isConnected)
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-12 h-12 bg-success-100 dark:bg-success-900 rounded-full">
                        <x-heroicon-o-check-circle class="w-6 h-6 text-success-600 dark:text-success-400" />
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Conectado ao Google Calendar</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            @if($this->calendarId)
                                Calendário: {{ $this->calendarId }}
                            @else
                                Usando calendário principal
                            @endif
                        </p>
                        @if($this->tokenExpires)
                            <p class="text-xs text-gray-400">Token expira em: {{ $this->tokenExpires }}</p>
                        @endif
                    </div>
                </div>
            @else
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-12 h-12 bg-gray-100 dark:bg-gray-800 rounded-full">
                        <x-heroicon-o-x-circle class="w-6 h-6 text-gray-400" />
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Não Conectado</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Conecte sua conta Google para sincronizar eventos e prazos
                        </p>
                    </div>
                </div>
            @endif
        </x-filament::section>

        @if($this->isConnected)
            {{-- Estatísticas de Sincronização --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <x-filament::section>
                    <div class="flex items-center gap-3">
                        <div class="flex items-center justify-center w-10 h-10 bg-success-100 dark:bg-success-900 rounded-lg">
                            <x-heroicon-o-check class="w-5 h-5 text-success-600" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $this->syncedEventsCount }}</p>
                            <p class="text-sm text-gray-500">Sincronizados</p>
                        </div>
                    </div>
                </x-filament::section>

                <x-filament::section>
                    <div class="flex items-center gap-3">
                        <div class="flex items-center justify-center w-10 h-10 bg-warning-100 dark:bg-warning-900 rounded-lg">
                            <x-heroicon-o-clock class="w-5 h-5 text-warning-600" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $this->pendingEventsCount }}</p>
                            <p class="text-sm text-gray-500">Pendentes</p>
                        </div>
                    </div>
                </x-filament::section>

                <x-filament::section>
                    <div class="flex items-center gap-3">
                        <div class="flex items-center justify-center w-10 h-10 bg-danger-100 dark:bg-danger-900 rounded-lg">
                            <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-danger-600" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $this->errorEventsCount }}</p>
                            <p class="text-sm text-gray-500">Com Erros</p>
                        </div>
                    </div>
                </x-filament::section>
            </div>

            {{-- Ações Adicionais --}}
            <x-filament::section>
                <x-slot name="heading">
                    Configurações Adicionais
                </x-slot>

                <div class="space-y-4">
                    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <div>
                            <h4 class="font-medium text-gray-900 dark:text-white">Criar Calendário Dedicado</h4>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Criar um calendário separado "LogísticaJus" para seus eventos jurídicos
                            </p>
                        </div>
                        <x-filament::button
                            wire:click="createCalendar"
                            color="gray"
                            size="sm"
                        >
                            Criar Calendário
                        </x-filament::button>
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
                <h4>Para configurar a integração com Google Calendar:</h4>
                <ol>
                    <li>Acesse o <a href="https://console.cloud.google.com" target="_blank" class="text-primary-600">Google Cloud Console</a></li>
                    <li>Crie um novo projeto ou selecione um existente</li>
                    <li>Ative a <strong>Google Calendar API</strong></li>
                    <li>Configure a <strong>Tela de Consentimento OAuth</strong></li>
                    <li>Crie <strong>Credenciais OAuth 2.0</strong> (tipo: Aplicativo Web)</li>
                    <li>Adicione a URI de redirecionamento: <code>{{ config('app.url') }}/funil/google-calendar/callback</code></li>
                    <li>Copie o <strong>Client ID</strong> e <strong>Client Secret</strong></li>
                    <li>Adicione no arquivo <code>.env</code>:
                        <pre>GOOGLE_CLIENT_ID=seu_client_id
GOOGLE_CLIENT_SECRET=seu_client_secret
GOOGLE_REDIRECT_URI={{ config('app.url') }}/funil/google-calendar/callback</pre>
                    </li>
                </ol>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
