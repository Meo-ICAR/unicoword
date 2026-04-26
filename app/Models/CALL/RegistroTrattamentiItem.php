<?php

namespace App\Models\CALL;

use App\Models\CALL\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\CALL\BaseModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RegistroTrattamentiItem extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $connection = 'mariadb';

    protected $fillable = [
        'company_id',
        'Attivita',
        'Finalita',
        'Interessati',
        'Dati',
        'Giuridica',
        'Destinatari',
        'extraEU',
        'Conservazione',
        'Sicurezza',
    ];

    protected $casts = [
        'extraEU' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(App\Models\CALL\Company::class, 'company_id', 'id');
    }

    // Scopes
    public function scopeByCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeExtraEU($query, $isExtraEU = true)
    {
        return $query->where('extraEU', $isExtraEU);
    }

    public function scopeWithinEU($query)
    {
        return $query->where('extraEU', false);
    }

    public function scopeByLegalBasis($query, $legalBasis)
    {
        return $query->where('Giuridica', 'like', '%' . $legalBasis . '%');
    }

    // Accessors
    public function getExtraEuLabelAttribute(): string
    {
        return $this->extraEU ? 'Sì' : 'No';
    }

    public function getAttivitaSummaryAttribute(): string
    {
        return substr($this->Attivita, 0, 100) . (strlen($this->Attivita) > 100 ? '...' : '');
    }

    public function getFinalitaSummaryAttribute(): string
    {
        return substr($this->Finalita, 0, 100) . (strlen($this->Finalita) > 100 ? '...' : '');
    }

    public function getInteressatiSummaryAttribute(): string
    {
        return substr($this->Interessati, 0, 100) . (strlen($this->Interessati) > 100 ? '...' : '');
    }

    public function getDatiSummaryAttribute(): string
    {
        return substr($this->Dati, 0, 100) . (strlen($this->Dati) > 100 ? '...' : '');
    }

    // Helper methods
    public function hasExtraEuTransfer(): bool
    {
        return $this->extraEU;
    }

    public function getLegalBasisType(): ?string
    {
        if (str_contains($this->Giuridica, 'Art. 6')) {
            return 'GDPR Art. 6';
        } elseif (str_contains($this->Giuridica, 'Art. 9')) {
            return 'GDPR Art. 9';
        }
        return null;
    }

    public function getRetentionPeriod(): ?string
    {
        // Extract retention period from Conservazione text
        if (preg_match('/(\d+)\s*(anni|mesi|giorni)/i', $this->Conservazione, $matches)) {
            return $matches[0];
        }
        return null;
    }

    protected static function booted()
    {
        static::creating(function ($registroTrattamentiItem) {
            if (auth()->check() && method_exists(auth()->user(), 'current_company_id')) {
                $registroTrattamentiItem->company_id = auth()->user()->current_company_id;
            }
        });
    }
}
