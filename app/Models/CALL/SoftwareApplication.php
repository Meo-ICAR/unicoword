<?php

namespace App\Models\CALL;

use App\Models\CALL\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\CALL\BaseModel;
use Illuminate\Database\Eloquent\Model;

class SoftwareApplication extends BaseModel
{
    use HasFactory;

    protected $connection = 'mysql_compliance';

    protected $fillable = [
        'name',
        'provider_name',
        'software_category_id',
        'website_url',
        'company_id',
        'api_url',
        'sandbox_url',
        'api_key_url',
        'api_parameters',
        'is_cloud',
        'apikey',
        'wallet_balance',
    ];

    protected $casts = [
        'is_cloud' => 'boolean',
        'wallet_balance' => 'decimal:2',
    ];

    public function apiConfigurations(): HasMany
    {
        return $this->hasMany(ApiConfiguration::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(App\Models\CALL\Company::class, 'company_id', 'id');
    }

    public function softwareCategory(): BelongsTo
    {
        return $this->belongsTo(App\Models\CALL\SoftwareCategory::class, 'software_category_id');
    }

    /**
     * Get all subappaltis where this software application is the sub contractor
     */
    public function subappaltisAsSub()
    {
        return $this->morphMany(App\Models\CALL\Subappalti::class, 'sub');
    }

    /**
     * Get all subappaltis where this software application is the originator
     */
    public function subappaltisAsOriginator()
    {
        return $this->morphMany(App\Models\CALL\Subappalti::class, 'originator');
    }

    protected static function booted()
    {
        static::creating(function ($softwareApplication) {
            if (auth()->check() && method_exists(auth()->user(), 'current_company_id')) {
                $softwareApplication->company_id = auth()->user()->current_company_id;
            }
        });
    }
}
