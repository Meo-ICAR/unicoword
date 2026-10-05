<?php

namespace App\Models\CALL;

use App\Models\CALL\Client;
use App\Models\CALL\Company;
use App\Models\CALL\Employee;
use App\Models\CALL\Subappalti;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\CALL\BaseModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;

class RegistroTrattamenti extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $connection = 'mariadb';

    protected $fillable = [
        'company_id',
        'name',
        'approved_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(App\Models\CALL\Company::class, 'company_id', 'id');
    }

    public function registroTrattamentiItems(): HasMany
    {
        return $this->hasMany(App\Models\CALL\RegistroTrattamentiItem::class, 'company_id', 'company_id');
    }

    public function clients(): HasMany
    {
        return $this->hasMany(App\Models\CALL\Client::class, 'company_id', 'company_id');
    }

    public function employees(): HasMany
    {
        return $this->hasMany(App\Models\CALL\Employee::class, 'company_id', 'company_id');
    }

    public function subappaltiClienti(): HasMany
    {
        return $this
            ->hasMany(App\Models\CALL\Subappalti::class, 'company_id', 'company_id')
            ->where('originator_type', 'company')
            ->where('sub_type', 'client');
    }

    public function subappaltiDipendenti(): HasMany
    {
        return $this
            ->hasMany(App\Models\CALL\Subappalti::class, 'company_id', 'company_id')
            ->where('originator_type', 'company')
            ->where('sub_type', 'employee');
    }

    // Scopes
    public function scopeApproved($query)
    {
        return $query->whereNotNull('approved_at');
    }

    public function scopePending($query)
    {
        return $query->whereNull('approved_at');
    }

    public function scopeByCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    // Accessors
    public function getStatusAttribute(): string
    {
        return $this->approved_at ? 'Approved' : 'Pending';
    }

    public function getApprovedAtFormattedAttribute(): string
    {
        return $this->approved_at ? $this->approved_at->format('d/m/Y H:i') : 'Not approved';
    }

    // Mutators
    public function setApprovedAtAttribute($value)
    {
        $this->attributes['approved_at'] = $value ? now() : null;
    }

    protected static function booted()
    {
        static::creating(function ($registroTrattamenti) {
            // Try to get company_id from authenticated user
            if (auth()->check()) {
                $user = auth()->user();

                // First try current_company_id from user
                if (isset($user->current_company_id) && !empty($user->current_company_id)) {
                    $registroTrattamenti->company_id = $user->current_company_id;
                    return;
                }

                // Fallback: try to get from Filament tenant
                if (function_exists('filament') && filament()->getTenant()) {
                    $tenant = filament()->getTenant();
                    if ($tenant instanceof Company) {
                        $registroTrattamenti->company_id = $tenant->id;
                        return;
                    }
                }

                // Last fallback: get first company if user has companies
                if ($user->companies()->exists()) {
                    $firstCompany = $user->companies()->first();
                    $registroTrattamenti->company_id = $firstCompany->id;
                    return;
                }
            }

            // If no company found, throw an exception to prevent null company_id
            throw new Exception('Company ID is required but could not be determined from authenticated user.');
        });
    }
}
