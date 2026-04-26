<?php

namespace App\Models\CALL;

use App\Models\CALL\BaseModel;
use Illuminate\Database\Eloquent\Model;

class PrivacyRetention extends BaseModel
{
    protected $table = 'privacy_retention';

    protected $fillable = [
        'data_category',
        'purpose',
        'retention_value',
        'retention_unit',
        'start_trigger',
        'legal_basis',
        'end_action',
        'legal_reference',
    ];

    protected $casts = [
        'retention_value' => 'integer',
    ];

    // Retention Units
    const UNIT_DAYS = 'days';
    const UNIT_HOURS = 'hours';
    const UNIT_MONTHS = 'months';
    const UNIT_YEARS = 'years';
    const UNIT_PERMANENT = 'permanent';
    // End Actions
    const ACTION_DELETE = 'delete';
    const ACTION_ANONYMIZE = 'anonymize';
    const ACTION_MANUAL_REVIEW = 'manual_review';

    /**
     * Get the available retention units as an array.
     */
    public static function getRetentionUnits(): array
    {
        return [
            self::UNIT_HOURS => 'Ore',
            self::UNIT_DAYS => 'Giorni',
            self::UNIT_MONTHS => 'Mesi',
            self::UNIT_YEARS => 'Anni',
            self::UNIT_PERMANENT => 'Permanente',
        ];
    }

    /**
     * Get the available end actions as an array.
     */
    public static function getEndActions(): array
    {
        return [
            self::ACTION_DELETE => 'Eliminazione',
            self::ACTION_ANONYMIZE => 'Anonimizzazione',
            self::ACTION_MANUAL_REVIEW => 'Revisione Manuale',
        ];
    }

    /**
     * Get the retention unit label.
     */
    public function getRetentionUnitLabel(): string
    {
        return self::getRetentionUnits()[$this->retention_unit] ?? $this->retention_unit;
    }

    /**
     * Get the end action label.
     */
    public function getEndActionLabel(): string
    {
        return self::getEndActions()[$this->end_action] ?? $this->end_action;
    }

    /**
     * Get the end action color for UI display.
     */
    public function getEndActionColor(): string
    {
        return match ($this->end_action) {
            self::ACTION_DELETE => 'danger',
            self::ACTION_ANONYMIZE => 'warning',
            self::ACTION_MANUAL_REVIEW => 'info',
            default => 'gray',
        };
    }

    /**
     * Get the formatted retention period.
     */
    public function getFormattedRetention(): string
    {
        if ($this->retention_unit === self::UNIT_PERMANENT) {
            return 'Permanente';
        }

        return "{$this->retention_value} {$this->getRetentionUnitLabel()}";
    }

    /**
     * Calculate the retention period in days.
     */
    public function getRetentionInDays(): ?int
    {
        if ($this->retention_unit === self::UNIT_PERMANENT) {
            return null;
        }

        return match ($this->retention_unit) {
            self::UNIT_HOURS => round($this->retention_value / 24, 2),
            self::UNIT_DAYS => $this->retention_value,
            self::UNIT_MONTHS => $this->retention_value * 30,
            self::UNIT_YEARS => $this->retention_value * 365,
            default => null,
        };
    }

    /**
     * Check if the retention is permanent.
     */
    public function isPermanent(): bool
    {
        return $this->retention_unit === self::UNIT_PERMANENT;
    }

    /**
     * Check if the end action requires manual intervention.
     */
    public function requiresManualAction(): bool
    {
        return $this->end_action === self::ACTION_MANUAL_REVIEW;
    }

    /**
     * Scope to get retention policies by data category.
     */
    public function scopeByDataCategory($query, string $dataCategory)
    {
        return $query->where('data_category', $dataCategory);
    }

    /**
     * Scope to get retention policies by legal basis.
     */
    public function scopeByLegalBasis($query, string $legalBasis)
    {
        return $query->where('legal_basis', $legalBasis);
    }

    /**
     * Scope to get permanent retention policies.
     */
    public function scopePermanent($query)
    {
        return $query->where('retention_unit', self::UNIT_PERMANENT);
    }

    /**
     * Scope to get temporary retention policies.
     */
    public function scopeTemporary($query)
    {
        return $query->where('retention_unit', '!=', self::UNIT_PERMANENT);
    }

    /**
     * Scope to get policies that require deletion.
     */
    public function scopeDeletion($query)
    {
        return $query->where('end_action', self::ACTION_DELETE);
    }

    /**
     * Scope to get policies that require anonymization.
     */
    public function scopeAnonymization($query)
    {
        return $query->where('end_action', self::ACTION_ANONYMIZE);
    }

    /**
     * Scope to get policies that require manual review.
     */
    public function scopeManualReview($query)
    {
        return $query->where('end_action', self::ACTION_MANUAL_REVIEW);
    }

    /**
     * Get retention policies grouped by data category.
     */
    public static function getGroupedByDataCategory(): array
    {
        return self::all()
            ->groupBy('data_category')
            ->mapWithKeys(function ($group) {
                return [$group->first()->data_category => $group->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'purpose' => $item->purpose,
                        'retention' => $item->getFormattedRetention(),
                        'start_trigger' => $item->start_trigger,
                        'legal_basis' => $item->legal_basis,
                        'end_action' => $item->getEndActionLabel(),
                    ];
                })->toArray()];
            })
            ->toArray();
    }

    /**
     * Get retention policies by legal basis.
     */
    public static function getByLegalBasisGrouped(): array
    {
        return self::all()
            ->groupBy('legal_basis')
            ->mapWithKeys(function ($group) {
                return [$group->first()->legal_basis => $group->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'data_category' => $item->data_category,
                        'purpose' => $item->purpose,
                        'retention' => $item->getFormattedRetention(),
                        'start_trigger' => $item->start_trigger,
                        'end_action' => $item->getEndActionLabel(),
                    ];
                })->toArray()];
            })
            ->toArray();
    }
}
