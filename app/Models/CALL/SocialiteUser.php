<?php

namespace App\Models\CALL;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\CALL\BaseModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SocialiteUser extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'provider',
        'provider_id',
        'provider_name',
        'provider_email',
        'provider_avatar',
        'provider_token',
        'provider_refresh_token',
        'provider_expires_in',
        'provider_token_secret',
        'is_personal',
        'is_pec',
        'nickname',
        'name',
        'email',
        'avatar',
        'profile_url',
        'website',
        'location',
        'bio',
        'gender',
        'locale',
        'timezone',
        'verified',
        'public_profile',
        'last_login_at',
        'login_count',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'provider_expires_in' => 'integer',
        'is_personal' => 'boolean',
        'is_pec' => 'boolean',
        'verified' => 'boolean',
        'public_profile' => 'boolean',
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
        'login_count' => 'integer',
    ];

    protected $hidden = [
        'provider_token',
        'provider_refresh_token',
        'provider_token_secret',
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

    public function scopeByProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    public function scopeByUser($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopePersonal($query)
    {
        return $query->where('is_personal', true);
    }

    public function scopeBusiness($query)
    {
        return $query->where('is_personal', false);
    }

    public function scopePec($query)
    {
        return $query->where('is_pec', true);
    }

    public function scopeVerified($query)
    {
        return $query->where('verified', true);
    }

    public function scopePublic($query)
    {
        return $query->where('public_profile', true);
    }

    public function scopePrivate($query)
    {
        return $query->where('public_profile', false);
    }

    public function scopeRecentLogins($query, int $days = 30)
    {
        return $query->where('last_login_at', '>=', now()->subDays($days));
    }

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(App\Models\CALL\User::class);
    }

    // Accessors
    public function getProviderDisplayNameAttribute(): string
    {
        return match ($this->provider) {
            'google' => 'Google',
            'facebook' => 'Facebook',
            'twitter' => 'Twitter',
            'linkedin' => 'LinkedIn',
            'github' => 'GitHub',
            'microsoft' => 'Microsoft',
            'apple' => 'Apple',
            default => ucfirst($this->provider),
        };
    }

    public function getAccountTypeLabelAttribute(): string
    {
        if ($this->is_personal) {
            return $this->is_pec ? 'Personale (PEC)' : 'Personale';
        }
        return 'Aziendale';
    }

    public function getIsActiveLabelAttribute(): string
    {
        return $this->is_active ? 'Attivo' : 'Inattivo';
    }

    public function getVerifiedLabelAttribute(): string
    {
        return $this->verified ? 'Verificato' : 'Non verificato';
    }

    public function getProfileTypeLabelAttribute(): string
    {
        return $this->public_profile ? 'Pubblico' : 'Privato';
    }

    public function getFormattedLastLoginAttribute(): string
    {
        return $this->last_login_at ? $this->last_login_at->format('d/m/Y H:i') : 'Mai';
    }

    public function getDaysSinceLastLoginAttribute(): ?int
    {
        return $this->last_login_at ? $this->last_login_at->diffInDays(now()) : null;
    }

    public function getLoginFrequencyAttribute(): string
    {
        if (!$this->last_login_at || $this->login_count <= 1) {
            return 'Primo accesso';
        }

        $daysSinceFirst = $this->created_at->diffInDays(now());
        $frequency = $this->login_count / max($daysSinceFirst, 1);

        if ($frequency >= 1) {
            return 'Giornaliero';
        } elseif ($frequency >= 0.5) {
            return 'Settimanale';
        } elseif ($frequency >= 0.1) {
            return 'Mensile';
        } else {
            return 'Occasionale';
        }
    }

    public function getAvatarUrlAttribute(): string
    {
        return $this->avatar ?? $this->provider_avatar ?? '/images/default-avatar.png';
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->name ?? $this->nickname ?? $this->email ?? $this->provider_name ?? 'Utente Social';
    }

    // Methods
    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function isPersonal(): bool
    {
        return $this->is_personal;
    }

    public function isBusiness(): bool
    {
        return !$this->is_personal;
    }

    public function isPec(): bool
    {
        return $this->is_pec;
    }

    public function isVerified(): bool
    {
        return $this->verified;
    }

    public function isPublicProfile(): bool
    {
        return $this->public_profile;
    }

    public function activate(): void
    {
        $this->update(['is_active' => true]);
    }

    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    public function recordLogin(): void
    {
        $this->increment('login_count');
        $this->update(['last_login_at' => now()]);
    }

    public function updateProfile(array $data): void
    {
        $this->update($data);
    }

    public function revokeAccess(): void
    {
        $this->update([
            'provider_token' => null,
            'provider_refresh_token' => null,
            'provider_token_secret' => null,
            'is_active' => false,
        ]);
    }

    public function hasValidToken(): bool
    {
        return !empty($this->provider_token);
    }

    public function tokenExpired(): bool
    {
        if (!$this->provider_expires_in || !$this->updated_at) {
            return false;
        }

        $expiryTime = $this->updated_at->addSeconds($this->provider_expires_in);
        return now()->gt($expiryTime);
    }

    public function canBeDeleted(): bool
    {
        return true;  // Socialite users can be safely deleted
    }

    public function getProfileSummary(): array
    {
        return [
            'provider' => $this->provider_display_name,
            'display_name' => $this->display_name,
            'email' => $this->email,
            'account_type' => $this->account_type_label,
            'verified' => $this->verified_label,
            'last_login' => $this->formatted_last_login,
            'login_count' => $this->login_count,
            'frequency' => $this->login_frequency,
            'days_since_login' => $this->days_since_last_login,
        ];
    }

    // Static methods
    public static function getByProvider(string $provider, string $providerId): ?SocialiteUser
    {
        return static::where('provider', $provider)
            ->where('provider_id', $providerId)
            ->first();
    }

    public static function getActiveProviders(): \Illuminate\Database\Eloquent\Collection
    {
        return static::active()->distinct()->pluck('provider');
    }

    public static function getProviderStatistics(): array
    {
        $stats = [];
        $providers = static::selectRaw('provider, COUNT(*) as count')
            ->groupBy('provider')
            ->get();

        foreach ($providers as $provider) {
            $stats[$provider->provider] = $provider->count;
        }

        return $stats;
    }

    public static function getLoginStatistics(): array
    {
        return [
            'total_logins' => static::sum('login_count'),
            'active_users' => static::active()->count(),
            'recent_logins' => static::recentLogins()->count(),
            'never_logged_in' => static::whereNull('last_login_at')->count(),
            'daily_active' => static::recentLogins(1)->count(),
            'weekly_active' => static::recentLogins(7)->count(),
            'monthly_active' => static::recentLogins(30)->count(),
        ];
    }

    public static function getMostActiveUsers(int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return static::orderBy('login_count', 'desc')
            ->orderBy('last_login_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public static function getInactiveUsers(int $days = 90): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('last_login_at', '<', now()->subDays($days))
            ->orWhereNull('last_login_at')
            ->get();
    }

    public static function createOrUpdateFromSocialite(array $userData, int $userId): SocialiteUser
    {
        $socialiteUser = static::getByProvider($userData['provider'], $userData['id']);

        if ($socialiteUser) {
            $socialiteUser->update([
                'provider_token' => $userData['token'] ?? null,
                'provider_refresh_token' => $userData['refresh_token'] ?? null,
                'provider_expires_in' => $userData['expires_in'] ?? null,
                'provider_name' => $userData['name'] ?? null,
                'provider_email' => $userData['email'] ?? null,
                'provider_avatar' => $userData['avatar'] ?? null,
                'nickname' => $userData['nickname'] ?? null,
                'name' => $userData['name'] ?? null,
                'email' => $userData['email'] ?? null,
                'avatar' => $userData['avatar'] ?? null,
                'profile_url' => $userData['profile_url'] ?? null,
                'website' => $userData['website'] ?? null,
                'location' => $userData['location'] ?? null,
                'bio' => $userData['bio'] ?? null,
                'verified' => $userData['verified'] ?? false,
                'is_active' => true,
            ]);
        } else {
            $socialiteUser = static::create([
                'user_id' => $userId,
                'provider' => $userData['provider'],
                'provider_id' => $userData['id'],
                'provider_token' => $userData['token'] ?? null,
                'provider_refresh_token' => $userData['refresh_token'] ?? null,
                'provider_expires_in' => $userData['expires_in'] ?? null,
                'provider_name' => $userData['name'] ?? null,
                'provider_email' => $userData['email'] ?? null,
                'provider_avatar' => $userData['avatar'] ?? null,
                'nickname' => $userData['nickname'] ?? null,
                'name' => $userData['name'] ?? null,
                'email' => $userData['email'] ?? null,
                'avatar' => $userData['avatar'] ?? null,
                'profile_url' => $userData['profile_url'] ?? null,
                'website' => $userData['website'] ?? null,
                'location' => $userData['location'] ?? null,
                'bio' => $userData['bio'] ?? null,
                'verified' => $userData['verified'] ?? false,
                'is_active' => true,
                'login_count' => 0,
            ]);
        }

        return $socialiteUser;
    }
}
