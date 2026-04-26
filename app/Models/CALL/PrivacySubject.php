<?php

namespace App\Models\CALL;

use App\Models\CALL\BaseModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PrivacySubject extends BaseModel
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'industry_sector',
        'description',
        'data_source',
        'has_vulnerable_subjects',
    ];

    protected $casts = [
        'has_vulnerable_subjects' => 'boolean',
    ];

    // Data Sources
    const SOURCE_DIRECT = 'direct';
    const SOURCE_THIRD_PARTY = 'third_party';
    const SOURCE_PUBLIC_RECORDS = 'public_records';
    const SOURCE_MIXED = 'mixed';

    /**
     * Get the available data sources as an array.
     */
    public static function getDataSources(): array
    {
        return [
            self::SOURCE_DIRECT => 'Diretto',
            self::SOURCE_THIRD_PARTY => 'Terze Parti',
            self::SOURCE_PUBLIC_RECORDS => 'Registri Pubblici',
            self::SOURCE_MIXED => 'Misto',
        ];
    }

    /**
     * Get the data source label.
     */
    public function getDataSourceLabel(): string
    {
        return self::getDataSources()[$this->data_source] ?? $this->data_source;
    }

    /**
     * Get the data source color for UI display.
     */
    public function getDataSourceColor(): string
    {
        return match ($this->data_source) {
            self::SOURCE_DIRECT => 'success',
            self::SOURCE_THIRD_PARTY => 'warning',
            self::SOURCE_PUBLIC_RECORDS => 'info',
            self::SOURCE_MIXED => 'primary',
            default => 'gray',
        };
    }

    /**
     * Check if the subject has vulnerable subjects.
     */
    public function hasVulnerableSubjects(): bool
    {
        return $this->has_vulnerable_subjects;
    }

    /**
     * Get the vulnerable subjects status label.
     */
    public function getVulnerableSubjectsLabel(): string
    {
        return $this->has_vulnerable_subjects ? 'Sì' : 'No';
    }

    /**
     * Get the vulnerable subjects status color.
     */
    public function getVulnerableSubjectsColor(): string
    {
        return $this->has_vulnerable_subjects ? 'danger' : 'success';
    }

    /**
     * Check if the data source is from third parties.
     */
    public function isThirdParty(): bool
    {
        return in_array($this->data_source, [self::SOURCE_THIRD_PARTY, self::SOURCE_MIXED]);
    }

    /**
     * Check if the data source is from public records.
     */
    public function isPublicRecords(): bool
    {
        return in_array($this->data_source, [self::SOURCE_PUBLIC_RECORDS, self::SOURCE_MIXED]);
    }

    /**
     * Check if the data source is direct.
     */
    public function isDirect(): bool
    {
        return $this->data_source === self::SOURCE_DIRECT;
    }

    /**
     * Scope to get subjects by data source.
     */
    public function scopeByDataSource($query, string $dataSource)
    {
        return $query->where('data_source', $dataSource);
    }

    /**
     * Scope to get subjects with vulnerable subjects.
     */
    public function scopeWithVulnerableSubjects($query)
    {
        return $query->where('has_vulnerable_subjects', true);
    }

    /**
     * Scope to get subjects without vulnerable subjects.
     */
    public function scopeWithoutVulnerableSubjects($query)
    {
        return $query->where('has_vulnerable_subjects', false);
    }

    /**
     * Scope to get subjects by industry sector.
     */
    public function scopeByIndustrySector($query, string $industrySector)
    {
        return $query->where('industry_sector', $industrySector);
    }

    /**
     * Scope to get subjects from third parties.
     */
    public function scopeThirdParty($query)
    {
        return $query->whereIn('data_source', [self::SOURCE_THIRD_PARTY, self::SOURCE_MIXED]);
    }

    /**
     * Scope to get subjects from public records.
     */
    public function scopePublicRecords($query)
    {
        return $query->whereIn('data_source', [self::SOURCE_PUBLIC_RECORDS, self::SOURCE_MIXED]);
    }

    /**
     * Scope to get direct subjects.
     */
    public function scopeDirect($query)
    {
        return $query->where('data_source', self::SOURCE_DIRECT);
    }

    /**
     * Get subjects grouped by industry sector.
     */
    public static function getGroupedByIndustrySector(): array
    {
        return self::all()
            ->groupBy('industry_sector')
            ->mapWithKeys(function ($group) {
                return [$group->first()->industry_sector => $group->map(function ($subject) {
                    return [
                        'id' => $subject->id,
                        'name' => $subject->name,
                        'data_source' => $subject->getDataSourceLabel(),
                        'data_source_color' => $subject->getDataSourceColor(),
                        'has_vulnerable_subjects' => $subject->getVulnerableSubjectsLabel(),
                        'vulnerable_color' => $subject->getVulnerableSubjectsColor(),
                    ];
                })->toArray()];
            })
            ->toArray();
    }

    /**
     * Get subjects grouped by data source.
     */
    public static function getGroupedByDataSource(): array
    {
        return self::all()
            ->groupBy('data_source')
            ->mapWithKeys(function ($group) {
                return [$group->first()->data_source => $group->map(function ($subject) {
                    return [
                        'id' => $subject->id,
                        'name' => $subject->name,
                        'industry_sector' => $subject->industry_sector,
                        'has_vulnerable_subjects' => $subject->getVulnerableSubjectsLabel(),
                        'vulnerable_color' => $subject->getVulnerableSubjectsColor(),
                    ];
                })->toArray()];
            })
            ->toArray();
    }

    /**
     * Get compliance level based on data source and vulnerable subjects.
     */
    public function getComplianceLevel(): string
    {
        if ($this->has_vulnerable_subjects) {
            return 'high';
        }

        if ($this->isThirdParty()) {
            return 'medium';
        }

        if ($this->isPublicRecords()) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * Get compliance level color.
     */
    public function getComplianceLevelColor(): string
    {
        return match ($this->getComplianceLevel()) {
            'high' => 'danger',
            'medium' => 'warning',
            'low' => 'success',
            default => 'gray',
        };
    }

    /**
     * Get compliance level label.
     */
    public function getComplianceLevelLabel(): string
    {
        return match ($this->getComplianceLevel()) {
            'high' => 'Alto',
            'medium' => 'Medio',
            'low' => 'Basso',
            default => 'Sconosciuto',
        };
    }

    /**
     * Check if additional safeguards are required.
     */
    public function requiresAdditionalSafeguards(): bool
    {
        return $this->has_vulnerable_subjects || $this->isThirdParty();
    }

    /**
     * Get required safeguards based on subject characteristics.
     */
    public function getRequiredSafeguards(): array
    {
        $safeguards = [];

        if ($this->has_vulnerable_subjects) {
            $safeguards[] = 'Consenso esplicito genitore/tutore';
            $safeguards[] = 'Valutazione DPIA obbligatoria';
            $safeguards[] = 'Limitazione trattamento al minimo necessario';
        }

        if ($this->isThirdParty()) {
            $safeguards[] = 'DPA (Data Processing Agreement)';
            $safeguards[] = 'Verifica conformità fornitore';
            $safeguards[] = 'Audit periodico terze parti';
        }

        if ($this->isPublicRecords()) {
            $safeguards[] = 'Verifica aggiornamento dati pubblici';
            $safeguards[] = 'Documentazione fonte dati';
        }

        return $safeguards;
    }
}
