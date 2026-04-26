<?php

namespace App\Models\CALL;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\CALL\BaseModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CodeRegistration extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'is_mandatory',
        'codeable_type',
    ];

    protected $casts = [
        'is_mandatory' => 'boolean',
    ];

    // Scopes
    public function scopeMandatory($query)
    {
        return $query->where('is_mandatory', true);
    }

    public function scopeOptional($query)
    {
        return $query->where('is_mandatory', false);
    }

    public function scopeByCode($query, $code)
    {
        return $query->where('code', $code);
    }

    public function scopeForModel($query, string $modelClass)
    {
        return $query->where('codeable_type', $modelClass);
    }

    public function scopeForClients($query)
    {
        return $query->where('codeable_type', 'App\Models\Client');
    }

    public function scopeForRegistrations($query)
    {
        return $query->where('codeable_type', 'App\Models\Registration');
    }

    // Accessors
    public function getIsMandatoryLabelAttribute(): string
    {
        return $this->is_mandatory ? 'Obbligatorio' : 'Opzionale';
    }

    public function getCodeableTypeLabelAttribute(): string
    {
        return match ($this->codeable_type) {
            'App\Models\Client' => 'Clienti',
            'App\Models\Registration' => 'Registrazioni',
            'App\Models\Company' => 'Aziende',
            default => class_basename($this->codeable_type)
        };
    }

    // Methods
    public static function getMandatoryCodes(?string $modelType = null): array
    {
        $query = static::mandatory();

        if ($modelType) {
            $query->where('codeable_type', $modelType);
        }

        return $query->pluck('code')->toArray();
    }

    public static function getOptionalCodes(?string $modelType = null): array
    {
        $query = static::optional();

        if ($modelType) {
            $query->where('codeable_type', $modelType);
        }

        return $query->pluck('code')->toArray();
    }

    public static function getAllCodes(?string $modelType = null): array
    {
        $query = static::query();

        if ($modelType) {
            $query->where('codeable_type', $modelType);
        }

        return $query->pluck('code')->toArray();
    }

    public static function getCodeDescription(string $code, ?string $modelType = null): ?string
    {
        $query = static::where('code', $code);

        if ($modelType) {
            $query->where('codeable_type', $modelType);
        }

        return $query->value('name');
    }

    public static function getCodesForModel(string $modelClass): array
    {
        return static::where('codeable_type', $modelClass)->pluck('code', 'name')->toArray();
    }

    public function isForModel(string $modelClass): bool
    {
        return $this->codeable_type === $modelClass;
    }
}
