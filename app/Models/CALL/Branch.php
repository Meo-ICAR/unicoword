<?php

namespace App\Models\CALL;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\CALL\BaseModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Branch extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'address',
        'city',
        'province',
        'postal_code',
        'country',
        'phone',
        'email',
        'is_main_office',
        'notes',
    ];

    protected $casts = [
        'is_main_office' => 'boolean',
    ];

    // Scopes
    public function scopeMainOffice($query)
    {
        return $query->where('is_main_office', true);
    }

    public function scopeSecondaryOffice($query)
    {
        return $query->where('is_main_office', false);
    }

    public function scopeByCompany($query, string $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeByCity($query, string $city)
    {
        return $query->where('city', $city);
    }

    public function scopeByProvince($query, string $province)
    {
        return $query->where('province', $province);
    }

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(App\Models\CALL\Company::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(App\Models\CALL\Employee::class, 'company_branch_id');
    }

    public function supervisors(): HasMany
    {
        return $this
            ->hasMany(App\Models\CALL\Employee::class, 'company_branch_id')
            ->where('is_supervisor', true);
    }

    // Accessors
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address,
            $this->postal_code,
            $this->city,
            $this->province,
            $this->country,
        ]);

        return implode(', ', $parts);
    }

    public function getIsMainOfficeLabelAttribute(): string
    {
        return $this->is_main_office ? 'Sede Principale' : 'Sede Secondaria';
    }

    public function getLocationAttribute(): string
    {
        return $this->city ? "{$this->city} ({$this->province})" : 'N/D';
    }

    // Methods
    public function isMainOffice(): bool
    {
        return $this->is_main_office;
    }

    public function makeMainOffice(): void
    {
        // Remove main office status from other branches of the same company
        if ($this->company) {
            $this->company->branches()->where('id', '!=', $this->id)->update(['is_main_office' => false]);
        }

        $this->update(['is_main_office' => true]);
    }

    public function makeSecondaryOffice(): void
    {
        $this->update(['is_main_office' => false]);
    }

    public function hasEmployees(): bool
    {
        return $this->employees()->exists();
    }

    public function getEmployeeCount(): int
    {
        return $this->employees()->count();
    }

    public function getSupervisorCount(): int
    {
        return $this->supervisors()->count();
    }

    public function canBeDeleted(): bool
    {
        return !$this->is_main_office && !$this->hasEmployees();
    }

    // Static methods
    public static function getMainOfficeForCompany(string $companyId): ?Branch
    {
        return static::where('company_id', $companyId)
            ->where('is_main_office', true)
            ->first();
    }

    public static function getSecondaryOfficesForCompany(string $companyId): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('company_id', $companyId)
            ->where('is_main_office', false)
            ->get();
    }

    public static function getOfficesByProvince(string $province): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('province', $province)->get();
    }

    protected static function booted()
    {
        static::creating(function ($branch) {
            if (auth()->check() && method_exists(auth()->user(), 'current_company_id')) {
                $branch->company_id = auth()->user()->current_company_id;
            }
        });
    }
}
