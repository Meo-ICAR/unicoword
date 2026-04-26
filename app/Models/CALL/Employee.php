<?php

namespace App\Models\CALL;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\CALL\BaseModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'company_branch_id',
        'user_id',
        'coordinated_by_id',
        'name',
        'email',
        'phone',
        'fiscal_code',
        'vat_number',
        'birth_date',
        'birth_place',
        'birth_province',
        'gender',
        'nationality',
        'address',
        'city',
        'province',
        'postal_code',
        'country',
        'hiring_date',
        'termination_date',
        'employment_type',
        'role',
        'department',
        'salary',
        'is_supervisor',
        'is_structure',
        'is_ghost',
        'notes',
    ];

    protected $casts = [
        'hiring_date' => 'date',
        'termination_date' => 'date',
        'birth_date' => 'date',
        'salary' => 'decimal:2',
        'is_supervisor' => 'boolean',
        'is_structure' => 'boolean',
        'is_ghost' => 'boolean',
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->whereNull('termination_date');
    }

    public function scopeTerminated($query)
    {
        return $query->whereNotNull('termination_date');
    }

    public function scopeSupervisors($query)
    {
        return $query->where('is_supervisor', true);
    }

    public function scopeRegularEmployees($query)
    {
        return $query->where('is_supervisor', false);
    }

    public function scopeByCompany($query, string $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeByBranch($query, string $branchId)
    {
        return $query->where('company_branch_id', $branchId);
    }

    public function scopeByDepartment($query, string $department)
    {
        return $query->where('department', $department);
    }

    public function scopeByRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    public function scopeStructure($query)
    {
        return $query->where('is_structure', true);
    }

    public function scopeGhost($query)
    {
        return $query->where('is_ghost', true);
    }

    public function scopeReal($query)
    {
        return $query->where('is_ghost', false);
    }

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(App\Models\CALL\Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(App\Models\CALL\Branch::class, 'company_branch_id');
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'coordinated_by_id');
    }

    public function subordinates(): HasMany
    {
        return $this->hasMany(Employee::class, 'coordinated_by_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(App\Models\CALL\User::class);
    }

    // Accessors - Note: employees table only has 'name' field, not first_name/last_name

    public function getIsActiveAttribute(): bool
    {
        return is_null($this->termination_date);
    }

    public function getIsActiveLabelAttribute(): string
    {
        return $this->is_active ? 'Attivo' : 'Terminato';
    }

    public function getIsSupervisorLabelAttribute(): string
    {
        return $this->is_supervisor ? 'Supervisore' : 'Dipendente';
    }

    public function getEmploymentStatusLabelAttribute(): string
    {
        if ($this->is_ghost) {
            return 'Fantasma';
        }
        if ($this->is_structure) {
            return 'Struttura';
        }
        return $this->is_active ? 'Attivo' : 'Terminato';
    }

    public function getAgeAttribute(): ?int
    {
        return $this->birth_date ? $this->birth_date->age : null;
    }

    public function getYearsOfServiceAttribute(): ?int
    {
        if (!$this->hiring_date) {
            return null;
        }

        $endDate = $this->termination_date ?? now();
        return $this->hiring_date->diffInYears($endDate);
    }

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

    public function getFormattedSalaryAttribute(): string
    {
        return $this->salary ? 'EUR ' . number_format($this->salary, 2, ',', '.') : 'N/D';
    }

    // Methods
    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function isSupervisor(): bool
    {
        return $this->is_supervisor;
    }

    public function isStructure(): bool
    {
        return $this->is_structure;
    }

    public function isGhost(): bool
    {
        return $this->is_ghost;
    }

    public function isReal(): bool
    {
        return !$this->is_ghost;
    }

    public function terminate(\DateTime|string $terminationDate = null): void
    {
        $date = $terminationDate ?? now();
        $this->update(['termination_date' => $date]);
    }

    public function reactivate(): void
    {
        $this->update(['termination_date' => null]);
    }

    public function makeSupervisor(): void
    {
        $this->update(['is_supervisor' => true]);
    }

    public function makeRegularEmployee(): void
    {
        $this->update(['is_supervisor' => false]);
    }

    public function assignToBranch(string $branchId): void
    {
        $this->update(['company_branch_id' => $branchId]);
    }

    public function assignSupervisor(?string $supervisorId): void
    {
        $this->update(['coordinated_by_id' => $supervisorId]);
    }

    public function hasSubordinates(): bool
    {
        return $this->subordinates()->exists();
    }

    public function getSubordinateCount(): int
    {
        return $this->subordinates()->count();
    }

    public function getActiveSubordinateCount(): int
    {
        return $this->subordinates()->active()->count();
    }

    public function canBeDeleted(): bool
    {
        return !$this->hasSubordinates() && !$this->is_structure;
    }

    public function getHierarchyLevel(): int
    {
        $level = 0;
        $current = $this;

        while ($current->supervisor) {
            $level++;
            $current = $current->supervisor;
        }

        return $level;
    }

    public function getDirectAndIndirectSubordinates(): \Illuminate\Database\Eloquent\Collection
    {
        $allSubordinates = collect();
        $directSubordinates = $this->subordinates;

        foreach ($directSubordinates as $subordinate) {
            $allSubordinates->push($subordinate);
            $allSubordinates = $allSubordinates->merge($subordinate->getDirectAndIndirectSubordinates());
        }

        return $allSubordinates;
    }

    // Static methods
    public static function getActiveEmployees(): \Illuminate\Database\Eloquent\Collection
    {
        return static::active()->get();
    }

    public static function getSupervisors(): \Illuminate\Database\Eloquent\Collection
    {
        return static::supervisors()->active()->get();
    }

    public static function getByCompany(string $companyId): \Illuminate\Database\Eloquent\Collection
    {
        return static::byCompany($companyId)->get();
    }

    public static function getByBranch(string $branchId): \Illuminate\Database\Eloquent\Collection
    {
        return static::byBranch($branchId)->get();
    }

    public static function getDepartmentEmployees(string $department): \Illuminate\Database\Eloquent\Collection
    {
        return static::byDepartment($department)->active()->get();
    }

    public static function getTopLevelSupervisors(): \Illuminate\Database\Eloquent\Collection
    {
        return static::supervisors()
            ->whereNull('coordinated_by_id')
            ->active()
            ->get();
    }

    public static function getWithSubordinateCount(): \Illuminate\Database\Eloquent\Collection
    {
        return static::withCount('subordinates')->get();
    }

    public static function getEmployeeStatistics(): array
    {
        return [
            'total' => static::count(),
            'active' => static::active()->count(),
            'terminated' => static::terminated()->count(),
            'supervisors' => static::supervisors()->active()->count(),
            'regular' => static::regularEmployees()->active()->count(),
            'structure' => static::structure()->count(),
            'ghost' => static::ghost()->count(),
        ];
    }

    /**
     * Get all subappaltis where this employee is the sub contractor
     */
    public function subappaltisAsSub()
    {
        return $this->morphMany(App\Models\CALL\Subappalti::class, 'sub');
    }

    /**
     * Get all subappaltis where this employee is the originator
     */
    public function subappaltisAsOriginator()
    {
        return $this->morphMany(App\Models\CALL\Subappalti::class, 'originator');
    }

    protected static function booted()
    {
        static::creating(function ($employee) {
            if (auth()->check() && method_exists(auth()->user(), 'current_company_id')) {
                $employee->company_id = auth()->user()->current_company_id;
            }
        });
    }
}
