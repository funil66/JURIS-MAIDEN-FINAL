<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class DocumentTemplate extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'name',
        'slug',
        'category',
        'content',
        'variables',
        'description',
        'format',
        'orientation',
        'is_active',
        'is_system',
        'usage_count',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean',
        'is_system' => 'boolean',
        'usage_count' => 'integer',
    ];

    /**
     * Activity Log Options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'category', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Boot do model para gerar slug automático
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($template) {
            if (empty($template->slug)) {
                $template->slug = Str::slug($template->name);
            }
        });
    }

    // ==========================================
    // RELACIONAMENTOS
    // ==========================================

    /**
     * Criador do template
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Último editor
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Documentos gerados a partir deste template
     */
    public function generatedDocuments(): HasMany
    {
        return $this->hasMany(GeneratedDocument::class);
    }

    // ==========================================
    // MÉTODOS AUXILIARES
    // ==========================================

    /**
     * Categorias disponíveis
     */
    public static function getCategoryOptions(): array
    {
        return [
            'procuracao' => '📜 Procuração',
            'substabelecimento' => '🔄 Substabelecimento',
            'peticao' => '📝 Petição',
            'contrato' => '📋 Contrato',
            'declaracao' => '📄 Declaração',
            'recibo' => '💵 Recibo',
            'relatorio' => '📊 Relatório',
            'correspondencia' => '✉️ Correspondência',
            'outro' => '📁 Outro',
        ];
    }

    /**
     * Cores das categorias
     */
    public static function getCategoryColors(): array
    {
        return [
            'procuracao' => 'amber',
            'substabelecimento' => 'blue',
            'peticao' => 'green',
            'contrato' => 'purple',
            'declaracao' => 'cyan',
            'recibo' => 'emerald',
            'relatorio' => 'orange',
            'correspondencia' => 'indigo',
            'outro' => 'gray',
        ];
    }

    /**
     * Orientações disponíveis
     */
    public static function getOrientationOptions(): array
    {
        return [
            'portrait' => 'Retrato (Vertical)',
            'landscape' => 'Paisagem (Horizontal)',
        ];
    }

    /**
     * Formatos de papel disponíveis
     */
    public static function getFormatOptions(): array
    {
        return [
            'A4' => 'A4 (210 x 297 mm)',
            'Letter' => 'Letter (216 x 279 mm)',
            'Legal' => 'Legal (216 x 356 mm)',
            'A5' => 'A5 (148 x 210 mm)',
        ];
    }

    /**
     * Extrai as variáveis do conteúdo do template
     */
    public function extractVariables(): array
    {
        preg_match_all('/\{\{(\w+)\}\}/', $this->content, $matches);
        return array_unique($matches[1]);
    }

    /**
     * Gera o documento substituindo as variáveis
     */
    public function generateContent(array $variables): string
    {
        $content = $this->content;

        foreach ($variables as $key => $value) {
            $content = str_replace("{{" . $key . "}}", $value, $content);
        }

        return $content;
    }

    /**
     * Incrementa contador de uso
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    /**
     * Variáveis padrão do sistema (disponíveis para todos os templates)
     */
    public static function getSystemVariables(): array
    {
        return [
            // Advogado
            'advogado_nome' => 'Nome completo do advogado',
            'advogado_oab' => 'Número OAB formatado (ex: OAB/SP 123.456)',
            'advogado_email' => 'E-mail do advogado',
            'advogado_telefone' => 'Telefone do advogado',
            'advogado_endereco' => 'Endereço profissional',
            
            // Cliente
            'cliente_nome' => 'Nome/Razão Social do cliente',
            'cliente_documento' => 'CPF ou CNPJ do cliente',
            'cliente_endereco' => 'Endereço completo do cliente',
            'cliente_telefone' => 'Telefone do cliente',
            'cliente_email' => 'E-mail do cliente',
            
            // Processo
            'processo_numero' => 'Número do processo',
            'processo_vara' => 'Vara/Tribunal',
            'processo_comarca' => 'Comarca',
            'processo_autor' => 'Nome do autor',
            'processo_reu' => 'Nome do réu',
            
            // Serviço
            'servico_codigo' => 'Código do serviço',
            'servico_tipo' => 'Tipo do serviço',
            'servico_data' => 'Data agendada',
            'servico_valor' => 'Valor do serviço',
            'servico_local' => 'Local da diligência',
            
            // Datas
            'data_atual' => 'Data atual por extenso',
            'data_atual_curta' => 'Data atual (dd/mm/aaaa)',
            'ano_atual' => 'Ano atual',
            'mes_atual' => 'Mês atual por extenso',
        ];
    }

    /**
     * Preenche variáveis do sistema automaticamente
     */
    public function fillSystemVariables(?User $user = null, ?Client $client = null, ?Service $service = null): array
    {
        $variables = [];

        // Data atual
        $variables['data_atual'] = now()->locale('pt_BR')->isoFormat('D [de] MMMM [de] YYYY');
        $variables['data_atual_curta'] = now()->format('d/m/Y');
        $variables['ano_atual'] = now()->year;
        $variables['mes_atual'] = now()->locale('pt_BR')->isoFormat('MMMM');

        // Advogado
        if ($user) {
            $variables['advogado_nome'] = $user->name;
            $variables['advogado_oab'] = $user->oab_formatted ?? '';
            $variables['advogado_email'] = $user->email;
            $variables['advogado_telefone'] = $user->phone ?? '';
        }

        // Cliente
        if ($client) {
            $variables['cliente_nome'] = $client->name;
            $variables['cliente_documento'] = $client->document ?? '';
            $variables['cliente_endereco'] = $client->full_address ?? '';
            $variables['cliente_telefone'] = $client->phone ?? '';
            $variables['cliente_email'] = $client->email ?? '';
        }

        // Serviço
        if ($service) {
            $variables['servico_codigo'] = $service->code;
            $variables['servico_tipo'] = $service->serviceType?->name ?? '';
            $variables['servico_data'] = $service->scheduled_datetime?->format('d/m/Y H:i') ?? '';
            $variables['servico_valor'] = $service->formatted_total ?? '';
            $variables['servico_local'] = $service->full_location ?? '';
            $variables['processo_numero'] = $service->process_number ?? '';
            $variables['processo_vara'] = $service->court ?? '';
            $variables['processo_comarca'] = $service->jurisdiction ?? '';
            $variables['processo_autor'] = $service->plaintiff ?? '';
            $variables['processo_reu'] = $service->defendant ?? '';
        }

        return $variables;
    }

    // ==========================================
    // SCOPES
    // ==========================================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    public function scopeCustom($query)
    {
        return $query->where('is_system', false);
    }
}
