<?php

namespace App\Models\CALL;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class CompanyUser extends Pivot
{
    use HasFactory;

    protected $table = 'company_user';

    protected $fillable = [
        'company_id',
        'user_id',
        'role',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the company that owns the company user.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(App\Models\CALL\Company::class);
    }

    /**
     * Get the user that owns the company user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(App\Models\CALL\User::class);
    }

    /**
     * Check if user is admin for this company
     */
    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'superadmin']);
    }

    /**
     * Check if user is superadmin for this company
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === 'superadmin';
    }

    /**
     * Check if user is regular user for this company
     */
    public function isUser(): bool
    {
        return $this->role === 'user';
    }

    /**
     * Get role label in Italian
     */
    public function getRoleLabelAttribute(): string
    {
        return match ($this->role) {
            'superadmin' => 'Super Amministratore',
            'admin' => 'Amministratore',
            'user' => 'Utente',
            default => ucfirst($this->role),
        };
    }
}
