<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;

class Holiday extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'name',
        'type',
        'state',
        'city',
        'court',
        'is_recurring',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'is_recurring' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Types
    public const TYPE_NATIONAL = 'national';
    public const TYPE_STATE = 'state';
    public const TYPE_MUNICIPAL = 'municipal';
    public const TYPE_COURT = 'court';

    public const TYPES = [
        self::TYPE_NATIONAL => 'Nacional',
        self::TYPE_STATE => 'Estadual',
        self::TYPE_MUNICIPAL => 'Municipal',
        self::TYPE_COURT => 'Forense',
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeNational($query)
    {
        return $query->where('type', self::TYPE_NATIONAL);
    }

    public function scopeForState($query, string $state)
    {
        return $query->where(function ($q) use ($state) {
            $q->where('type', self::TYPE_NATIONAL)
              ->orWhere(function ($sq) use ($state) {
                  $sq->where('type', self::TYPE_STATE)
                     ->where('state', $state);
              });
        });
    }

    public function scopeForDate($query, Carbon $date)
    {
        return $query->where(function ($q) use ($date) {
            // Feriado fixo na data
            $q->where('date', $date->toDateString())
              // OU feriado recorrente no mesmo dia/mês
              ->orWhere(function ($sq) use ($date) {
                  $sq->where('is_recurring', true)
                     ->whereMonth('date', $date->month)
                     ->whereDay('date', $date->day);
              });
        });
    }

    public function scopeInPeriod($query, Carbon $start, Carbon $end)
    {
        return $query->where(function ($q) use ($start, $end) {
            $q->whereBetween('date', [$start->toDateString(), $end->toDateString()]);
            
            // Incluir feriados recorrentes
            if ($start->year !== $end->year) {
                // Se cruza anos, buscar todos os recorrentes
                $q->orWhere('is_recurring', true);
            }
        });
    }

    /**
     * Verifica se uma data é feriado
     */
    public static function isHoliday(Carbon $date, ?string $state = null): bool
    {
        $query = static::active()->forDate($date);
        
        if ($state) {
            $query->where(function ($q) use ($state) {
                $q->where('type', self::TYPE_NATIONAL)
                  ->orWhere(function ($sq) use ($state) {
                      $sq->where('state', $state);
                  });
            });
        } else {
            $query->national();
        }
        
        return $query->exists();
    }

    /**
     * Retorna lista de feriados em um período
     */
    public static function getHolidaysInPeriod(Carbon $start, Carbon $end, ?string $state = null): array
    {
        $holidays = [];
        
        $query = static::active()->inPeriod($start, $end);
        
        if ($state) {
            $query->forState($state);
        } else {
            $query->national();
        }
        
        foreach ($query->get() as $holiday) {
            if ($holiday->is_recurring) {
                // Para feriados recorrentes, adicionar para cada ano no período
                $current = $start->copy()->setDay($holiday->date->day)->setMonth($holiday->date->month);
                if ($current->lt($start)) {
                    $current->addYear();
                }
                
                while ($current->lte($end)) {
                    $holidays[$current->toDateString()] = $holiday->name;
                    $current->addYear();
                }
            } else {
                $holidays[$holiday->date->toDateString()] = $holiday->name;
            }
        }
        
        return $holidays;
    }

    /**
     * Popula feriados nacionais brasileiros
     */
    public static function seedBrazilianHolidays(int $year = null): void
    {
        $year = $year ?? now()->year;
        
        $nationalHolidays = [
            ['month' => 1, 'day' => 1, 'name' => 'Confraternização Universal', 'recurring' => true],
            ['month' => 4, 'day' => 21, 'name' => 'Tiradentes', 'recurring' => true],
            ['month' => 5, 'day' => 1, 'name' => 'Dia do Trabalhador', 'recurring' => true],
            ['month' => 9, 'day' => 7, 'name' => 'Independência do Brasil', 'recurring' => true],
            ['month' => 10, 'day' => 12, 'name' => 'Nossa Senhora Aparecida', 'recurring' => true],
            ['month' => 11, 'day' => 2, 'name' => 'Finados', 'recurring' => true],
            ['month' => 11, 'day' => 15, 'name' => 'Proclamação da República', 'recurring' => true],
            ['month' => 12, 'day' => 25, 'name' => 'Natal', 'recurring' => true],
        ];
        
        foreach ($nationalHolidays as $holiday) {
            $date = Carbon::create($year, $holiday['month'], $holiday['day']);
            
            static::updateOrCreate(
                ['date' => $date->toDateString()],
                [
                    'name' => $holiday['name'],
                    'type' => self::TYPE_NATIONAL,
                    'is_recurring' => $holiday['recurring'],
                    'is_active' => true,
                ]
            );
        }
        
        // Feriados móveis (calcular para o ano específico)
        // Páscoa e feriados dependentes
        $easter = static::calculateEaster($year);
        
        $mobileHolidays = [
            ['date' => $easter->copy()->subDays(47), 'name' => 'Carnaval'], // Terça-feira de Carnaval
            ['date' => $easter->copy()->subDays(48), 'name' => 'Carnaval'], // Segunda-feira de Carnaval
            ['date' => $easter->copy()->subDays(2), 'name' => 'Sexta-feira Santa'],
            ['date' => $easter, 'name' => 'Páscoa'],
            ['date' => $easter->copy()->addDays(60), 'name' => 'Corpus Christi'],
        ];
        
        foreach ($mobileHolidays as $holiday) {
            static::updateOrCreate(
                ['date' => $holiday['date']->toDateString()],
                [
                    'name' => $holiday['name'],
                    'type' => self::TYPE_NATIONAL,
                    'is_recurring' => false, // Móveis não são recorrentes
                    'is_active' => true,
                ]
            );
        }
    }

    /**
     * Calcula a data da Páscoa para um ano (Algoritmo de Gauss)
     */
    public static function calculateEaster(int $year): Carbon
    {
        $a = $year % 19;
        $b = intdiv($year, 100);
        $c = $year % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $month = intdiv($h + $l - 7 * $m + 114, 31);
        $day = (($h + $l - 7 * $m + 114) % 31) + 1;
        
        return Carbon::create($year, $month, $day);
    }
}
