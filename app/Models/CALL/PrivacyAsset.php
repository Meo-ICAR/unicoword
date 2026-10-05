<?php

namespace App\Models\CALL;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\CALL\BaseModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PrivacyAsset extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'asset_name',
        'type',
        'owner',
        'location',
    ];

    protected $casts = [
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

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeHardware($query)
    {
        return $query->where('type', 'hardware');
    }

    public function scopeSoftware($query)
    {
        return $query->where('type', 'software');
    }

    public function scopeCloudService($query)
    {
        return $query->where('type', 'cloud_service');
    }

    public function scopePaperArchive($query)
    {
        return $query->where('type', 'paper_archive');
    }

    public function scopeByOwner($query, $owner)
    {
        return $query->where('owner', $owner);
    }

    public function scopeByLocation($query, $location)
    {
        return $query->where('location', $location);
    }

    public function scopeByLocationContaining($query, $location)
    {
        return $query->where('location', 'LIKE', "%{$location}%");
    }

    public function scopeByAssetName($query, $assetName)
    {
        return $query->where('asset_name', $assetName);
    }

    public function scopeByAssetNameContaining($query, $assetName)
    {
        return $query->where('asset_name', 'LIKE', "%{$assetName}%");
    }

    // Accessors
    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'hardware' => 'Hardware',
            'software' => 'Software',
            'cloud_service' => 'Servizio Cloud',
            'paper_archive' => 'Archivio Cartaceo',
            default => 'Sconosciuto',
        };
    }

    public function getTypeIconAttribute(): string
    {
        return match ($this->type) {
            'hardware' => 'fas-server',
            'software' => 'fas-desktop',
            'cloud_service' => 'fas-cloud',
            'paper_archive' => 'fas-file-alt',
            default => 'fas-question-circle',
        };
    }

    public function getTypeColorAttribute(): string
    {
        return match ($this->type) {
            'hardware' => 'primary',
            'software' => 'info',
            'cloud_service' => 'success',
            'paper_archive' => 'warning',
            default => 'gray',
        };
    }

    public function getRiskLevelAttribute(): string
    {
        return match ($this->type) {
            'cloud_service' => 'Medio',  // Rischi esterni
            'hardware' => 'Medio',  // Perdita/furto fisico
            'software' => 'Basso',  // Vulnerabilità software
            'paper_archive' => 'Alto',  // Perdita/danno irreversibile
            default => 'Sconosciuto',
        };
    }

    public function getRiskColorAttribute(): string
    {
        return match ($this->risk_level) {
            'Alto' => 'danger',
            'Medio' => 'warning',
            'Basso' => 'info',
            default => 'gray',
        };
    }

    public function getFormattedLocationAttribute(): string
    {
        return $this->location ?: 'Nessuna posizione specificata';
    }

    public function getFormattedOwnerAttribute(): string
    {
        return $this->owner ?: 'Nessun proprietario specificato';
    }

    public function getIsHighRiskAttribute(): bool
    {
        return $this->risk_level === 'Alto';
    }

    public function getIsMediumRiskAttribute(): bool
    {
        return $this->risk_level === 'Medio';
    }

    public function getIsLowRiskAttribute(): bool
    {
        return $this->risk_level === 'Basso';
    }

    public function getSecurityRequirementsAttribute(): array
    {
        return match ($this->type) {
            'hardware' => [
                'Controllo accessi fisico',
                'Sorveglianza ambientale',
                'Backup regolare',
                'Manutenzione programmata',
                'Sicurezza antincendio'
            ],
            'software' => [
                'Aggiornamenti di sicurezza',
                'Antivirus/firewall',
                'Controllo accessi logici',
                'Backup dati',
                'Audit di sicurezza'
            ],
            'cloud_service' => [
                'Crittografia dati',
                'Autenticazione multi-fattore',
                'Contratto di servizio',
                'Certificazioni compliance',
                'Piano disaster recovery'
            ],
            'paper_archive' => [
                'Armadi blindati',
                'Controllo accessi',
                'Inventario documentale',
                'Distruzione sicura',
                'Backup digitale'
            ],
            default => [],
        };
    }

    // Methods
    public function isHardware(): bool
    {
        return $this->type === 'hardware';
    }

    public function isSoftware(): bool
    {
        return $this->type === 'software';
    }

    public function isCloudService(): bool
    {
        return $this->type === 'cloud_service';
    }

    public function isPaperArchive(): bool
    {
        return $this->type === 'paper_archive';
    }

    public function updateOwner(string $owner): void
    {
        $this->owner = $owner;
        $this->save();
    }

    public function updateLocation(string $location): void
    {
        $this->location = $location;
        $this->save();
    }

    public function updateType(string $type): void
    {
        if (in_array($type, ['hardware', 'software', 'cloud_service', 'paper_archive'])) {
            $this->type = $type;
            $this->save();
        }
    }

    public function getSecurityCompliance(): array
    {
        $requirements = $this->security_requirements;
        $compliance = [];

        foreach ($requirements as $requirement) {
            $compliance[$requirement] = [
                'implemented' => false,  // Da implementare con logica specifica
                'last_check' => null,
                'notes' => null,
            ];
        }

        return $compliance;
    }

    public function generateAssetId(): string
    {
        $prefix = match ($this->type) {
            'hardware' => 'HW',
            'software' => 'SW',
            'cloud_service' => 'CS',
            'paper_archive' => 'PA',
            default => 'AS',
        };

        return $prefix . '-' . str_pad($this->id, 6, '0', STR_PAD_LEFT);
    }

    // Constants
    const TYPES = [
        'hardware' => 'Hardware',
        'software' => 'Software',
        'cloud_service' => 'Servizio Cloud',
        'paper_archive' => 'Archivio Cartaceo',
    ];

    const RISK_LEVELS = [
        'basso' => 'Basso',
        'medio' => 'Medio',
        'alto' => 'Alto',
    ];

    public static function getTypeOptions(): array
    {
        return self::TYPES;
    }

    public static function getRiskLevelOptions(): array
    {
        return self::RISK_LEVELS;
    }

    public static function getAssetsByType(string $type): \Illuminate\Database\Eloquent\Collection
    {
        return static::byType($type)->get();
    }

    public static function getAssetsByOwner(string $owner): \Illuminate\Database\Eloquent\Collection
    {
        return static::byOwner($owner)->get();
    }

    public static function getAssetsByLocation(string $location): \Illuminate\Database\Eloquent\Collection
    {
        return static::byLocationContaining($location)->get();
    }

    public static function getHighRiskAssets(): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('type', 'paper_archive')->get();
    }

    public static function getCloudAssets(): \Illuminate\Database\Eloquent\Collection
    {
        return static::cloudService()->get();
    }

    public static function searchAssets(string $query): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('asset_name', 'LIKE', "%{$query}%")
            ->orWhere('owner', 'LIKE', "%{$query}%")
            ->orWhere('location', 'LIKE', "%{$query}%")
            ->get();
    }

    public static function getAssetStatistics(): array
    {
        $stats = [];

        foreach (self::TYPES as $type => $label) {
            $stats[$type] = [
                'label' => $label,
                'count' => static::byType($type)->count(),
                'icon' => match ($type) {
                    'hardware' => 'fas-server',
                    'software' => 'fas-desktop',
                    'cloud_service' => 'fas-cloud',
                    'paper_archive' => 'fas-file-alt',
                    default => 'fas-question-circle',
                },
                'color' => match ($type) {
                    'hardware' => 'primary',
                    'software' => 'info',
                    'cloud_service' => 'success',
                    'paper_archive' => 'warning',
                    default => 'gray',
                },
            ];
        }

        return $stats;
    }
}
