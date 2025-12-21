<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class User extends Authenticatable implements FilamentUser, HasAvatar
{
    use HasFactory, Notifiable, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'oab',
        'oab_uf',
        'specialties',
        'phone',
        'whatsapp',
        'bio',
        'avatar',
        'website',
        'linkedin',
        'is_active',
        'google_access_token',
        'google_refresh_token',
        'google_token_expires_at',
        'google_calendar_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'specialties' => 'array',
            'is_active' => 'boolean',
            'google_token_expires_at' => 'datetime',
        ];
    }

    /**
     * Configure activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'oab', 'oab_uf', 'specialties', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Check if user can access Filament panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active;
    }

    /**
     * Get the user's avatar URL for Filament.
     */
    public function getFilamentAvatarUrl(): ?string
    {
        return $this->avatar;
    }

    /**
     * Get OAB formatted (e.g., "OAB/SP 123.456")
     */
    public function getOabFormattedAttribute(): ?string
    {
        if (!$this->oab || !$this->oab_uf) {
            return null;
        }
        return "OAB/{$this->oab_uf} {$this->oab}";
    }

    /**
     * Get specialties as comma-separated string.
     */
    public function getSpecialtiesTextAttribute(): ?string
    {
        if (!$this->specialties || !is_array($this->specialties)) {
            return null;
        }
        return implode(', ', $this->specialties);
    }

    /**
     * List of Brazilian states for OAB registration.
     */
    public static function getOabStates(): array
    {
        return [
            'AC' => 'Acre',
            'AL' => 'Alagoas',
            'AP' => 'Amapá',
            'AM' => 'Amazonas',
            'BA' => 'Bahia',
            'CE' => 'Ceará',
            'DF' => 'Distrito Federal',
            'ES' => 'Espírito Santo',
            'GO' => 'Goiás',
            'MA' => 'Maranhão',
            'MT' => 'Mato Grosso',
            'MS' => 'Mato Grosso do Sul',
            'MG' => 'Minas Gerais',
            'PA' => 'Pará',
            'PB' => 'Paraíba',
            'PR' => 'Paraná',
            'PE' => 'Pernambuco',
            'PI' => 'Piauí',
            'RJ' => 'Rio de Janeiro',
            'RN' => 'Rio Grande do Norte',
            'RS' => 'Rio Grande do Sul',
            'RO' => 'Rondônia',
            'RR' => 'Roraima',
            'SC' => 'Santa Catarina',
            'SP' => 'São Paulo',
            'SE' => 'Sergipe',
            'TO' => 'Tocantins',
        ];
    }

    /**
     * Common legal specialties in Brazil.
     */
    public static function getLegalSpecialties(): array
    {
        return [
            'Direito Civil',
            'Direito Trabalhista',
            'Direito Criminal',
            'Direito Previdenciário',
            'Direito Tributário',
            'Direito do Consumidor',
            'Direito de Família',
            'Direito Empresarial',
            'Direito Administrativo',
            'Direito Ambiental',
            'Direito Imobiliário',
            'Direito Digital',
            'Direito Eleitoral',
            'Direito Internacional',
            'Direito Contratual',
        ];
    }

    /**
     * Relacionamento com eventos do Google Calendar
     */
    public function googleCalendarEvents(): HasMany
    {
        return $this->hasMany(GoogleCalendarEvent::class);
    }

    /**
     * Verificar se está conectado ao Google Calendar
     */
    public function isGoogleCalendarConnected(): bool
    {
        return !empty($this->google_access_token) && !empty($this->google_refresh_token);
    }

    /**
     * Verificar se o token do Google está expirado
     */
    public function isGoogleTokenExpired(): bool
    {
        if (!$this->google_token_expires_at) {
            return true;
        }
        return $this->google_token_expires_at->isPast();
    }

    /**
     * Salvar tokens do Google
     */
    public function saveGoogleTokens(array $tokens): void
    {
        $this->update([
            'google_access_token' => $tokens['access_token'],
            'google_refresh_token' => $tokens['refresh_token'] ?? $this->google_refresh_token,
            'google_token_expires_at' => isset($tokens['expires_in'])
                ? now()->addSeconds($tokens['expires_in'])
                : now()->addHour(),
        ]);
    }

    /**
     * Remover conexão do Google Calendar
     */
    public function disconnectGoogleCalendar(): void
    {
        $this->update([
            'google_access_token' => null,
            'google_refresh_token' => null,
            'google_token_expires_at' => null,
            'google_calendar_id' => null,
        ]);

        $this->googleCalendarEvents()->delete();
    }
}
