<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Contract;
use App\Models\Deadline;
use App\Models\Diligence;
use App\Models\GeneratedDocument;
use App\Models\Invoice;
use App\Models\Proceeding;
use App\Models\Process;
use App\Models\Service;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class GlobalSearchService
{
    /**
     * Entidades pesquisáveis e seus atributos
     */
    protected array $searchableEntities = [
        'clients' => [
            'model' => Client::class,
            'label' => 'Clientes',
            'icon' => '👤',
            'color' => 'blue',
            'fields' => ['name', 'email', 'cpf_cnpj', 'phone', 'uid'],
            'route' => 'filament.admin.resources.clients.view',
            'displayField' => 'name',
            'subtitleField' => 'cpf_cnpj',
        ],
        'processes' => [
            'model' => Process::class,
            'label' => 'Processos',
            'icon' => '⚖️',
            'color' => 'purple',
            'fields' => ['uid', 'process_number', 'title', 'court', 'judge_name'],
            'route' => 'filament.admin.resources.processes.view',
            'displayField' => 'title',
            'subtitleField' => 'process_number',
        ],
        'proceedings' => [
            'model' => Proceeding::class,
            'label' => 'Andamentos',
            'icon' => '📋',
            'color' => 'indigo',
            'fields' => ['uid', 'title', 'description'],
            'route' => 'filament.admin.resources.proceedings.view',
            'displayField' => 'title',
            'subtitleField' => 'uid',
        ],
        'diligences' => [
            'model' => Diligence::class,
            'label' => 'Diligências',
            'icon' => '📍',
            'color' => 'orange',
            'fields' => ['uid', 'description', 'location', 'contact_name'],
            'route' => 'filament.admin.resources.diligences.view',
            'displayField' => 'description',
            'subtitleField' => 'uid',
        ],
        'deadlines' => [
            'model' => Deadline::class,
            'label' => 'Prazos',
            'icon' => '⏰',
            'color' => 'red',
            'fields' => ['uid', 'title', 'description'],
            'route' => 'filament.admin.resources.deadlines.view',
            'displayField' => 'title',
            'subtitleField' => 'uid',
        ],
        'contracts' => [
            'model' => Contract::class,
            'label' => 'Contratos',
            'icon' => '📝',
            'color' => 'green',
            'fields' => ['uid', 'title', 'description', 'contract_number'],
            'route' => 'filament.admin.resources.contracts.view',
            'displayField' => 'title',
            'subtitleField' => 'contract_number',
        ],
        'invoices' => [
            'model' => Invoice::class,
            'label' => 'Faturas',
            'icon' => '💰',
            'color' => 'yellow',
            'fields' => ['uid', 'invoice_number', 'description'],
            'route' => 'filament.admin.resources.invoices.view',
            'displayField' => 'invoice_number',
            'subtitleField' => 'uid',
        ],
        'services' => [
            'model' => Service::class,
            'label' => 'Serviços',
            'icon' => '🛠️',
            'color' => 'cyan',
            'fields' => ['uid', 'description', 'location'],
            'route' => 'filament.admin.resources.services.view',
            'displayField' => 'description',
            'subtitleField' => 'uid',
        ],
        'time_entries' => [
            'model' => TimeEntry::class,
            'label' => 'Lançamentos de Tempo',
            'icon' => '⏱️',
            'color' => 'teal',
            'fields' => ['uid', 'description'],
            'route' => 'filament.admin.resources.time-entries.view',
            'displayField' => 'description',
            'subtitleField' => 'uid',
        ],
        'documents' => [
            'model' => GeneratedDocument::class,
            'label' => 'Documentos',
            'icon' => '📄',
            'color' => 'gray',
            'fields' => ['uid', 'title', 'content'],
            'route' => 'filament.admin.resources.generated-documents.view',
            'displayField' => 'title',
            'subtitleField' => 'uid',
        ],
    ];

    /**
     * Realiza busca global em todas as entidades
     */
    public function search(string $query, array $filters = [], int $limit = 50): array
    {
        $query = trim($query);
        
        if (strlen($query) < 2) {
            return [
                'results' => [],
                'total' => 0,
                'query' => $query,
                'entities' => [],
            ];
        }

        $results = [];
        $entityCounts = [];
        $selectedEntities = $filters['entities'] ?? array_keys($this->searchableEntities);

        foreach ($selectedEntities as $entityKey) {
            if (!isset($this->searchableEntities[$entityKey])) {
                continue;
            }

            $config = $this->searchableEntities[$entityKey];
            $entityResults = $this->searchEntity($entityKey, $config, $query, $limit);
            
            $entityCounts[$entityKey] = count($entityResults);
            $results = array_merge($results, $entityResults);
        }

        // Ordenar por relevância
        usort($results, function ($a, $b) {
            return $b['relevance'] <=> $a['relevance'];
        });

        // Aplicar limite global
        $results = array_slice($results, 0, $limit);

        return [
            'results' => $results,
            'total' => count($results),
            'query' => $query,
            'entities' => $entityCounts,
        ];
    }

    /**
     * Busca em uma entidade específica
     */
    protected function searchEntity(string $key, array $config, string $query, int $limit): array
    {
        $modelClass = $config['model'];
        
        if (!class_exists($modelClass)) {
            return [];
        }

        $results = [];
        $queryBuilder = $modelClass::query();

        // Construir busca OR em múltiplos campos
        $queryBuilder->where(function ($q) use ($config, $query) {
            foreach ($config['fields'] as $index => $field) {
                if ($index === 0) {
                    $q->where($field, 'LIKE', "%{$query}%");
                } else {
                    $q->orWhere($field, 'LIKE', "%{$query}%");
                }
            }
        });

        // Limitar resultados por entidade
        $records = $queryBuilder->limit(min($limit, 20))->get();

        foreach ($records as $record) {
            $results[] = $this->formatResult($record, $key, $config, $query);
        }

        return $results;
    }

    /**
     * Formata um resultado de busca
     */
    protected function formatResult($record, string $entityKey, array $config, string $query): array
    {
        $displayValue = $record->{$config['displayField']} ?? 'N/A';
        $subtitleValue = $record->{$config['subtitleField']} ?? '';

        // Calcular relevância
        $relevance = $this->calculateRelevance($record, $config['fields'], $query);

        // Gerar URL
        $url = null;
        try {
            $url = route($config['route'], ['record' => $record->id]);
        } catch (\Exception $e) {
            // Rota pode não existir
        }

        // Destacar termo de busca
        $highlightedDisplay = $this->highlightTerm($displayValue, $query);
        $highlightedSubtitle = $this->highlightTerm($subtitleValue, $query);

        // Obter contexto de onde o termo foi encontrado
        $context = $this->getContext($record, $config['fields'], $query);

        return [
            'id' => $record->id,
            'uid' => $record->uid ?? null,
            'entity' => $entityKey,
            'entity_label' => $config['label'],
            'icon' => $config['icon'],
            'color' => $config['color'],
            'display' => $displayValue,
            'display_highlighted' => $highlightedDisplay,
            'subtitle' => $subtitleValue,
            'subtitle_highlighted' => $highlightedSubtitle,
            'context' => $context,
            'url' => $url,
            'relevance' => $relevance,
            'created_at' => $record->created_at?->format('d/m/Y H:i'),
        ];
    }

    /**
     * Calcula relevância do resultado
     */
    protected function calculateRelevance($record, array $fields, string $query): int
    {
        $relevance = 0;
        $queryLower = Str::lower($query);

        foreach ($fields as $index => $field) {
            $value = Str::lower($record->{$field} ?? '');
            
            // Peso maior para primeiros campos
            $weight = max(10 - $index, 1);
            
            // Match exato
            if ($value === $queryLower) {
                $relevance += 100 * $weight;
            }
            // Começa com o termo
            elseif (Str::startsWith($value, $queryLower)) {
                $relevance += 50 * $weight;
            }
            // Contém o termo
            elseif (Str::contains($value, $queryLower)) {
                $relevance += 25 * $weight;
            }
        }

        // Bonus para UID match
        if (isset($record->uid) && Str::contains(Str::lower($record->uid), $queryLower)) {
            $relevance += 200;
        }

        return $relevance;
    }

    /**
     * Destaca o termo de busca no texto
     */
    protected function highlightTerm(string $text, string $query): string
    {
        if (empty($query) || empty($text)) {
            return $text;
        }

        $pattern = '/(' . preg_quote($query, '/') . ')/i';
        return preg_replace($pattern, '<mark class="bg-yellow-200 dark:bg-yellow-900 px-0.5 rounded">$1</mark>', $text);
    }

    /**
     * Obtém contexto de onde o termo foi encontrado
     */
    protected function getContext($record, array $fields, string $query): ?string
    {
        $queryLower = Str::lower($query);

        foreach ($fields as $field) {
            $value = $record->{$field} ?? '';
            
            if (empty($value) || !is_string($value)) {
                continue;
            }

            $valueLower = Str::lower($value);
            $position = strpos($valueLower, $queryLower);

            if ($position !== false) {
                // Extrair contexto ao redor do termo
                $start = max(0, $position - 30);
                $length = min(strlen($value) - $start, 100);
                $context = substr($value, $start, $length);

                if ($start > 0) {
                    $context = '...' . $context;
                }
                if ($start + $length < strlen($value)) {
                    $context .= '...';
                }

                return $this->highlightTerm($context, $query);
            }
        }

        return null;
    }

    /**
     * Obtém entidades pesquisáveis
     */
    public function getSearchableEntities(): array
    {
        return $this->searchableEntities;
    }

    /**
     * Obtém sugestões de busca recentes
     */
    public function getRecentSearches(int $limit = 5): array
    {
        $userId = auth()->id();
        $cacheKey = "recent_searches_{$userId}";
        
        return Cache::get($cacheKey, []);
    }

    /**
     * Salva busca recente
     */
    public function saveRecentSearch(string $query): void
    {
        $userId = auth()->id();
        $cacheKey = "recent_searches_{$userId}";
        
        $recent = Cache::get($cacheKey, []);
        
        // Remover duplicatas
        $recent = array_filter($recent, fn($item) => $item !== $query);
        
        // Adicionar no início
        array_unshift($recent, $query);
        
        // Limitar a 10 buscas
        $recent = array_slice($recent, 0, 10);
        
        Cache::put($cacheKey, $recent, now()->addDays(30));
    }

    /**
     * Busca rápida por UID
     */
    public function searchByUid(string $uid): ?array
    {
        // Identificar tipo de entidade pelo prefixo
        $prefixes = [
            'CLI' => 'clients',
            'PRC' => 'processes',
            'AND' => 'proceedings',
            'DLG' => 'diligences',
            'PRZ' => 'deadlines',
            'CTR' => 'contracts',
            'FAT' => 'invoices',
            'SRV' => 'services',
            'TIM' => 'time_entries',
            'DOC' => 'documents',
        ];

        $prefix = substr($uid, 0, 3);
        
        if (!isset($prefixes[$prefix])) {
            return null;
        }

        $entityKey = $prefixes[$prefix];
        $config = $this->searchableEntities[$entityKey];
        $modelClass = $config['model'];

        $record = $modelClass::where('uid', $uid)->first();

        if (!$record) {
            return null;
        }

        return $this->formatResult($record, $entityKey, $config, $uid);
    }

    /**
     * Obtém estatísticas de entidades
     */
    public function getEntityStats(): array
    {
        $stats = [];

        foreach ($this->searchableEntities as $key => $config) {
            $modelClass = $config['model'];
            
            if (class_exists($modelClass)) {
                $stats[$key] = [
                    'label' => $config['label'],
                    'icon' => $config['icon'],
                    'color' => $config['color'],
                    'count' => $modelClass::count(),
                ];
            }
        }

        return $stats;
    }
}
