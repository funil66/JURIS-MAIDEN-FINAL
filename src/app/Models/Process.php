<?php

namespace App\Models;

use App\Traits\HasGlobalUid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Process extends Model
{
    use HasFactory, SoftDeletes, LogsActivity, HasGlobalUid;

    /**
     * Prefixo do UID para Processos
     */
    public static function getUidPrefix(): string
    {
        return 'PRC';
    }

    protected $fillable = [
        'client_id',
        'parent_id',
        'responsible_user_id',
        'cnj_number',
        'old_number',
        'title',
        'court',
        'jurisdiction',
        'court_division',
        'court_section',
        'state',
        'plaintiff',
        'defendant',
        'client_role',
        'matter_type',
        'action_type',
        'procedure_type',
        'subject',
        'distribution_date',
        'filing_date',
        'closing_date',
        'transit_date',
        'case_value',
        'contingency_value',
        'sentence_value',
        'status',
        'phase',
        'instance',
        'external_lawyer',
        'external_lawyer_oab',
        'external_lawyer_email',
        'external_lawyer_phone',
        'opposing_lawyer',
        'opposing_lawyer_oab',
        'strategy',
        'risk_assessment',
        'notes',
        'is_urgent',
        'is_confidential',
        'is_pro_bono',
        'has_injunction',
        'internal_code',
        'folder_location',
    ];

    protected $casts = [
        'distribution_date' => 'date',
        'filing_date' => 'date',
        'closing_date' => 'date',
        'transit_date' => 'date',
        'case_value' => 'decimal:2',
        'contingency_value' => 'decimal:2',
        'sentence_value' => 'decimal:2',
        'is_urgent' => 'boolean',
        'is_confidential' => 'boolean',
        'is_pro_bono' => 'boolean',
        'has_injunction' => 'boolean',
    ];

    /**
     * Activity Log Options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'phase', 'instance', 'title'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // ==========================================
    // RELACIONAMENTOS
    // ==========================================

    /**
     * Cliente do processo
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Processo pai (para subprocessos)
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Process::class, 'parent_id');
    }

    /**
     * Subprocessos
     */
    public function children(): HasMany
    {
        return $this->hasMany(Process::class, 'parent_id');
    }

    /**
     * Alias para subprocessos
     */
    public function subprocesses(): HasMany
    {
        return $this->children();
    }

    /**
     * Usuário responsável
     */
    public function responsibleUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    /**
     * Serviços vinculados a este processo
     */
    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    /**
     * Eventos vinculados a este processo
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    /**
     * Transações vinculadas a este processo
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Andamentos do processo
     */
    public function proceedings(): HasMany
    {
        return $this->hasMany(Proceeding::class);
    }

    /**
     * Diligências do processo
     */
    public function diligences(): HasMany
    {
        return $this->hasMany(Diligence::class);
    }

    /**
     * Lançamentos de tempo do processo
     */
    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }

    /**
     * Prazos do processo
     */
    public function deadlines(): HasMany
    {
        return $this->hasMany(Deadline::class);
    }

    // ==========================================
    // SCOPES
    // ==========================================

    /**
     * Processos ativos
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Processos principais (não subprocessos)
     */
    public function scopeMain($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Processos urgentes
     */
    public function scopeUrgent($query)
    {
        return $query->where('is_urgent', true);
    }

    /**
     * Por status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Por fase
     */
    public function scopeByPhase($query, string $phase)
    {
        return $query->where('phase', $phase);
    }

    /**
     * Por responsável
     */
    public function scopeByResponsible($query, int $userId)
    {
        return $query->where('responsible_user_id', $userId);
    }

    /**
     * Busca por número (CNJ / antigo / internal_code)
     */
    public function scopeByNumber($query, string $value)
    {
        $clean = preg_replace('/[^0-9]/', '', $value);

        return $query->where(function ($q) use ($clean) {
            $q->where('cnj_number', 'like', "%{$clean}%")
                ->orWhere('old_number', 'like', "%{$clean}%")
                ->orWhere('internal_code', 'like', "%{$clean}%");
        });
    }

    /**
     * Acessor genérico para `number` (compatibilidade com views/relations)
     */
    public function getNumberAttribute(): ?string
    {
        return $this->cnj_number ?? $this->old_number ?? $this->internal_code ?? null;
    }

    // ==========================================
    // MÉTODOS AUXILIARES
    // ==========================================

    /**
     * Verifica se é um subprocesso
     */
    public function isSubprocess(): bool
    {
        return $this->parent_id !== null;
    }

    /**
     * Verifica se tem subprocessos
     */
    public function hasSubprocesses(): bool
    {
        return $this->children()->exists();
    }

    /**
     * Verifica se está encerrado
     */
    public function isClosed(): bool
    {
        return in_array($this->status, [
            'archived',
            'closed_won',
            'closed_lost',
            'closed_settled',
            'closed_other'
        ]);
    }

    /**
     * Retorna o processo raiz (pai mais alto)
     */
    public function getRootProcess(): Process
    {
        if (!$this->isSubprocess()) {
            return $this;
        }

        $parent = $this->parent;
        while ($parent && $parent->parent_id !== null) {
            $parent = $parent->parent;
        }

        return $parent ?? $this;
    }

    /**
     * Formata o número CNJ
     */
    public function getFormattedCnjAttribute(): ?string
    {
        if (empty($this->cnj_number)) {
            return null;
        }

        // Se já estiver formatado, retorna
        if (preg_match('/^\d{7}-\d{2}\.\d{4}\.\d\.\d{2}\.\d{4}$/', $this->cnj_number)) {
            return $this->cnj_number;
        }

        // Remove caracteres não numéricos
        $numbers = preg_replace('/[^0-9]/', '', $this->cnj_number);

        if (strlen($numbers) !== 20) {
            return $this->cnj_number;
        }

        // Formata: NNNNNNN-DD.AAAA.J.TR.OOOO
        return sprintf(
            '%s-%s.%s.%s.%s.%s',
            substr($numbers, 0, 7),
            substr($numbers, 7, 2),
            substr($numbers, 9, 4),
            substr($numbers, 13, 1),
            substr($numbers, 14, 2),
            substr($numbers, 16, 4)
        );
    }

    /**
     * Retorna label do status
     */
    public function getStatusLabelAttribute(): string
    {
        return self::getStatusOptions()[$this->status] ?? $this->status;
    }

    /**
     * Retorna label da fase
     */
    public function getPhaseLabelAttribute(): string
    {
        return self::getPhaseOptions()[$this->phase] ?? $this->phase;
    }

    /**
     * Retorna label da instância
     */
    public function getInstanceLabelAttribute(): string
    {
        return self::getInstanceOptions()[$this->instance] ?? $this->instance;
    }

    // ==========================================
    // OPTIONS ESTÁTICAS
    // ==========================================

    /**
     * Opções de status
     */
    public static function getStatusOptions(): array
    {
        return [
            'prospecting' => 'Em Prospecção',
            'active' => 'Em Andamento',
            'suspended' => 'Suspenso',
            'archived' => 'Arquivado',
            'closed_won' => 'Encerrado - Êxito',
            'closed_lost' => 'Encerrado - Improcedente',
            'closed_settled' => 'Encerrado - Acordo',
            'closed_other' => 'Encerrado - Outros',
        ];
    }

    /**
     * Opções de fase
     */
    public static function getPhaseOptions(): array
    {
        return [
            'knowledge' => 'Conhecimento',
            'execution' => 'Execução',
            'appeal' => 'Recursal',
            'precautionary' => 'Cautelar',
            'preliminary' => 'Tutela de Urgência',
        ];
    }

    /**
     * Opções de instância
     */
    public static function getInstanceOptions(): array
    {
        return [
            'first' => '1ª Instância',
            'second' => '2ª Instância',
            'superior' => 'Tribunais Superiores',
            'supreme' => 'STF',
        ];
    }

    /**
     * Opções de papel do cliente
     */
    public static function getClientRoleOptions(): array
    {
        return [
            'plaintiff' => 'Autor/Requerente',
            'defendant' => 'Réu/Requerido',
            'third_party' => 'Terceiro Interessado',
            'interested' => 'Interessado',
            'other' => 'Outro',
        ];
    }

    /**
     * Áreas do direito
     */
    public static function getMatterTypeOptions(): array
    {
        return [
            'civil' => 'Cível',
            'family' => 'Família',
            'criminal' => 'Criminal',
            'labor' => 'Trabalhista',
            'tax' => 'Tributário',
            'administrative' => 'Administrativo',
            'consumer' => 'Consumidor',
            'business' => 'Empresarial',
            'real_estate' => 'Imobiliário',
            'bankruptcy' => 'Falência/Recuperação',
            'environmental' => 'Ambiental',
            'electoral' => 'Eleitoral',
            'social_security' => 'Previdenciário',
            'intellectual_property' => 'Propriedade Intelectual',
            'other' => 'Outros',
        ];
    }

    /**
     * Estados brasileiros
     */
    public static function getStateOptions(): array
    {
        return [
            'AC' => 'Acre', 'AL' => 'Alagoas', 'AP' => 'Amapá',
            'AM' => 'Amazonas', 'BA' => 'Bahia', 'CE' => 'Ceará',
            'DF' => 'Distrito Federal', 'ES' => 'Espírito Santo',
            'GO' => 'Goiás', 'MA' => 'Maranhão', 'MT' => 'Mato Grosso',
            'MS' => 'Mato Grosso do Sul', 'MG' => 'Minas Gerais',
            'PA' => 'Pará', 'PB' => 'Paraíba', 'PR' => 'Paraná',
            'PE' => 'Pernambuco', 'PI' => 'Piauí', 'RJ' => 'Rio de Janeiro',
            'RN' => 'Rio Grande do Norte', 'RS' => 'Rio Grande do Sul',
            'RO' => 'Rondônia', 'RR' => 'Roraima', 'SC' => 'Santa Catarina',
            'SP' => 'São Paulo', 'SE' => 'Sergipe', 'TO' => 'Tocantins',
        ];
    }

    /**
     * Tribunais principais
     */
    public static function getCourtOptions(): array
    {
        return [
            // Justiça Estadual
            'TJAC' => 'TJAC - Acre', 'TJAL' => 'TJAL - Alagoas',
            'TJAP' => 'TJAP - Amapá', 'TJAM' => 'TJAM - Amazonas',
            'TJBA' => 'TJBA - Bahia', 'TJCE' => 'TJCE - Ceará',
            'TJDFT' => 'TJDFT - Distrito Federal', 'TJES' => 'TJES - Espírito Santo',
            'TJGO' => 'TJGO - Goiás', 'TJMA' => 'TJMA - Maranhão',
            'TJMT' => 'TJMT - Mato Grosso', 'TJMS' => 'TJMS - Mato Grosso do Sul',
            'TJMG' => 'TJMG - Minas Gerais', 'TJPA' => 'TJPA - Pará',
            'TJPB' => 'TJPB - Paraíba', 'TJPR' => 'TJPR - Paraná',
            'TJPE' => 'TJPE - Pernambuco', 'TJPI' => 'TJPI - Piauí',
            'TJRJ' => 'TJRJ - Rio de Janeiro', 'TJRN' => 'TJRN - Rio Grande do Norte',
            'TJRS' => 'TJRS - Rio Grande do Sul', 'TJRO' => 'TJRO - Rondônia',
            'TJRR' => 'TJRR - Roraima', 'TJSC' => 'TJSC - Santa Catarina',
            'TJSP' => 'TJSP - São Paulo', 'TJSE' => 'TJSE - Sergipe',
            'TJTO' => 'TJTO - Tocantins',
            // Justiça Federal
            'TRF1' => 'TRF 1ª Região', 'TRF2' => 'TRF 2ª Região',
            'TRF3' => 'TRF 3ª Região', 'TRF4' => 'TRF 4ª Região',
            'TRF5' => 'TRF 5ª Região', 'TRF6' => 'TRF 6ª Região',
            // Justiça do Trabalho
            'TRT1' => 'TRT 1ª Região (RJ)', 'TRT2' => 'TRT 2ª Região (SP)',
            'TRT3' => 'TRT 3ª Região (MG)', 'TRT4' => 'TRT 4ª Região (RS)',
            'TRT5' => 'TRT 5ª Região (BA)', 'TRT15' => 'TRT 15ª Região (Campinas)',
            // Tribunais Superiores
            'STJ' => 'Superior Tribunal de Justiça',
            'STF' => 'Supremo Tribunal Federal',
            'TST' => 'Tribunal Superior do Trabalho',
            'TSE' => 'Tribunal Superior Eleitoral',
            'STM' => 'Superior Tribunal Militar',
        ];
    }
}
