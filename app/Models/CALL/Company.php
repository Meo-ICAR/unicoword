<?php

namespace App\Models\CALL;

use App\Models\CALL\BaseModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class Company extends BaseModel
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'name',
        'vat_number',
        'owner',
        'sponsor',
        'company_type',
        'is_iso27001_certified',
        'contact_email',
        'dpo_email',
        'page_header',
        'page_footer',
        'user_id',
        'payment_last_date',
        'payment_startup',
    ];

    protected $casts = [
        'is_iso27001_certified' => 'boolean',
        'smtp_enabled' => 'boolean',
        'smtp_verify_ssl' => 'boolean',
        'payment_last_date' => 'datetime',
        'payment' => 'decimal:2',
        'payment_startup' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::created(function (Company $company) {
            // Only create admin user if no user_id was provided
            if (!$company->user_id) {
                $company->createAdminUser();
            }
        });
    }

    /**
     * Create an admin user for this company
     */
    public function createAdminUser(): User
    {
        $adminEmail = $this->generateAdminEmail();

        $user = User::create([
            'name' => 'Admin ' . $this->name,
            'email' => $adminEmail,
            'password' => Hash::make('password'),  // Default password
            'company_name' => $this->name,
            'is_approved' => true,
            'is_super_admin' => false,
            'current_company_id' => $this->id,
            'email_verified_at' => now(),
        ]);

        // Create CompanyUser relationship
        $this->users()->attach($user->id, [
            'role' => 'admin',
        ]);

        // Update company with user_id
        $this->update(['user_id' => $user->id]);

        return $user;
    }

    /**
     * Generate admin email for the company
     */
    protected function generateAdminEmail(): string
    {
        $baseEmail = Str::slug($this->name) . '-admin';
        $domain = 'unicocall.local';  // You can change this to your domain

        $email = $baseEmail . '@' . $domain;

        // Ensure email is unique
        $counter = 1;
        while (User::where('email', $email)->exists()) {
            $email = $baseEmail . '-' . $counter . '@' . $domain;
            $counter++;
        }

        return $email;
    }

    /**
     * Get the admin user for this company
     */
    public function getAdminUser(): ?User
    {
        return $this->users()->wherePivot('role', 'admin')->first();
    }

    /**
     * Get the admin user associated with this company
     */
    public function companyAdminUser()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Check if company has an admin user
     */
    public function hasAdminUser(): bool
    {
        return $this->users()->wherePivot('role', 'admin')->exists();
    }

    /**
     * Add user to company with specified role
     */
    public function addUser(User $user, string $role = 'user'): void
    {
        $this->users()->syncWithoutDetaching([$user->id => ['role' => $role]]);

        // Update user's current company if not set
        if (!$user->current_company_id) {
            $user->update(['current_company_id' => $this->id]);
        }
    }

    /**
     * Remove user from company
     */
    public function removeUser(User $user): void
    {
        $this->users()->detach($user->id);

        // Update user's current company if it was this company
        if ($user->current_company_id === $this->id) {
            // Set to first available company or null
            $firstCompany = $user->companies()->first();
            $user->update(['current_company_id' => $firstCompany?->id]);
        }
    }

    public function users()
    {
        return $this
            ->belongsToMany(User::class)
            ->using(CompanyUser::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    public function clients()
    {
        return $this->hasMany(Client::class);
    }

    public function branches()
    {
        return $this->hasMany(Branch::class);
    }

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    public function websites()
    {
        return $this->hasMany(Website::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    public function addresses(): MorphMany
    {
        return $this->morphMany(Address::class, 'addressable');
    }

    public function legalAddress(): MorphMany
    {
        return $this->morphMany(Address::class, 'addressable')->where('address_type_id', 10);
    }

    public function getPrimaryLegalAddressAttribute(): ?Address
    {
        return $this->legalAddress()->first();
    }

    public function getLegalAddressFormattedAttribute(): ?string
    {
        $legalAddress = $this->primaryLegalAddress;
        return $legalAddress?->full_address;
    }

    public function registroTrattamentiItems(): HasMany
    {
        return $this->hasMany(RegistroTrattamentiItem::class);
    }

    public function softwareApplications(): HasMany
    {
        return $this->hasMany(SoftwareApplication::class);
    }

    public function getMainAddressAttribute(): ?Address
    {
        return $this->addresses()->where('address_type_id', 5)->first();  // Sede Legale
    }

    public function scopeByCompanyType($query, $type)
    {
        return $query->where('company_type', $type);
    }

    public function scopeCertified($query)
    {
        return $query->where('is_iso27001_certified', true);
    }
}
