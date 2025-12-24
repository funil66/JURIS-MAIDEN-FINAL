<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DeadlineType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'default_days',
        'counting_type',
        'excludes_start_date',
        'extends_to_next_business_day',
        'category',
        'priority',
        'alert_days',
        'is_active',
    ];

    protected $casts = [
        'default_days' => 'integer',
        'excludes_start_date' => 'boolean',
        'extends_to_next_business_day' => 'boolean',
        'alert_days' => 'array',
        'is_active' => 'boolean',
    ];

    // Counting Types
    public const COUNTING_BUSINESS_DAYS = 'business_days';
    public const COUNTING_CALENDAR_DAYS = 'calendar_days';
    public const COUNTING_CONTINUOUS = 'continuous';

    public const COUNTING_TYPES = [
        self::COUNTING_BUSINESS_DAYS => 'Dias Úteis',
        self::COUNTING_CALENDAR_DAYS => 'Dias Corridos',
        self::COUNTING_CONTINUOUS => 'Contínuo (sem interrupção)',
    ];

    // Categories
    public const CATEGORY_RESPONSE = 'response';
    public const CATEGORY_APPEAL = 'appeal';
    public const CATEGORY_MANIFESTATION = 'manifestation';
    public const CATEGORY_HEARING = 'hearing';
    public const CATEGORY_EXECUTION = 'execution';
    public const CATEGORY_OTHER = 'other';

    public const CATEGORIES = [
        self::CATEGORY_RESPONSE => 'Resposta/Contestação',
        self::CATEGORY_APPEAL => 'Recurso',
        self::CATEGORY_MANIFESTATION => 'Manifestação',
        self::CATEGORY_HEARING => 'Audiência',
        self::CATEGORY_EXECUTION => 'Execução',
        self::CATEGORY_OTHER => 'Outro',
    ];

    // Priorities
    public const PRIORITY_LOW = 'low';
    public const PRIORITY_NORMAL = 'normal';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_CRITICAL = 'critical';

    public const PRIORITIES = [
        self::PRIORITY_LOW => 'Baixa',
        self::PRIORITY_NORMAL => 'Normal',
        self::PRIORITY_HIGH => 'Alta',
        self::PRIORITY_CRITICAL => 'Crítica',
    ];

    // Relationships
    public function deadlines()
    {
        return $this->hasMany(Deadline::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Seed de tipos de prazo comuns
     */
    public static function seedCommonTypes(): void
    {
        $types = [
            // Respostas
            [
                'code' => 'CONT',
                'name' => 'Contestação',
                'description' => 'Prazo para apresentação de contestação',
                'default_days' => 15,
                'counting_type' => self::COUNTING_BUSINESS_DAYS,
                'category' => self::CATEGORY_RESPONSE,
                'priority' => self::PRIORITY_HIGH,
                'alert_days' => [5, 3, 1],
            ],
            [
                'code' => 'RECON',
                'name' => 'Reconvenção',
                'description' => 'Prazo para apresentação de reconvenção',
                'default_days' => 15,
                'counting_type' => self::COUNTING_BUSINESS_DAYS,
                'category' => self::CATEGORY_RESPONSE,
                'priority' => self::PRIORITY_HIGH,
                'alert_days' => [5, 3, 1],
            ],
            [
                'code' => 'IMPUG',
                'name' => 'Impugnação à Contestação',
                'description' => 'Prazo para impugnação à contestação',
                'default_days' => 15,
                'counting_type' => self::COUNTING_BUSINESS_DAYS,
                'category' => self::CATEGORY_RESPONSE,
                'priority' => self::PRIORITY_NORMAL,
                'alert_days' => [5, 2],
            ],

            // Recursos
            [
                'code' => 'APEL',
                'name' => 'Apelação',
                'description' => 'Prazo para interposição de apelação',
                'default_days' => 15,
                'counting_type' => self::COUNTING_BUSINESS_DAYS,
                'category' => self::CATEGORY_APPEAL,
                'priority' => self::PRIORITY_CRITICAL,
                'alert_days' => [5, 3, 2, 1],
            ],
            [
                'code' => 'AGRAV',
                'name' => 'Agravo de Instrumento',
                'description' => 'Prazo para interposição de agravo de instrumento',
                'default_days' => 15,
                'counting_type' => self::COUNTING_BUSINESS_DAYS,
                'category' => self::CATEGORY_APPEAL,
                'priority' => self::PRIORITY_CRITICAL,
                'alert_days' => [5, 3, 2, 1],
            ],
            [
                'code' => 'EMBDEC',
                'name' => 'Embargos de Declaração',
                'description' => 'Prazo para oposição de embargos de declaração',
                'default_days' => 5,
                'counting_type' => self::COUNTING_BUSINESS_DAYS,
                'category' => self::CATEGORY_APPEAL,
                'priority' => self::PRIORITY_CRITICAL,
                'alert_days' => [3, 2, 1],
            ],
            [
                'code' => 'RESP',
                'name' => 'Recurso Especial',
                'description' => 'Prazo para interposição de recurso especial',
                'default_days' => 15,
                'counting_type' => self::COUNTING_BUSINESS_DAYS,
                'category' => self::CATEGORY_APPEAL,
                'priority' => self::PRIORITY_CRITICAL,
                'alert_days' => [5, 3, 2, 1],
            ],
            [
                'code' => 'REXT',
                'name' => 'Recurso Extraordinário',
                'description' => 'Prazo para interposição de recurso extraordinário',
                'default_days' => 15,
                'counting_type' => self::COUNTING_BUSINESS_DAYS,
                'category' => self::CATEGORY_APPEAL,
                'priority' => self::PRIORITY_CRITICAL,
                'alert_days' => [5, 3, 2, 1],
            ],
            [
                'code' => 'CONTRAP',
                'name' => 'Contrarrazões de Apelação',
                'description' => 'Prazo para apresentação de contrarrazões',
                'default_days' => 15,
                'counting_type' => self::COUNTING_BUSINESS_DAYS,
                'category' => self::CATEGORY_APPEAL,
                'priority' => self::PRIORITY_HIGH,
                'alert_days' => [5, 3, 1],
            ],

            // Manifestações
            [
                'code' => 'MANIF',
                'name' => 'Manifestação Genérica',
                'description' => 'Prazo para manifestação sobre documentos ou diligências',
                'default_days' => 5,
                'counting_type' => self::COUNTING_BUSINESS_DAYS,
                'category' => self::CATEGORY_MANIFESTATION,
                'priority' => self::PRIORITY_NORMAL,
                'alert_days' => [3, 1],
            ],
            [
                'code' => 'REPLICA',
                'name' => 'Réplica',
                'description' => 'Prazo para apresentação de réplica',
                'default_days' => 15,
                'counting_type' => self::COUNTING_BUSINESS_DAYS,
                'category' => self::CATEGORY_MANIFESTATION,
                'priority' => self::PRIORITY_NORMAL,
                'alert_days' => [5, 2],
            ],
            [
                'code' => 'ALEGFIN',
                'name' => 'Alegações Finais',
                'description' => 'Prazo para apresentação de alegações finais',
                'default_days' => 15,
                'counting_type' => self::COUNTING_BUSINESS_DAYS,
                'category' => self::CATEGORY_MANIFESTATION,
                'priority' => self::PRIORITY_HIGH,
                'alert_days' => [5, 3, 1],
            ],

            // Audiências
            [
                'code' => 'AUDCONC',
                'name' => 'Audiência de Conciliação',
                'description' => 'Prazo para preparação de audiência de conciliação',
                'default_days' => 0,
                'counting_type' => self::COUNTING_CALENDAR_DAYS,
                'category' => self::CATEGORY_HEARING,
                'priority' => self::PRIORITY_HIGH,
                'alert_days' => [7, 3, 1],
            ],
            [
                'code' => 'AUDINST',
                'name' => 'Audiência de Instrução',
                'description' => 'Prazo para preparação de audiência de instrução',
                'default_days' => 0,
                'counting_type' => self::COUNTING_CALENDAR_DAYS,
                'category' => self::CATEGORY_HEARING,
                'priority' => self::PRIORITY_CRITICAL,
                'alert_days' => [10, 5, 2, 1],
            ],

            // Execução
            [
                'code' => 'EMBEXEC',
                'name' => 'Embargos à Execução',
                'description' => 'Prazo para oposição de embargos à execução',
                'default_days' => 15,
                'counting_type' => self::COUNTING_BUSINESS_DAYS,
                'category' => self::CATEGORY_EXECUTION,
                'priority' => self::PRIORITY_CRITICAL,
                'alert_days' => [5, 3, 2, 1],
            ],
            [
                'code' => 'IMPUGCUMP',
                'name' => 'Impugnação ao Cumprimento',
                'description' => 'Prazo para impugnação ao cumprimento de sentença',
                'default_days' => 15,
                'counting_type' => self::COUNTING_BUSINESS_DAYS,
                'category' => self::CATEGORY_EXECUTION,
                'priority' => self::PRIORITY_HIGH,
                'alert_days' => [5, 3, 1],
            ],

            // Outros
            [
                'code' => 'GENERICO',
                'name' => 'Prazo Genérico',
                'description' => 'Prazo genérico para outras situações',
                'default_days' => 5,
                'counting_type' => self::COUNTING_BUSINESS_DAYS,
                'category' => self::CATEGORY_OTHER,
                'priority' => self::PRIORITY_NORMAL,
                'alert_days' => [3, 1],
            ],
        ];

        foreach ($types as $type) {
            static::updateOrCreate(
                ['code' => $type['code']],
                array_merge($type, [
                    'excludes_start_date' => true,
                    'extends_to_next_business_day' => true,
                    'is_active' => true,
                ])
            );
        }
    }
}
