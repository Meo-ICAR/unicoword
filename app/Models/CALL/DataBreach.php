<?php

namespace App\Models\CALL;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\CALL\BaseModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DataBreach extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'discovered_at',
        'occurred_at',
        'description',
        'nature_of_breach',
        'approximate_records_count',
        'is_notifiable_to_authority',
        'is_notifiable_to_subjects',
        'mitigation_actions',
        'company_id',
    ];

    protected $casts = [
        'discovered_at' => 'datetime',
        'occurred_at' => 'datetime',
        'is_notifiable_to_authority' => 'boolean',
        'is_notifiable_to_subjects' => 'boolean',
        'approximate_records_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(App\Models\CALL\Company::class);
    }

    // Scopes
    public function scopeByCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeNotifiableToAuthority($query)
    {
        return $query->where('is_notifiable_to_authority', true);
    }

    public function scopeNotNotifiableToAuthority($query)
    {
        return $query->where('is_notifiable_to_authority', false);
    }

    public function scopeNotifiableToSubjects($query)
    {
        return $query->where('is_notifiable_to_subjects', true);
    }

    public function scopeNotNotifiableToSubjects($query)
    {
        return $query->where('is_notifiable_to_subjects', false);
    }

    public function scopeHighImpact($query, $threshold = 1000)
    {
        return $query->where('approximate_records_count', '>=', $threshold);
    }

    public function scopeMediumImpact($query, $min = 100, $max = 999)
    {
        return $query->whereBetween('approximate_records_count', [$min, $max]);
    }

    public function scopeLowImpact($query, $max = 99)
    {
        return $query->where('approximate_records_count', '<=', $max);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('discovered_at', '>=', now()->subDays($days));
    }

    public function scopeCritical($query)
    {
        return $query
            ->where('is_notifiable_to_authority', true)
            ->where('is_notifiable_to_subjects', true);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('discovered_at', [$startDate, $endDate]);
    }

    // Accessors
    public function getImpactLevelAttribute(): string
    {
        $count = $this->approximate_records_count;

        return match (true) {
            $count >= 1000 => 'Alto',
            $count >= 100 => 'Medio',
            $count >= 1 => 'Basso',
            default => 'N/A',
        };
    }

    public function getImpactColorAttribute(): string
    {
        return match ($this->impact_level) {
            'Alto' => 'danger',
            'Medio' => 'warning',
            'Basso' => 'info',
            default => 'gray',
        };
    }

    public function getFormattedDiscoveredAtAttribute(): string
    {
        return $this->discovered_at ? $this->discovered_at->format('d/m/Y H:i') : 'N/A';
    }

    public function getFormattedOccurredAtAttribute(): string
    {
        return $this->occurred_at ? $this->occurred_at->format('d/m/Y H:i') : 'N/A';
    }

    public function getTimeToDiscoveryAttribute(): string
    {
        if (!$this->occurred_at || !$this->discovered_at) {
            return 'N/A';
        }

        $diff = $this->occurred_at->diff($this->discovered_at);

        if ($diff->days > 0) {
            return "{$diff->days} giorni";
        } elseif ($diff->h > 0) {
            return "{$diff->h} ore";
        } else {
            return "{$diff->i} minuti";
        }
    }

    public function getDaysSinceDiscoveryAttribute(): int
    {
        return $this->discovered_at ? now()->diffInDays($this->discovered_at) : 0;
    }

    public function getIsOverdueForNotificationAttribute(): bool
    {
        if (!$this->is_notifiable_to_authority) {
            return false;
        }

        return $this->discovered_at && $this->discovered_at->diffInHours(now()) > 72;
    }

    public function getHoursUntilDeadlineAttribute(): int
    {
        if (!$this->is_notifiable_to_authority || !$this->discovered_at) {
            return -1;
        }

        $deadline = $this->discovered_at->copy()->addHours(72);
        return max(0, now()->diffInHours($deadline, false));
    }

    public function getNotificationStatusAttribute(): string
    {
        if (!$this->is_notifiable_to_authority) {
            return 'Non richiesta';
        }

        if ($this->is_overdue_for_notification) {
            return 'Scaduta';
        } elseif ($this->hours_until_deadline <= 24) {
            return 'Urgente';
        } elseif ($this->hours_until_deadline <= 48) {
            return 'Attenzione';
        } else {
            return 'In tempo';
        }
    }

    public function getNotificationColorAttribute(): string
    {
        return match ($this->notification_status) {
            'Scaduta' => 'danger',
            'Urgente' => 'warning',
            'Attenzione' => 'info',
            'In tempo' => 'success',
            default => 'gray',
        };
    }

    public function getApproximateRecordsFormattedAttribute(): string
    {
        return number_format($this->approximate_records_count, 0, '.', ',');
    }

    // Methods
    public function markAsNotifiableToAuthority(): void
    {
        $this->is_notifiable_to_authority = true;
        $this->save();
    }

    public function markAsNotifiableToSubjects(): void
    {
        $this->is_notifiable_to_subjects = true;
        $this->save();
    }

    public function markAsNotNotifiableToAuthority(): void
    {
        $this->is_notifiable_to_authority = false;
        $this->save();
    }

    public function markAsNotNotifiableToSubjects(): void
    {
        $this->is_notifiable_to_subjects = false;
        $this->save();
    }

    public function requiresAuthorityNotification(): bool
    {
        return $this->is_notifiable_to_authority && !$this->is_overdue_for_notification;
    }

    public function requiresSubjectNotification(): bool
    {
        return $this->is_notifiable_to_subjects;
    }

    public function updateMitigationActions(string $actions): void
    {
        $this->mitigation_actions = $actions;
        $this->save();
    }

    public function updateRecordsCount(int $count): void
    {
        $this->approximate_records_count = $count;
        $this->save();
    }

    public function getRiskAssessment(): array
    {
        $riskFactors = [];

        // Numero record
        if ($this->approximate_records_count >= 1000) {
            $riskFactors[] = 'Alto numero di record coinvolti';
        }

        // Tempo di scoperta
        if ($this->time_to_discovery !== 'N/A' && str_contains($this->time_to_discovery, 'giorn')) {
            $riskFactors[] = 'Tempo di scoperta prolungato';
        }

        // Notifica autorità
        if ($this->is_notifiable_to_authority) {
            $riskFactors[] = "Richiede notifica all'autorità";
        }

        // Notifica soggetti
        if ($this->is_notifiable_to_subjects) {
            $riskFactors[] = 'Richiede notifica agli interessati';
        }

        return $riskFactors;
    }

    public function getComplianceStatus(): string
    {
        if (!$this->is_notifiable_to_authority) {
            return 'Non applicabile';
        }

        if ($this->is_overdue_for_notification) {
            return 'Violata (scaduta 72h)';
        }

        if ($this->hours_until_deadline <= 0) {
            return 'In scadenza';
        }

        return 'Conforme';
    }

    public function getComplianceColorAttribute(): string
    {
        return match ($this->compliance_status) {
            'Violata (scaduta 72h)' => 'danger',
            'In scadenza' => 'warning',
            'Conforme' => 'success',
            default => 'gray',
        };
    }

    // Constants
    const BREACH_NATURES = [
        'confidentiality_breach' => 'Violazione della riservatezza',
        'integrity_breach' => "Violazione dell'integrità",
        'availability_breach' => 'Violazione della disponibilità',
        'combination_breach' => 'Combinazione di violazioni',
    ];

    const NOTIFICATION_THRESHOLDS = [
        'authority_72h' => 72,  // ore
        'subjects_without_undue_delay' => null,  // senza ritardo ingiustificato
    ];

    public static function getBreachNatureOptions(): array
    {
        return self::BREACH_NATURES;
    }

    public static function getRecentBreaches(int $days = 30): \Illuminate\Database\Eloquent\Collection
    {
        return static::recent($days)->get();
    }

    public static function getCriticalBreaches(): \Illuminate\Database\Eloquent\Collection
    {
        return static::critical()->get();
    }

    public static function getOverdueNotifications(): \Illuminate\Database\Eloquent\Collection
    {
        return static::notifiableToAuthority()
            ->where('discovered_at', '<', now()->subHours(72))
            ->get();
    }
}
