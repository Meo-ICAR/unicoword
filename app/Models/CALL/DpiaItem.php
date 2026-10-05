<?php

namespace App\Models\CALL;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\CALL\BaseModel;
use Illuminate\Database\Eloquent\Model;

class DpiaItem extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'dpia_id',
        'risk_source',
        'potential_impact',
        'probability',
        'severity',
        'inherent_risk_score',
        'privacy_security_id',
        'residual_risk_score',
    ];

    protected $casts = [
        'probability' => 'integer',
        'severity' => 'integer',
        'inherent_risk_score' => 'integer',
        'residual_risk_score' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function dpia(): BelongsTo
    {
        return $this->belongsTo(App\Models\CALL\Dpia::class);
    }

    public function privacySecurity(): BelongsTo
    {
        return $this->belongsTo(App\Models\CALL\PrivacySecurity::class);
    }

    // Scopes
    public function scopeByRiskSource($query, $riskSource)
    {
        return $query->where('risk_source', $riskSource);
    }

    public function scopeByImpact($query, $impact)
    {
        return $query->where('potential_impact', $impact);
    }

    public function scopeHighProbability($query, $threshold = 3)
    {
        return $query->where('probability', '>=', $threshold);
    }

    public function scopeHighSeverity($query, $threshold = 3)
    {
        return $query->where('severity', '>=', $threshold);
    }

    public function scopeHighInherentRisk($query, $threshold = 10)
    {
        return $query->where('inherent_risk_score', '>=', $threshold);
    }

    public function scopeHighResidualRisk($query, $threshold = 10)
    {
        return $query->where('residual_risk_score', '>=', $threshold);
    }

    public function scopeWithMitigation($query)
    {
        return $query->whereNotNull('privacy_security_id');
    }

    public function scopeWithoutMitigation($query)
    {
        return $query->whereNull('privacy_security_id');
    }

    public function scopeByRiskLevel($query, $level)
    {
        $threshold = match ($level) {
            'low' => 5,
            'medium' => 10,
            'high' => 15,
            'critical' => 20,
            default => 0,
        };

        return $query->where('inherent_risk_score', '>=', $threshold);
    }

    // Accessors
    public function getRiskLevelAttribute(): string
    {
        $score = $this->inherent_risk_score;

        return match (true) {
            $score >= 20 => 'Critico',
            $score >= 15 => 'Alto',
            $score >= 10 => 'Medio',
            $score >= 5 => 'Basso',
            default => 'Trascurabile',
        };
    }

    public function getResidualRiskLevelAttribute(): string
    {
        $score = $this->residual_risk_score;

        return match (true) {
            $score >= 20 => 'Critico',
            $score >= 15 => 'Alto',
            $score >= 10 => 'Medio',
            $score >= 5 => 'Basso',
            default => 'Trascurabile',
        };
    }

    public function getRiskColorAttribute(): string
    {
        return match ($this->risk_level) {
            'Critico' => 'danger',
            'Alto' => 'warning',
            'Medio' => 'info',
            'Basso' => 'success',
            default => 'gray',
        };
    }

    public function getResidualRiskColorAttribute(): string
    {
        return match ($this->residual_risk_level) {
            'Critico' => 'danger',
            'Alto' => 'warning',
            'Medio' => 'info',
            'Basso' => 'success',
            default => 'gray',
        };
    }

    public function getProbabilityLabelAttribute(): string
    {
        return match ($this->probability) {
            1 => 'Molto Bassa',
            2 => 'Bassa',
            3 => 'Media',
            4 => 'Alta',
            5 => 'Molto Alta',
            default => 'Sconosciuta',
        };
    }

    public function getSeverityLabelAttribute(): string
    {
        return match ($this->severity) {
            1 => 'Trascurabile',
            2 => 'Minore',
            3 => 'Moderato',
            4 => 'Significativo',
            5 => 'Catastrofico',
            default => 'Sconosciuta',
        };
    }

    public function getRiskReductionPercentageAttribute(): float
    {
        if ($this->inherent_risk_score == 0) {
            return 0;
        }

        $reduction = $this->inherent_risk_score - $this->residual_risk_score;
        return round(($reduction / $this->inherent_risk_score) * 100, 2);
    }

    public function getIsMitigatedAttribute(): bool
    {
        return $this->residual_risk_score < $this->inherent_risk_score;
    }

    public function getRequiresFurtherMitigationAttribute(): bool
    {
        return $this->residual_risk_score >= 10;  // Soglia per rischio medio-alto
    }

    // Methods
    public function calculateInherentRisk(): void
    {
        // Calcolo rischio inerente: probabilità × gravità
        $this->inherent_risk_score = $this->probability * $this->severity;
        $this->save();
    }

    public function calculateResidualRisk(): void
    {
        if ($this->privacySecurity) {
            // Applica fattore di riduzione basato sulla misura di mitigazione
            $mitigationFactor = $this->privacySecurity->getMitigationFactor();
            $this->residual_risk_score = max(1, intval($this->inherent_risk_score * (1 - $mitigationFactor)));
        } else {
            // Nessuna mitigazione = rischio inerente
            $this->residual_risk_score = $this->inherent_risk_score;
        }
        $this->save();
    }

    public function applyMitigation(PrivacySecurity $security): void
    {
        $this->privacy_security_id = $security->id;
        $this->calculateResidualRisk();
    }

    public function removeMitigation(): void
    {
        $this->privacy_security_id = null;
        $this->residual_risk_score = $this->inherent_risk_score;
        $this->save();
    }

    public function updateRiskScores(int $probability, int $severity): void
    {
        $this->probability = $probability;
        $this->severity = $severity;
        $this->calculateInherentRisk();
        $this->calculateResidualRisk();
    }

    // Constants
    const RISK_SOURCES = [
        'Attacco hacker' => 'Attacco hacker',
        'Errore umano' => 'Errore umano',
        'Guasto hardware' => 'Guasto hardware',
        'Guasto software' => 'Guasto software',
        'Disastro naturale' => 'Disastro naturale',
        'Accesso non autorizzato' => 'Accesso non autorizzato',
        'Perdita dati' => 'Perdita dati',
        'Corruzione dati' => 'Corruzione dati',
        'Divulgazione non autorizzata' => 'Divulgazione non autorizzata',
        'Interruzione servizio' => 'Interruzione servizio',
    ];

    const POTENTIAL_IMPACTS = [
        'Perdita di riservatezza' => 'Perdita di riservatezza',
        'Danno reputazionale' => 'Danno reputazionale',
        'Perdita finanziaria' => 'Perdita finanziaria',
        'Violazione privacy' => 'Violazione privacy',
        'Interruzione operativa' => 'Interruzione operativa',
        'Danno fisico' => 'Danno fisico',
        'Violazione compliance' => 'Violazione compliance',
        'Perdita di integrità dati' => 'Perdita di integrità dati',
        'Disponibilità compromessa' => 'Disponibilità compromessa',
    ];

    public static function getRiskSourceOptions(): array
    {
        return self::RISK_SOURCES;
    }

    public static function getPotentialImpactOptions(): array
    {
        return self::POTENTIAL_IMPACTS;
    }

    public static function getProbabilityOptions(): array
    {
        return [
            1 => 'Molto Bassa',
            2 => 'Bassa',
            3 => 'Media',
            4 => 'Alta',
            5 => 'Molto Alta',
        ];
    }

    public static function getSeverityOptions(): array
    {
        return [
            1 => 'Trascurabile',
            2 => 'Minore',
            3 => 'Moderato',
            4 => 'Significativo',
            5 => 'Catastrofico',
        ];
    }
}
