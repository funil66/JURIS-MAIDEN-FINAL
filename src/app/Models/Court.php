<?php

namespace App\Models;

use App\Traits\HasGlobalUid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class Court extends Model
{
    use HasFactory, HasGlobalUid;

    /**
     * Prefixo do UID para Tribunais
     */
    protected static string $uidPrefix = 'TRB';

    protected $fillable = [
        'uid',
        'name',
        'acronym',
        'full_name',
        'type',
        'jurisdiction',
        'state',
        'region',
        'api_type',
        'api_base_url',
        'api_key',
        'api_username',
        'api_password',
        'api_certificate_path',
        'api_certificate_password',
        'supported_operations',
        'request_headers',
        'authentication_config',
        'requests_per_minute',
        'requests_per_day',
        'is_active',
        'is_configured',
        'last_sync_at',
        'last_error_at',
        'last_error_message',
        'metadata',
    ];

    protected $casts = [
        'supported_operations' => 'array',
        'request_headers' => 'array',
        'authentication_config' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
        'is_configured' => 'boolean',
        'last_sync_at' => 'datetime',
        'last_error_at' => 'datetime',
        'requests_per_minute' => 'integer',
        'requests_per_day' => 'integer',
    ];

    protected $hidden = [
        'api_key',
        'api_password',
        'api_certificate_password',
    ];

    /**
     * Tipos de tribunal
     */
    public const TYPE_STF = 'stf';
    public const TYPE_STJ = 'stj';
    public const TYPE_TST = 'tst';
    public const TYPE_TSE = 'tse';
    public const TYPE_STM = 'stm';
    public const TYPE_TRF = 'trf';
    public const TYPE_TRE = 'tre';
    public const TYPE_TRT = 'trt';
    public const TYPE_TJM = 'tjm';
    public const TYPE_TJ = 'tj';
    public const TYPE_1GRAU_FEDERAL = '1grau_federal';
    public const TYPE_1GRAU_ESTADUAL = '1grau_estadual';
    public const TYPE_1GRAU_TRABALHO = '1grau_trabalho';
    public const TYPE_JEF = 'jef';
    public const TYPE_JEC = 'jec';
    public const TYPE_OUTRO = 'outro';

    public const TYPES = [
        self::TYPE_STF => 'Supremo Tribunal Federal',
        self::TYPE_STJ => 'Superior Tribunal de Justiça',
        self::TYPE_TST => 'Tribunal Superior do Trabalho',
        self::TYPE_TSE => 'Tribunal Superior Eleitoral',
        self::TYPE_STM => 'Superior Tribunal Militar',
        self::TYPE_TRF => 'Tribunal Regional Federal',
        self::TYPE_TRE => 'Tribunal Regional Eleitoral',
        self::TYPE_TRT => 'Tribunal Regional do Trabalho',
        self::TYPE_TJM => 'Tribunal de Justiça Militar',
        self::TYPE_TJ => 'Tribunal de Justiça Estadual',
        self::TYPE_1GRAU_FEDERAL => '1º Grau Federal',
        self::TYPE_1GRAU_ESTADUAL => '1º Grau Estadual',
        self::TYPE_1GRAU_TRABALHO => '1º Grau Trabalho',
        self::TYPE_JEF => 'Juizado Especial Federal',
        self::TYPE_JEC => 'Juizado Especial Cível',
        self::TYPE_OUTRO => 'Outro',
    ];

    /**
     * Jurisdições
     */
    public const JURISDICTION_FEDERAL = 'federal';
    public const JURISDICTION_ESTADUAL = 'estadual';
    public const JURISDICTION_TRABALHISTA = 'trabalhista';
    public const JURISDICTION_ELEITORAL = 'eleitoral';
    public const JURISDICTION_MILITAR = 'militar';

    public const JURISDICTIONS = [
        self::JURISDICTION_FEDERAL => 'Justiça Federal',
        self::JURISDICTION_ESTADUAL => 'Justiça Estadual',
        self::JURISDICTION_TRABALHISTA => 'Justiça do Trabalho',
        self::JURISDICTION_ELEITORAL => 'Justiça Eleitoral',
        self::JURISDICTION_MILITAR => 'Justiça Militar',
    ];

    /**
     * Tipos de API
     */
    public const API_DATAJUD = 'datajud';
    public const API_PJE = 'pje';
    public const API_ESAJ = 'esaj';
    public const API_PROJUDI = 'projudi';
    public const API_EPROC = 'eproc';
    public const API_OUTROS = 'outros';

    public const API_TYPES = [
        self::API_DATAJUD => 'DataJud (CNJ)',
        self::API_PJE => 'PJe API',
        self::API_ESAJ => 'e-SAJ',
        self::API_PROJUDI => 'Projudi',
        self::API_EPROC => 'e-Proc',
        self::API_OUTROS => 'Outros',
    ];

    /**
     * Estados brasileiros
     */
    public const STATES = [
        'AC' => 'Acre', 'AL' => 'Alagoas', 'AP' => 'Amapá', 'AM' => 'Amazonas',
        'BA' => 'Bahia', 'CE' => 'Ceará', 'DF' => 'Distrito Federal', 'ES' => 'Espírito Santo',
        'GO' => 'Goiás', 'MA' => 'Maranhão', 'MT' => 'Mato Grosso', 'MS' => 'Mato Grosso do Sul',
        'MG' => 'Minas Gerais', 'PA' => 'Pará', 'PB' => 'Paraíba', 'PR' => 'Paraná',
        'PE' => 'Pernambuco', 'PI' => 'Piauí', 'RJ' => 'Rio de Janeiro', 'RN' => 'Rio Grande do Norte',
        'RS' => 'Rio Grande do Sul', 'RO' => 'Rondônia', 'RR' => 'Roraima', 'SC' => 'Santa Catarina',
        'SP' => 'São Paulo', 'SE' => 'Sergipe', 'TO' => 'Tocantins',
    ];

    /**
     * Mutator: Criptografar chave de API
     */
    public function setApiKeyAttribute($value): void
    {
        $this->attributes['api_key'] = $value ? Crypt::encryptString($value) : null;
    }

    /**
     * Accessor: Descriptografar chave de API
     */
    public function getDecryptedApiKeyAttribute(): ?string
    {
        if (!$this->attributes['api_key']) {
            return null;
        }
        try {
            return Crypt::decryptString($this->attributes['api_key']);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Mutator: Criptografar senha
     */
    public function setApiPasswordAttribute($value): void
    {
        $this->attributes['api_password'] = $value ? Crypt::encryptString($value) : null;
    }

    /**
     * Accessor: Descriptografar senha
     */
    public function getDecryptedApiPasswordAttribute(): ?string
    {
        if (!$this->attributes['api_password']) {
            return null;
        }
        try {
            return Crypt::decryptString($this->attributes['api_password']);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Relacionamento: Consultas realizadas
     */
    public function queries(): HasMany
    {
        return $this->hasMany(CourtQuery::class);
    }

    /**
     * Relacionamento: Movimentações importadas
     */
    public function movements(): HasMany
    {
        return $this->hasMany(CourtMovement::class);
    }

    /**
     * Relacionamento: Agendamentos de sincronização
     */
    public function syncSchedules(): HasMany
    {
        return $this->hasMany(CourtSyncSchedule::class);
    }

    /**
     * Relacionamento: Logs de sincronização
     */
    public function syncLogs(): HasMany
    {
        return $this->hasMany(CourtSyncLog::class);
    }

    /**
     * Accessor: Label do tipo
     */
    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    /**
     * Accessor: Label da jurisdição
     */
    public function getJurisdictionLabelAttribute(): string
    {
        return self::JURISDICTIONS[$this->jurisdiction] ?? $this->jurisdiction;
    }

    /**
     * Accessor: Label do tipo de API
     */
    public function getApiTypeLabelAttribute(): string
    {
        return self::API_TYPES[$this->api_type] ?? $this->api_type;
    }

    /**
     * Accessor: Nome do estado
     */
    public function getStateNameAttribute(): ?string
    {
        return self::STATES[$this->state] ?? $this->state;
    }

    /**
     * Accessor: Nome completo formatado
     */
    public function getDisplayNameAttribute(): string
    {
        return "{$this->acronym} - {$this->name}";
    }

    /**
     * Verificar se a API está configurada
     */
    public function isApiConfigured(): bool
    {
        if (!$this->api_base_url) {
            return false;
        }

        // Verificar credenciais baseado no tipo de API
        switch ($this->api_type) {
            case self::API_DATAJUD:
                return !empty($this->decrypted_api_key);
            case self::API_PJE:
            case self::API_ESAJ:
            case self::API_PROJUDI:
                return !empty($this->api_username) && !empty($this->decrypted_api_password);
            default:
                return true;
        }
    }

    /**
     * Registrar erro de sincronização
     */
    public function logError(string $message): void
    {
        $this->update([
            'last_error_at' => now(),
            'last_error_message' => $message,
        ]);
    }

    /**
     * Registrar sincronização bem-sucedida
     */
    public function logSuccess(): void
    {
        $this->update([
            'last_sync_at' => now(),
            'last_error_at' => null,
            'last_error_message' => null,
        ]);
    }

    /**
     * Scope: Tribunais ativos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Tribunais configurados
     */
    public function scopeConfigured($query)
    {
        return $query->where('is_configured', true);
    }

    /**
     * Scope: Por estado
     */
    public function scopeForState($query, string $state)
    {
        return $query->where('state', $state);
    }

    /**
     * Scope: Por jurisdição
     */
    public function scopeForJurisdiction($query, string $jurisdiction)
    {
        return $query->where('jurisdiction', $jurisdiction);
    }

    /**
     * Accessor: URL base da API (compatibilidade)
     */
    public function getApiEndpointAttribute(): ?string
    {
        return $this->api_base_url;
    }

    /**
     * Obter endpoint de autenticação conforme tipo de API
     */
    public function getAuthEndpoint(): string
    {
        return match ($this->api_type) {
            self::API_DATAJUD => '/api/v1/auth/token',
            self::API_PJE => '/pje/auth/oauth/token',
            self::API_ESAJ => '/esaj/auth/login.json',
            self::API_PROJUDI => '/projudi/api/auth/token',
            self::API_EPROC => '/eproc/ws/auth/token',
            default => '/auth/token',
        };
    }

    /**
     * Obter operações suportadas pelo tipo de API
     */
    public function getSupportedOperationsDefault(): array
    {
        return match ($this->api_type) {
            self::API_DATAJUD => ['movements', 'parties', 'documents', 'hearings', 'details'],
            self::API_PJE => ['movements', 'parties', 'documents', 'hearings'],
            self::API_ESAJ => ['movements', 'parties', 'documents'],
            self::API_PROJUDI => ['movements', 'parties', 'documents', 'hearings'],
            self::API_EPROC => ['movements', 'parties', 'documents', 'hearings'],
            default => ['movements'],
        };
    }
}
