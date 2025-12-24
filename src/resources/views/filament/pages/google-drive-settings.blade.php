<x-filament-panels::page>
    {{-- Tabs de navegação --}}
    <div class="mb-6">
        <nav class="flex space-x-4 border-b border-gray-200 dark:border-gray-700">
            <button 
                wire:click="setActiveTab('connection')"
                class="px-4 py-2 text-sm font-medium border-b-2 transition-colors {{ $activeTab === 'connection' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400' }}"
            >
                🔌 Conexão
            </button>
            <button 
                wire:click="setActiveTab('settings')"
                class="px-4 py-2 text-sm font-medium border-b-2 transition-colors {{ $activeTab === 'settings' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400' }}"
            >
                ⚙️ Configurações
            </button>
            <button 
                wire:click="setActiveTab('files')"
                class="px-4 py-2 text-sm font-medium border-b-2 transition-colors {{ $activeTab === 'files' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400' }}"
            >
                📁 Arquivos Recentes
            </button>
            <button 
                wire:click="setActiveTab('activity')"
                class="px-4 py-2 text-sm font-medium border-b-2 transition-colors {{ $activeTab === 'activity' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400' }}"
            >
                📋 Atividades
            </button>
        </nav>
    </div>

    {{-- Tab: Conexão --}}
    @if($activeTab === 'connection')
        <div class="space-y-6">
            {{-- Status da Conexão --}}
            <x-filament::section>
                <x-slot name="heading">
                    Status da Conexão
                </x-slot>

                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        @if($isConnected)
                            <div class="w-16 h-16 rounded-full bg-success-100 dark:bg-success-900/30 flex items-center justify-center">
                                <x-heroicon-o-check-circle class="w-10 h-10 text-success-500" />
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                    ✅ Conectado ao Google Drive
                                </h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    Pasta raiz: {{ $settings->root_folder_name ?? 'N/A' }}
                                </p>
                                @if($settings->last_sync_at)
                                    <p class="text-xs text-gray-400">
                                        Última sincronização: {{ $settings->last_sync_at->diffForHumans() }}
                                    </p>
                                @endif
                            </div>
                        @else
                            <div class="w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
                                <x-heroicon-o-cloud class="w-10 h-10 text-gray-400" />
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                    ⚠️ Não Conectado
                                </h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    Conecte sua conta do Google Drive para sincronizar documentos
                                </p>
                            </div>
                        @endif
                    </div>

                    @if($isConnected && $settings->root_folder_id)
                        <x-filament::button 
                            wire:click="openDriveFolder"
                            color="gray"
                            icon="heroicon-o-arrow-top-right-on-square"
                        >
                            Abrir no Drive
                        </x-filament::button>
                    @endif
                </div>

                @if($settings->last_error)
                    <div class="mt-4 p-4 bg-danger-50 dark:bg-danger-900/20 rounded-lg">
                        <p class="text-sm text-danger-600 dark:text-danger-400">
                            <strong>Último erro:</strong> {{ $settings->last_error }}
                        </p>
                    </div>
                @endif
            </x-filament::section>

            {{-- Estatísticas --}}
            @if($isConnected)
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <x-filament::section>
                        <div class="text-center">
                            <div class="text-3xl font-bold text-primary-600">
                                {{ $stats['total_files'] ?? 0 }}
                            </div>
                            <div class="text-sm text-gray-500">Total de Arquivos</div>
                        </div>
                    </x-filament::section>

                    <x-filament::section>
                        <div class="text-center">
                            <div class="text-3xl font-bold text-success-600">
                                {{ $stats['synced_files'] ?? 0 }}
                            </div>
                            <div class="text-sm text-gray-500">Sincronizados</div>
                        </div>
                    </x-filament::section>

                    <x-filament::section>
                        <div class="text-center">
                            <div class="text-3xl font-bold text-warning-600">
                                {{ $stats['pending_files'] ?? 0 }}
                            </div>
                            <div class="text-sm text-gray-500">Pendentes</div>
                        </div>
                    </x-filament::section>

                    <x-filament::section>
                        <div class="text-center">
                            <div class="text-3xl font-bold text-gray-600">
                                {{ $stats['total_size_formatted'] ?? '0 B' }}
                            </div>
                            <div class="text-sm text-gray-500">Espaço Usado</div>
                        </div>
                    </x-filament::section>
                </div>

                {{-- Barra de progresso --}}
                @if($stats['total_files'] > 0)
                    <x-filament::section>
                        <x-slot name="heading">
                            Taxa de Sincronização
                        </x-slot>
                        
                        <div class="space-y-2">
                            <div class="flex justify-between text-sm">
                                <span>{{ $stats['synced_files'] }} de {{ $stats['total_files'] }} arquivos sincronizados</span>
                                <span class="font-semibold">{{ $stats['sync_rate'] }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                                <div 
                                    class="bg-success-500 h-3 rounded-full transition-all duration-500"
                                    style="width: {{ $stats['sync_rate'] }}%"
                                ></div>
                            </div>
                        </div>
                    </x-filament::section>
                @endif
            @endif

            {{-- Instruções de configuração --}}
            @if(!$isConnected)
                <x-filament::section>
                    <x-slot name="heading">
                        📋 Como Configurar
                    </x-slot>

                    <div class="prose dark:prose-invert max-w-none">
                        <ol class="list-decimal list-inside space-y-3">
                            <li>
                                Acesse o <a href="https://console.cloud.google.com" target="_blank" class="text-primary-600 hover:underline">Google Cloud Console</a>
                            </li>
                            <li>Crie um novo projeto ou selecione um existente</li>
                            <li>Ative a API do Google Drive</li>
                            <li>Configure as credenciais OAuth 2.0</li>
                            <li>Adicione as seguintes variáveis no arquivo <code>.env</code>:
                                <pre class="mt-2 p-3 bg-gray-100 dark:bg-gray-800 rounded text-sm">
GOOGLE_CLIENT_ID=seu_client_id
GOOGLE_CLIENT_SECRET=seu_client_secret
GOOGLE_REDIRECT_URI={{ config('app.url') }}/google/callback</pre>
                            </li>
                            <li>Clique no botão "Conectar Google Drive" acima</li>
                        </ol>
                    </div>
                </x-filament::section>
            @endif
        </div>
    @endif

    {{-- Tab: Configurações --}}
    @if($activeTab === 'settings')
        <div class="space-y-6">
            @if($isConnected)
                <form wire:submit="saveSettings">
                    {{ $this->form }}

                    <div class="mt-6">
                        <x-filament::button type="submit" icon="heroicon-o-check">
                            Salvar Configurações
                        </x-filament::button>
                    </div>
                </form>
            @else
                <x-filament::section>
                    <div class="text-center py-8">
                        <x-heroicon-o-lock-closed class="w-16 h-16 mx-auto text-gray-300" />
                        <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">
                            Conecte sua conta primeiro
                        </h3>
                        <p class="mt-2 text-sm text-gray-500">
                            Para acessar as configurações, você precisa conectar sua conta do Google Drive.
                        </p>
                    </div>
                </x-filament::section>
            @endif
        </div>
    @endif

    {{-- Tab: Arquivos Recentes --}}
    @if($activeTab === 'files')
        <x-filament::section>
            <x-slot name="heading">
                Arquivos Recentes
            </x-slot>

            @if($recentFiles->count() > 0)
                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($recentFiles as $file)
                        <div class="py-4 flex items-center justify-between">
                            <div class="flex items-center space-x-4">
                                <span class="text-2xl">{{ $file->file_icon }}</span>
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-white">
                                        {{ $file->name }}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        {{ $file->formatted_size }} • {{ $file->created_at->diffForHumans() }}
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center space-x-3">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    {{ match($file->sync_status) {
                                        'synced' => 'bg-success-100 text-success-800 dark:bg-success-900/30 dark:text-success-400',
                                        'pending' => 'bg-warning-100 text-warning-800 dark:bg-warning-900/30 dark:text-warning-400',
                                        'failed' => 'bg-danger-100 text-danger-800 dark:bg-danger-900/30 dark:text-danger-400',
                                        default => 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400',
                                    } }}
                                ">
                                    {{ $file->sync_status_badge }}
                                </span>

                                @if($file->web_view_link)
                                    <a 
                                        href="{{ $file->web_view_link }}" 
                                        target="_blank"
                                        class="text-primary-600 hover:text-primary-800"
                                    >
                                        <x-heroicon-o-arrow-top-right-on-square class="w-5 h-5" />
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-4 text-center">
                    <a 
                        href="{{ route('filament.admin.resources.google-drive-files.index') }}" 
                        class="text-sm text-primary-600 hover:underline"
                    >
                        Ver todos os arquivos →
                    </a>
                </div>
            @else
                <div class="text-center py-8">
                    <x-heroicon-o-document class="w-12 h-12 mx-auto text-gray-300" />
                    <p class="mt-2 text-sm text-gray-500">
                        Nenhum arquivo sincronizado ainda.
                    </p>
                </div>
            @endif
        </x-filament::section>
    @endif

    {{-- Tab: Atividades --}}
    @if($activeTab === 'activity')
        <x-filament::section>
            <x-slot name="heading">
                Histórico de Atividades
            </x-slot>

            {{ $this->table }}
        </x-filament::section>
    @endif

    <script>
        document.addEventListener('open-url', (event) => {
            window.open(event.detail.url, '_blank');
        });
    </script>
</x-filament-panels::page>
