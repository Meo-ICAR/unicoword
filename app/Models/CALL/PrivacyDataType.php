<?php

namespace App\Models\CALL;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\CALL\BaseModel;
use Illuminate\Database\Eloquent\Model;

class PrivacyDataType extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'category',
        'retention_years',
    ];

    protected $casts = [
        'retention_years' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Constants for categories
    const CATEGORY_COMUNI = 'comuni';
    const CATEGORY_PARTICOLARI = 'particolari';
    const CATEGORY_GIUDIZIARI = 'giudiziari';

    /**
     * Get formatted retention period
     */
    public function getRetentionPeriodAttribute(): string
    {
        return $this->retention_years . ' ' . ($this->retention_years == 1 ? 'anno' : 'anni');
    }

    /**
     * Get category label in Italian
     */
    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            self::CATEGORY_COMUNI => 'Dati Comuni',
            self::CATEGORY_PARTICOLARI => 'Dati Particolari',
            self::CATEGORY_GIUDIZIARI => 'Dati Giudiziari',
            default => 'Sconosciuto',
        };
    }

    /**
     * Get category color for UI
     */
    public function getCategoryColorAttribute(): string
    {
        return match ($this->category) {
            self::CATEGORY_COMUNI => 'success',
            self::CATEGORY_PARTICOLARI => 'warning',
            self::CATEGORY_GIUDIZIARI => 'danger',
            default => 'gray',
        };
    }

    /**
     * Scope to filter by category
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope to search by name or slug
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q
                ->where('name', 'like', '%' . $search . '%')
                ->orWhere('slug', 'like', '%' . $search . '%');
        });
    }

    /**
     * Get all categories as options
     */
    public static function getCategories(): array
    {
        return [
            self::CATEGORY_COMUNI => 'Dati Comuni',
            self::CATEGORY_PARTICOLARI => 'Dati Particolari',
            self::CATEGORY_GIUDIZIARI => 'Dati Giudiziari',
        ];
    }

    /**
     * Get all data types as options for select
     */
    public static function getOptions(): array
    {
        return static::pluck('name', 'slug')->toArray();
    }

    /**
     * Get data types grouped by category
     */
    public static function getGroupedByCategory(): array
    {
        return static::all()
            ->groupBy('category')
            ->map(function ($group) {
                return $group->pluck('name', 'slug');
            })
            ->toArray();
    }

    /**
     * Get data types by category for select
     */
    public static function getByCategoryOptions(string $category): array
    {
        return static::byCategory($category)->pluck('name', 'slug')->toArray();
    }

    /**
     * Find by slug
     */
    public static function findBySlug(string $slug): ?self
    {
        return static::where('slug', $slug)->first();
    }
}
