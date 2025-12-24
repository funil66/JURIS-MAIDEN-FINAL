<?php

namespace App\Services;

use App\Models\Court;
use App\Models\CourtQuery;
use App\Models\CourtMovement;
use App\Models\CourtMovementCode;
use App\Models\CourtSyncLog;
use App\Models\CourtSyncSchedule;
use App\Models\Proceeding;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use Exception;

class CourtApiService
{
    /**
     * Cache TTL para tokens (em minutos)
     */
    protected const TOKEN_CACHE_TTL = 55; // Tokens geralmente expiram em 60min

    /**
     * Timeout padrão para requisições (em segundos)
     */
    protected const DEFAULT_TIMEOUT = 30;

    /**
     * Consultar movimentações de um processo
     */
    public function queryProcessMovements(
        Court $court,
        string $processNumber,
        ?int $userId = null
    ): array {
        $query = $this->createQuery(
            CourtQuery::TYPE_MOVEMENTS,
            $processNumber,
            $court,
            $userId
        );

        try {
            $query->markAsProcessing();

            $response = $this->executeQuery($court, $processNumber, 'movements');

            if (!$response['success']) {
                $query->markAsError($response['message']);
                return $response;
            }

            $movements = $this->parseMovements($response['data'], $court);

            $query->markAsCompleted(
                $response['data'],
                count($movements)
            );

            return [
                'success' => true,
                'movements' => $movements,
                'query' => $query,
            ];
        } catch (Exception $e) {
            Log::error('CourtApiService: Erro ao consultar movimentações', [
                'court_id' => $court->id,
                'process_number' => $processNumber,
                'error' => $e->getMessage(),
            ]);

            $query->markAsError($e->getMessage());

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'query' => $query,
            ];
        }
    }

    /**
     * Consultar partes de um processo
     */
    public function queryProcessParties(
        Court $court,
        string $processNumber,
        ?int $userId = null
    ): array {
        $query = $this->createQuery(
            CourtQuery::TYPE_PARTIES,
            $processNumber,
            $court,
            $userId
        );

        try {
            $query->markAsProcessing();

            $response = $this->executeQuery($court, $processNumber, 'parties');

            if (!$response['success']) {
                $query->markAsError($response['message']);
                return $response;
            }

            $query->markAsCompleted($response['data']);

            return [
                'success' => true,
                'parties' => $response['data']['parties'] ?? [],
                'query' => $query,
            ];
        } catch (Exception $e) {
            Log::error('CourtApiService: Erro ao consultar partes', [
                'court_id' => $court->id,
                'process_number' => $processNumber,
                'error' => $e->getMessage(),
            ]);

            $query->markAsError($e->getMessage());

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'query' => $query,
            ];
        }
    }

    /**
     * Consultar documentos de um processo
     */
    public function queryProcessDocuments(
        Court $court,
        string $processNumber,
        ?int $userId = null
    ): array {
        $query = $this->createQuery(
            CourtQuery::TYPE_DOCUMENTS,
            $processNumber,
            $court,
            $userId
        );

        try {
            $query->markAsProcessing();

            $response = $this->executeQuery($court, $processNumber, 'documents');

            if (!$response['success']) {
                $query->markAsError($response['message']);
                return $response;
            }

            $query->markAsCompleted($response['data']);

            return [
                'success' => true,
                'documents' => $response['data']['documents'] ?? [],
                'query' => $query,
            ];
        } catch (Exception $e) {
            Log::error('CourtApiService: Erro ao consultar documentos', [
                'court_id' => $court->id,
                'process_number' => $processNumber,
                'error' => $e->getMessage(),
            ]);

            $query->markAsError($e->getMessage());

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'query' => $query,
            ];
        }
    }

    /**
     * Consultar audiências de um processo
     */
    public function queryProcessHearings(
        Court $court,
        string $processNumber,
        ?int $userId = null
    ): array {
        $query = $this->createQuery(
            CourtQuery::TYPE_HEARINGS,
            $processNumber,
            $court,
            $userId
        );

        try {
            $query->markAsProcessing();

            $response = $this->executeQuery($court, $processNumber, 'hearings');

            if (!$response['success']) {
                $query->markAsError($response['message']);
                return $response;
            }

            $query->markAsCompleted($response['data']);

            return [
                'success' => true,
                'hearings' => $response['data']['hearings'] ?? [],
                'query' => $query,
            ];
        } catch (Exception $e) {
            Log::error('CourtApiService: Erro ao consultar audiências', [
                'court_id' => $court->id,
                'process_number' => $processNumber,
                'error' => $e->getMessage(),
            ]);

            $query->markAsError($e->getMessage());

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'query' => $query,
            ];
        }
    }

    /**
     * Sincronizar movimentações de múltiplos processos
     */
    public function syncMultipleProcesses(
        Court $court,
        array $processNumbers,
        ?CourtSyncSchedule $schedule = null,
        ?int $userId = null
    ): CourtSyncLog {
        $syncLog = CourtSyncLog::start(
            $schedule ? CourtSyncLog::TYPE_SCHEDULED : CourtSyncLog::TYPE_MANUAL,
            $court->id,
            $schedule?->id,
            $userId
        );

        $totalMovementsFound = 0;
        $totalMovementsNew = 0;
        $totalMovementsImported = 0;
        $errorsCount = 0;

        try {
            foreach ($processNumbers as $processNumber) {
                try {
                    $result = $this->queryProcessMovements($court, $processNumber, $userId);

                    if ($result['success']) {
                        $movements = $result['movements'];
                        $totalMovementsFound += count($movements);

                        // Salvar movimentações no banco
                        $saveResult = $this->saveMovements($movements, $court);
                        $totalMovementsNew += $saveResult['new'];
                        $totalMovementsImported += $saveResult['imported'];
                    } else {
                        $errorsCount++;
                    }
                } catch (Exception $e) {
                    $errorsCount++;
                    Log::error('CourtApiService: Erro ao sincronizar processo', [
                        'process_number' => $processNumber,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $syncLog->finish(
                count($processNumbers),
                $totalMovementsFound,
                $totalMovementsNew,
                $totalMovementsImported,
                $errorsCount
            );
        } catch (Exception $e) {
            $syncLog->finishWithError($e->getMessage());
        }

        return $syncLog;
    }

    /**
     * Sincronizar todos os processos ativos
     */
    public function syncAllActiveProcesses(
        Court $court,
        ?CourtSyncSchedule $schedule = null
    ): CourtSyncLog {
        // Buscar todos os processos ativos no tribunal
        $processNumbers = Proceeding::query()
            ->whereHas('process', function ($query) use ($court) {
                $query->where('court_state', $court->state);
            })
            ->whereIn('status', ['pending', 'in_progress'])
            ->pluck('process_number')
            ->unique()
            ->filter()
            ->values()
            ->toArray();

        return $this->syncMultipleProcesses($court, $processNumbers, $schedule);
    }

    /**
     * Executar agendamentos pendentes
     */
    public function runPendingSchedules(): array
    {
        $schedules = CourtSyncSchedule::query()
            ->active()
            ->readyToRun()
            ->with('court')
            ->get();

        $results = [];

        foreach ($schedules as $schedule) {
            if (!$schedule->court || !$schedule->court->is_active) {
                continue;
            }

            try {
                $syncLog = $this->syncAllActiveProcesses($schedule->court, $schedule);
                
                $schedule->update([
                    'last_run_at' => now(),
                    'next_run_at' => $schedule->calculateNextRun(),
                ]);

                $results[] = [
                    'schedule_id' => $schedule->id,
                    'court' => $schedule->court->name,
                    'status' => $syncLog->status,
                    'movements_new' => $syncLog->movements_new,
                ];
            } catch (Exception $e) {
                Log::error('CourtApiService: Erro ao executar agendamento', [
                    'schedule_id' => $schedule->id,
                    'error' => $e->getMessage(),
                ]);

                $results[] = [
                    'schedule_id' => $schedule->id,
                    'court' => $schedule->court->name,
                    'status' => 'error',
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Testar conexão com tribunal
     */
    public function testConnection(Court $court): array
    {
        try {
            $token = $this->getAuthToken($court, true);

            if (!$token) {
                return [
                    'success' => false,
                    'message' => 'Não foi possível obter token de autenticação',
                ];
            }

            // Tentar uma consulta simples para validar
            $response = $this->makeApiRequest($court, 'test', []);

            return [
                'success' => true,
                'message' => 'Conexão estabelecida com sucesso',
                'api_version' => $response['version'] ?? 'N/A',
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Obter estatísticas
     */
    public function getStatistics(?int $courtId = null): array
    {
        $movementsQuery = CourtMovement::query();
        $queriesQuery = CourtQuery::query();
        $syncsQuery = CourtSyncLog::query();

        if ($courtId) {
            $movementsQuery->where('court_id', $courtId);
            $queriesQuery->where('court_id', $courtId);
            $syncsQuery->where('court_id', $courtId);
        }

        return [
            'total_movements' => (clone $movementsQuery)->count(),
            'movements_today' => (clone $movementsQuery)->whereDate('created_at', today())->count(),
            'movements_this_week' => (clone $movementsQuery)->whereBetween('created_at', [now()->startOfWeek(), now()])->count(),
            'pending_import' => (clone $movementsQuery)->where('status', CourtMovement::STATUS_PENDING)->count(),
            'imported' => (clone $movementsQuery)->where('status', CourtMovement::STATUS_IMPORTED)->count(),
            'ignored' => (clone $movementsQuery)->where('status', CourtMovement::STATUS_IGNORED)->count(),
            'total_queries' => (clone $queriesQuery)->count(),
            'queries_today' => (clone $queriesQuery)->whereDate('created_at', today())->count(),
            'successful_queries' => (clone $queriesQuery)->where('status', CourtQuery::STATUS_SUCCESS)->count(),
            'failed_queries' => (clone $queriesQuery)->where('status', CourtQuery::STATUS_ERROR)->count(),
            'total_syncs' => (clone $syncsQuery)->count(),
            'successful_syncs' => (clone $syncsQuery)->where('status', CourtSyncLog::STATUS_SUCCESS)->count(),
            'courts_active' => Court::active()->count(),
            'courts_configured' => Court::whereNotNull('api_key')->orWhereNotNull('api_username')->count(),
        ];
    }

    /**
     * Criar registro de consulta
     */
    protected function createQuery(
        string $type,
        string $processNumber,
        Court $court,
        ?int $userId
    ): CourtQuery {
        return CourtQuery::create([
            'court_id' => $court->id,
            'user_id' => $userId ?? auth()->id(),
            'process_number' => $this->normalizeProcessNumber($processNumber),
            'query_type' => $type,
            'status' => CourtQuery::STATUS_PENDING,
        ]);
    }

    /**
     * Executar consulta na API
     */
    protected function executeQuery(Court $court, string $processNumber, string $type): array
    {
        $normalizedNumber = $this->normalizeProcessNumber($processNumber);

        return match ($court->api_type) {
            Court::API_DATAJUD => $this->queryDataJud($court, $normalizedNumber, $type),
            Court::API_PJE => $this->queryPje($court, $normalizedNumber, $type),
            Court::API_ESAJ => $this->queryEsaj($court, $normalizedNumber, $type),
            Court::API_PROJUDI => $this->queryProjudi($court, $normalizedNumber, $type),
            Court::API_EPROC => $this->queryEproc($court, $normalizedNumber, $type),
            default => throw new Exception('Tipo de API não suportado: ' . $court->api_type),
        };
    }

    /**
     * Consulta via DataJud (CNJ)
     */
    protected function queryDataJud(Court $court, string $processNumber, string $type): array
    {
        $endpoint = match ($type) {
            'movements' => "/api/v1/processos/{$processNumber}/movimentos",
            'parties' => "/api/v1/processos/{$processNumber}/partes",
            'documents' => "/api/v1/processos/{$processNumber}/documentos",
            'hearings' => "/api/v1/processos/{$processNumber}/audiencias",
            default => throw new Exception("Tipo de consulta não suportado: {$type}"),
        };

        $response = $this->makeApiRequest($court, $endpoint, [
            'includeDocuments' => true,
        ]);

        return [
            'success' => true,
            'data' => $response,
        ];
    }

    /**
     * Consulta via PJe
     */
    protected function queryPje(Court $court, string $processNumber, string $type): array
    {
        $endpoint = match ($type) {
            'movements' => "/pje/processo/{$processNumber}/movimentos",
            'parties' => "/pje/processo/{$processNumber}/partes",
            'documents' => "/pje/processo/{$processNumber}/documentos",
            'hearings' => "/pje/processo/{$processNumber}/pautas",
            default => throw new Exception("Tipo de consulta não suportado: {$type}"),
        };

        $response = $this->makeApiRequest($court, $endpoint, []);

        return [
            'success' => true,
            'data' => $response,
        ];
    }

    /**
     * Consulta via e-SAJ
     */
    protected function queryEsaj(Court $court, string $processNumber, string $type): array
    {
        // e-SAJ usa SOAP/XML, aqui fazemos adaptação
        $endpoint = match ($type) {
            'movements' => "/esaj/cpopg/consultarMovimentacoes.json",
            'parties' => "/esaj/cpopg/consultarPartes.json",
            'documents' => "/esaj/cpopg/consultarDocumentos.json",
            'hearings' => "/esaj/cpopg/consultarAudiencias.json",
            default => throw new Exception("Tipo de consulta não suportado: {$type}"),
        };

        $response = $this->makeApiRequest($court, $endpoint, [
            'numeroProcesso' => $processNumber,
        ]);

        return [
            'success' => true,
            'data' => $response,
        ];
    }

    /**
     * Consulta via Projudi
     */
    protected function queryProjudi(Court $court, string $processNumber, string $type): array
    {
        $endpoint = match ($type) {
            'movements' => "/projudi/api/processo/{$processNumber}/andamentos",
            'parties' => "/projudi/api/processo/{$processNumber}/polos",
            'documents' => "/projudi/api/processo/{$processNumber}/pecas",
            'hearings' => "/projudi/api/processo/{$processNumber}/sessoes",
            default => throw new Exception("Tipo de consulta não suportado: {$type}"),
        };

        $response = $this->makeApiRequest($court, $endpoint, []);

        return [
            'success' => true,
            'data' => $response,
        ];
    }

    /**
     * Consulta via e-Proc
     */
    protected function queryEproc(Court $court, string $processNumber, string $type): array
    {
        $endpoint = match ($type) {
            'movements' => "/eproc/ws/processos/{$processNumber}/eventos",
            'parties' => "/eproc/ws/processos/{$processNumber}/participantes",
            'documents' => "/eproc/ws/processos/{$processNumber}/anexos",
            'hearings' => "/eproc/ws/processos/{$processNumber}/audiencias",
            default => throw new Exception("Tipo de consulta não suportado: {$type}"),
        };

        $response = $this->makeApiRequest($court, $endpoint, []);

        return [
            'success' => true,
            'data' => $response,
        ];
    }

    /**
     * Fazer requisição à API
     */
    protected function makeApiRequest(
        Court $court,
        string $endpoint,
        array $params = [],
        string $method = 'GET'
    ): array {
        $baseUrl = rtrim($court->api_endpoint, '/');
        $url = $baseUrl . $endpoint;

        $token = $this->getAuthToken($court);

        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        if ($token) {
            $headers['Authorization'] = "Bearer {$token}";
        }

        if ($court->api_key) {
            $headers['X-API-Key'] = $court->api_key;
        }

        try {
            $request = Http::timeout(self::DEFAULT_TIMEOUT)
                ->withHeaders($headers);

            if ($method === 'GET') {
                $response = $request->get($url, $params);
            } else {
                $response = $request->post($url, $params);
            }

            if (!$response->successful()) {
                throw new Exception(
                    "Erro na API ({$response->status()}): " . $response->body()
                );
            }

            return $response->json();
        } catch (Exception $e) {
            Log::error('CourtApiService: Erro na requisição', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Obter token de autenticação
     */
    protected function getAuthToken(Court $court, bool $forceRefresh = false): ?string
    {
        if (!$court->api_username && !$court->api_key) {
            return null;
        }

        $cacheKey = "court_token_{$court->id}";

        if (!$forceRefresh) {
            $cached = Cache::get($cacheKey);
            if ($cached) {
                return $cached;
            }
        }

        try {
            $baseUrl = rtrim($court->api_endpoint, '/');
            $authEndpoint = $court->getAuthEndpoint();

            $response = Http::timeout(15)
                ->post($baseUrl . $authEndpoint, [
                    'username' => $court->api_username,
                    'password' => $court->api_password,
                    'grant_type' => 'password',
                ]);

            if (!$response->successful()) {
                throw new Exception('Falha na autenticação: ' . $response->status());
            }

            $data = $response->json();
            $token = $data['access_token'] ?? $data['token'] ?? null;

            if ($token) {
                Cache::put($cacheKey, $token, now()->addMinutes(self::TOKEN_CACHE_TTL));
            }

            return $token;
        } catch (Exception $e) {
            Log::error('CourtApiService: Erro ao obter token', [
                'court_id' => $court->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Parsear movimentações da resposta da API
     */
    protected function parseMovements(array $data, Court $court): array
    {
        $movements = [];

        // Campo pode variar conforme API
        $movementsList = $data['movimentos'] 
            ?? $data['movements'] 
            ?? $data['andamentos'] 
            ?? $data['eventos'] 
            ?? [];

        foreach ($movementsList as $item) {
            $movements[] = [
                'process_number' => $data['numeroProcesso'] ?? $data['process_number'] ?? null,
                'movement_date' => $this->parseDate($item['data'] ?? $item['date'] ?? $item['dataHora'] ?? null),
                'movement_code' => $item['codigo'] ?? $item['code'] ?? null,
                'movement_name' => $item['nome'] ?? $item['name'] ?? $item['descricao'] ?? null,
                'movement_description' => $item['descricao'] ?? $item['description'] ?? $item['complemento'] ?? null,
                'court_origin' => $item['origem'] ?? $item['vara'] ?? null,
                'raw_data' => $item,
            ];
        }

        return $movements;
    }

    /**
     * Salvar movimentações no banco
     */
    protected function saveMovements(array $movements, Court $court): array
    {
        $newCount = 0;
        $importedCount = 0;

        foreach ($movements as $movementData) {
            // Verificar se já existe
            $hash = CourtMovement::generateHash($movementData);
            $existing = CourtMovement::where('hash', $hash)->first();

            if ($existing) {
                continue;
            }

            // Buscar ou criar código de movimentação
            $movementCode = null;
            if ($movementData['movement_code']) {
                $movementCode = CourtMovementCode::findOrCreateFromApi(
                    $movementData['movement_code'],
                    $movementData['movement_name'] ?? 'Movimentação',
                    $court->id
                );
            }

            // Criar movimentação
            CourtMovement::create([
                'court_id' => $court->id,
                'court_movement_code_id' => $movementCode?->id,
                'process_number' => $movementData['process_number'],
                'movement_date' => $movementData['movement_date'],
                'movement_code' => $movementData['movement_code'],
                'movement_name' => $movementData['movement_name'],
                'movement_description' => $movementData['movement_description'],
                'court_origin' => $movementData['court_origin'],
                'raw_data' => $movementData['raw_data'],
                'hash' => $hash,
                'status' => CourtMovement::STATUS_PENDING,
            ]);

            $newCount++;
        }

        return [
            'new' => $newCount,
            'imported' => $importedCount,
        ];
    }

    /**
     * Normalizar número de processo (formato CNJ)
     */
    protected function normalizeProcessNumber(string $number): string
    {
        // Remove caracteres não numéricos
        $clean = preg_replace('/[^0-9]/', '', $number);

        // Se já tem 20 dígitos, formata no padrão CNJ
        if (strlen($clean) === 20) {
            return sprintf(
                '%s-%s.%s.%s.%s.%s',
                substr($clean, 0, 7),
                substr($clean, 7, 2),
                substr($clean, 9, 4),
                substr($clean, 13, 1),
                substr($clean, 14, 2),
                substr($clean, 16, 4)
            );
        }

        return $number;
    }

    /**
     * Parsear data da API
     */
    protected function parseDate(?string $dateString): ?Carbon
    {
        if (!$dateString) {
            return null;
        }

        try {
            // Tenta vários formatos comuns
            $formats = [
                'Y-m-d\TH:i:s',
                'Y-m-d H:i:s',
                'd/m/Y H:i:s',
                'd/m/Y H:i',
                'd/m/Y',
                'Y-m-d',
            ];

            foreach ($formats as $format) {
                try {
                    return Carbon::createFromFormat($format, $dateString);
                } catch (Exception $e) {
                    continue;
                }
            }

            return Carbon::parse($dateString);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Importar movimentação para andamento do processo
     */
    public function importMovementToProceeding(
        CourtMovement $movement,
        ?int $processId = null
    ): ?Proceeding {
        if ($movement->status === CourtMovement::STATUS_IMPORTED) {
            return $movement->proceeding;
        }

        // Buscar processo pelo número
        if (!$processId && $movement->process_number) {
            $process = \App\Models\Process::query()
                ->where('number', 'like', '%' . preg_replace('/[^0-9]/', '', $movement->process_number) . '%')
                ->first();

            $processId = $process?->id;
        }

        if (!$processId) {
            return null;
        }

        return $movement->importToProceeding($processId);
    }

    /**
     * Importar múltiplas movimentações
     */
    public function importMultipleMovements(array $movementIds): array
    {
        $imported = 0;
        $failed = 0;

        foreach ($movementIds as $id) {
            $movement = CourtMovement::find($id);
            
            if (!$movement) {
                $failed++;
                continue;
            }

            $proceeding = $this->importMovementToProceeding($movement);

            if ($proceeding) {
                $imported++;
            } else {
                $failed++;
            }
        }

        return [
            'imported' => $imported,
            'failed' => $failed,
        ];
    }
}
