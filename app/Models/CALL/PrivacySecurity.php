<?php

namespace App\Models\CALL;

use App\Models\CALL\BaseModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PrivacySecurity extends BaseModel
{
    use SoftDeletes;

    protected $table = 'privacy_security';

    protected $fillable = [
        'name',
        'description',
        'type',
        'status',
        'risk_level',
        'owner',
        'last_reviewed_at',
        'next_review_due',
    ];

    protected $casts = [
        'last_reviewed_at' => 'datetime',
        'next_review_due' => 'datetime',
    ];

    // Types
    const TYPE_TECHNICAL = 'technical';
    const TYPE_ORGANIZATIONAL = 'organizational';
    // Statuses
    const STATUS_PLANNED = 'planned';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_IMPLEMENTED = 'implemented';
    const STATUS_DEPRECATED = 'deprecated';
    // Risk Levels
    const RISK_LOW = 'low';
    const RISK_MEDIUM = 'medium';
    const RISK_HIGH = 'high';
    const RISK_CRITICAL = 'critical';

    /**
     * Get the available types as an array.
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_TECHNICAL => 'Tecnico',
            self::TYPE_ORGANIZATIONAL => 'Organizzativo',
        ];
    }

    /**
     * Get the available statuses as an array.
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_PLANNED => 'Pianificato',
            self::STATUS_IN_PROGRESS => 'In Corso',
            self::STATUS_IMPLEMENTED => 'Implementato',
            self::STATUS_DEPRECATED => 'Deprecato',
        ];
    }

    /**
     * Get the available risk levels as an array.
     */
    public static function getRiskLevels(): array
    {
        return [
            self::RISK_LOW => 'Basso',
            self::RISK_MEDIUM => 'Medio',
            self::RISK_HIGH => 'Alto',
            self::RISK_CRITICAL => 'Critico',
        ];
    }

    /**
     * Get the type label.
     */
    public function getTypeLabel(): string
    {
        return self::getTypes()[$this->type] ?? $this->type;
    }

    /**
     * Get the status label.
     */
    public function getStatusLabel(): string
    {
        return self::getStatuses()[$this->status] ?? $this->status;
    }

    /**
     * Get the risk level label.
     */
    public function getRiskLevelLabel(): string
    {
        return self::getRiskLevels()[$this->risk_level] ?? $this->risk_level;
    }

    /**
     * Get the risk level color for UI display.
     */
    public function getRiskLevelColor(): string
    {
        return match ($this->risk_level) {
            self::RISK_LOW => 'success',
            self::RISK_MEDIUM => 'warning',
            self::RISK_HIGH => 'danger',
            self::RISK_CRITICAL => 'danger',
            default => 'gray',
        };
    }

    /**
     * Get the status color for UI display.
     */
    public function getStatusColor(): string
    {
        return match ($this->status) {
            self::STATUS_PLANNED => 'gray',
            self::STATUS_IN_PROGRESS => 'warning',
            self::STATUS_IMPLEMENTED => 'success',
            self::STATUS_DEPRECATED => 'danger',
            default => 'gray',
        };
    }

    /**
     * Check if the security measure is due for review.
     */
    public function isReviewDue(): bool
    {
        return $this->next_review_due && $this->next_review_due->isPast();
    }

    /**
     * Check if the security measure is implemented.
     */
    public function isImplemented(): bool
    {
        return $this->status === self::STATUS_IMPLEMENTED;
    }

    /**
     * Scope to get only implemented measures.
     */
    public function scopeImplemented($query)
    {
        return $query->where('status', self::STATUS_IMPLEMENTED);
    }

    /**
     * Scope to get measures by type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to get measures by risk level.
     */
    public function scopeByRiskLevel($query, string $riskLevel)
    {
        return $query->where('risk_level', $riskLevel);
    }

    /**
     * Scope to get measures due for review.
     */
    public function scopeReviewDue($query)
    {
        return $query
            ->whereNotNull('next_review_due')
            ->where('next_review_due', '<=', now());
    }

    /**
     * Get the mitigation factor for risk reduction calculation.
     * Returns a value between 0.0 and 0.9 representing the risk reduction percentage.
     */
    public function getMitigationFactor(): float
    {
        // Base factor depends on implementation status
        $baseFactor = match ($this->status) {
            self::STATUS_IMPLEMENTED => 0.3,  // 30% reduction base
            self::STATUS_IN_PROGRESS => 0.15,  // 15% reduction base
            self::STATUS_PLANNED => 0.05,  // 5% reduction base
            default => 0.0,  // Deprecated = no reduction
        };

        // Adjust factor based on type
        $typeMultiplier = match ($this->type) {
            self::TYPE_TECHNICAL => 1.2,  // Technical measures more effective
            self::TYPE_ORGANIZATIONAL => 1.0,  // Organizational measures standard
            default => 1.0,
        };

        // Adjust factor based on risk level (higher risk = more effective mitigation)
        $riskMultiplier = match ($this->risk_level) {
            self::RISK_CRITICAL => 1.5,  // Critical risks get best mitigation
            self::RISK_HIGH => 1.3,  // High risks get good mitigation
            self::RISK_MEDIUM => 1.1,  // Medium risks get moderate mitigation
            self::RISK_LOW => 1.0,  // Low risks get standard mitigation
            default => 1.0,
        };

        // Calculate final factor with maximum cap of 0.9 (90% reduction)
        $finalFactor = $baseFactor * $typeMultiplier * $riskMultiplier;

        return min($finalFactor, 0.9);
    }

    /**
     * Get the mitigation factor as a percentage.
     */
    public function getMitigationPercentage(): int
    {
        return intval($this->getMitigationFactor() * 100);
    }
}
