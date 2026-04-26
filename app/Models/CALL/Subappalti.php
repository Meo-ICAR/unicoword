<?php

namespace App\Models\CALL;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Builder;
use App\Models\CALL\BaseModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subappalti extends BaseModel
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'categoria_dati' => 'array',
        'nomina_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the sub contractor (polymorphic relationship)
     */
    public function sub(): MorphTo
    {
        return $this->morphTo('sub');
    }

    /**
     * Get the originator (polymorphic relationship)
     */
    public function originator(): MorphTo
    {
        return $this->morphTo('originator');
    }

    /**
     * Get the company that owns the subappalto
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(App\Models\CALL\Company::class);
    }

    // Scopes
    public function scopeByCompany(Builder $query, string $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeBySubType(Builder $query, string $subType): Builder
    {
        return $query->where('sub_type', $subType);
    }

    public function scopeByOriginatorType(Builder $query, string $originatorType): Builder
    {
        return $query->where('originator_type', $originatorType);
    }

    public function scopeByServizio(Builder $query, string $servizio): Builder
    {
        return $query->where('servizio', 'like', '%' . $servizio . '%');
    }

    public function scopeByNomina(Builder $query, string $nomina): Builder
    {
        return $query->where('nomina', $nomina);
    }

    public function scopeWithNomination(Builder $query): Builder
    {
        return $query->whereNotNull('nomina_at');
    }

    public function scopeWithoutNomination(Builder $query): Builder
    {
        return $query->whereNull('nomina_at');
    }

    // Accessors
    public function getFormattedNominaAtAttribute(): string
    {
        return $this->nomina_at ? $this->nomina_at->format('d/m/Y H:i') : '';
    }

    public function getSubTypeLabelAttribute(): string
    {
        return match ($this->sub_type) {
            'client' => 'Cliente',
            'employee' => 'Dipendente',
            'software' => 'Software',
            default => ucfirst($this->sub_type),
        };
    }

    public function getOriginatorTypeLabelAttribute(): string
    {
        return match ($this->originator_type) {
            'company' => 'Azienda',
            'client' => 'Cliente',
            'employee' => 'Dipendente',
            default => ucfirst($this->originator_type),
        };
    }

    public function getCategorieDatiListAttribute(): string
    {
        if (!is_array($this->categoria_dati)) {
            return '';
        }

        return implode(', ', $this->categoria_dati);
    }

    public function getHasNominationAttribute(): bool
    {
        return !is_null($this->nomina_at);
    }

    public function getSummaryAttribute(): string
    {
        return substr($this->description, 0, 100) . (strlen($this->description) > 100 ? '...' : '');
    }

    // Helper methods
    public function isClient(): bool
    {
        return $this->sub_type === 'client';
    }

    public function isEmployee(): bool
    {
        return $this->sub_type === 'employee';
    }

    public function isSoftware(): bool
    {
        return $this->sub_type === 'software';
    }

    public function isOriginatorCompany(): bool
    {
        return $this->originator_type === 'company';
    }

    public function isOriginatorClient(): bool
    {
        return $this->originator_type === 'client';
    }

    public function isOriginatorEmployee(): bool
    {
        return $this->originator_type === 'employee';
    }

    public function hasDataCategories(): bool
    {
        return !empty($this->categoria_dati) && is_array($this->categoria_dati);
    }

    public function getDataCategoriesCount(): int
    {
        return $this->hasDataCategories() ? count($this->categoria_dati) : 0;
    }

    // Static methods
    public static function getSubTypes(): array
    {
        return [
            'client' => 'Cliente',
            'employee' => 'Dipendente',
            'software' => 'Software',
        ];
    }

    public static function getOriginatorTypes(): array
    {
        return [
            'company' => 'Azienda',
            'client' => 'Cliente',
            'employee' => 'Dipendente',
        ];
    }

    public static function getNominations(): array
    {
        return [
            'DPO' => 'Data Protection Officer',
            'Amministratore di Sistema' => 'Amministratore di Sistema',
            'Responsabile del Trattamento' => 'Responsabile del Trattamento',
            'Incaricato del Trattamento' => 'Incaricato del Trattamento',
            'Contitolare del Trattamento' => 'Contitolare del Trattamento',
        ];
    }

    protected static function booted()
    {
        static::creating(function ($subappalto) {
            if (auth()->check() && method_exists(auth()->user(), 'current_company_id')) {
                $subappalto->company_id = auth()->user()->current_company_id;
            }
        });
    }
}
