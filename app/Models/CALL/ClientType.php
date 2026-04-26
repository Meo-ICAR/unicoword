<?php

namespace App\Models\CALL;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Collection;
use App\Models\CALL\BaseModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClientType extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'is_active',
        'sort_order',
        'icon',
        'color',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function scopeByName($query, string $name)
    {
        return $query->where('name', $name);
    }

    // Relationships
    public function clients(): HasMany
    {
        return $this->hasMany(App\Models\CALL\Client::class);
    }

    public function activeClients(): HasMany
    {
        return $this->hasMany(App\Models\CALL\Client::class)->where('is_active', true);
    }

    public function inactiveClients(): HasMany
    {
        return $this->hasMany(App\Models\CALL\Client::class)->where('is_active', false);
    }

    public function leadClients(): HasMany
    {
        return $this->hasMany(App\Models\CALL\Client::class)->where('is_lead', true);
    }

    public function actualClients(): HasMany
    {
        return $this->hasMany(App\Models\CALL\Client::class)->where('is_client', true);
    }

    // Accessors
    public function getIsActiveLabelAttribute(): string
    {
        return $this->is_active ? 'Attivo' : 'Inattivo';
    }

    public function getClientCountAttribute(): int
    {
        return $this->clients()->count();
    }

    public function getActiveClientCountAttribute(): int
    {
        return $this->activeClients()->count();
    }

    public function getLeadCountAttribute(): int
    {
        return $this->leadClients()->count();
    }

    public function getClientTypeLabelAttribute(): string
    {
        return match ($this->name) {
            'Privato' => 'Persona Fisica',
            'PMI' => 'Piccola/Media Impresa',
            'PA' => 'Pubblica Amministrazione',
            'Azienda' => 'Azienda/Persona Giuridica',
            'Lead' => 'Potenziale Cliente',
            'Professionista' => 'Libero Professionista',
            'Istituzione' => 'Istituzione Governativa',
            default => $this->name,
        };
    }

    public function getIconWithColorAttribute(): string
    {
        if ($this->icon && $this->color) {
            return "<span style='color: {$this->color}'><i class='{$this->icon}'></i></span>";
        }
        return '';
    }

    // Methods
    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function activate(): void
    {
        $this->update(['is_active' => true]);
    }

    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    public function hasClients(): bool
    {
        return $this->clients()->exists();
    }

    public function hasActiveClients(): bool
    {
        return $this->activeClients()->exists();
    }

    public function canBeDeleted(): bool
    {
        return !$this->hasClients();
    }

    public function getClientStatistics(): array
    {
        return [
            'total' => $this->getClientCountAttribute(),
            'active' => $this->getActiveClientCountAttribute(),
            'leads' => $this->getLeadCountAttribute(),
            'clients' => $this->actualClients()->count(),
        ];
    }

    public function updateSortOrder(int $newOrder): void
    {
        $this->update(['sort_order' => $newOrder]);
    }

    // Static methods
    public static function getActiveTypes(): Collection
    {
        return static::active()->ordered()->get();
    }

    public static function getByName(string $name): ?ClientType
    {
        return static::byName($name)->first();
    }

    public static function getPersonTypes(): Collection
    {
        return static::whereIn('name', ['Privato', 'Professionista', 'Lead'])->ordered()->get();
    }

    public static function getCompanyTypes(): Collection
    {
        return static::whereIn('name', ['PMI', 'PA', 'Azienda', 'Istituzione'])->ordered()->get();
    }

    public static function getLeadTypes(): Collection
    {
        return static::whereIn('name', ['Lead'])->ordered()->get();
    }

    public static function getWithClientCount(): Collection
    {
        return static::withCount('clients')->ordered()->get();
    }

    public static function getMostPopular(int $limit = 5): Collection
    {
        return static::withCount('clients')
            ->orderBy('clients_count', 'desc')
            ->limit($limit)
            ->get();
    }
}
