<?php

namespace App\Models\CALL;

use App\Models\CALL\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;

class Client extends BaseModel
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_person' => 'boolean',
            'is_company_consultant' => 'boolean',
            'is_lead' => 'boolean',
            'is_structure' => 'boolean',
            'is_regulatory' => 'boolean',
            'is_ghost' => 'boolean',
            'is_sales' => 'boolean',
            'is_pep' => 'boolean',
            'is_sanctioned' => 'boolean',
            'is_remote_interaction' => 'boolean',
            'is_requiredApprovation' => 'boolean',
            'is_approved' => 'boolean',
            'is_anonymous' => 'boolean',
            'is_client' => 'boolean',
            'is_consultant_gdpr' => 'boolean',
            'privacy_consent' => 'boolean',
            'is_art108' => 'boolean',
            'contract_signed_at' => 'datetime',
            'acquired_at' => 'datetime',
            'general_consent_at' => 'datetime',
            'privacy_policy_read_at' => 'datetime',
            'consent_special_categories_at' => 'datetime',
            'consent_sic_at' => 'datetime',
            'consent_marketing_at' => 'datetime',
            'consent_profiling_at' => 'datetime',
            'blacklist_at' => 'datetime',
            'categorie_dati' => 'array',
            'nomina_at' => 'datetime',
            'documents' => 'array',
            'company_type' => 'string',
            'salary' => 'decimal:2',
            'salary_quote' => 'decimal:2',
        ];
    }

    /**
     * Get the client's full name
     */
    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->name);
    }

    /**
     * Get the client's display name
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->is_person) {
            return $this->full_name;
        }
        return $this->name;
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function clientType(): BelongsTo
    {
        return $this->belongsTo(ClientType::class);
    }

    public function leadSource(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'leadsource_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'deleted_by');
    }

    /**
     * Get the sales invoices for this client based on VAT number
     */
    public function salesInvoices(): HasMany
    {
        return $this->hasMany(SalesInvoice::class, 'partita_iva', 'vat_number');
    }

    /**
     * Get the purchase invoices for this client based on VAT number
     */
    public function purchaseInvoices(): HasMany
    {
        return $this->hasMany(PurchaseInvoice::class, 'partita_iva', 'vat_number');
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

    /**
     * Get all invoices (sales + purchase) for this client
     * Returns a collection with both sales and purchase invoices
     */
    public function getAllInvoices()
    {
        $salesInvoices = $this->salesInvoices()->get()->map(function ($invoice) {
            $invoice->type = 'sales';
            $invoice->label = 'Fattura Emessa';
            return $invoice;
        });

        $purchaseInvoices = $this->purchaseInvoices()->get()->map(function ($invoice) {
            $invoice->type = 'purchase';
            $invoice->label = 'Fattura Ricevuta';
            return $invoice;
        });

        return $salesInvoices->concat($purchaseInvoices)->sortBy('data_documento');
    }

    /**
     * Get total amount of unpaid sales invoices
     */
    public function getUnpaidSalesInvoicesTotal(): float
    {
        return $this->salesInvoices()->where('incassi', 'Non incassata')->sum('netto_a_pagare');
    }

    /**
     * Get total amount of unpaid purchase invoices
     */
    public function getUnpaidPurchaseInvoicesTotal(): float
    {
        return $this->purchaseInvoices()->where('pagamenti', 'Non pagata')->sum('netto_a_pagare');
    }

    /**
     * Get total IVA amount from sales invoices
     */
    public function getTotalSalesIva(): float
    {
        return $this->salesInvoices()->sum('totale_iva');
    }

    /**
     * Get total IVA amount from purchase invoices (deductible)
     */
    public function getTotalPurchaseIva(): float
    {
        return $this->purchaseInvoices()->sum('totale_iva');
    }

    /**
     * Get net IVA position (sales IVA - purchase IVA)
     */
    public function getNetIvaPosition(): float
    {
        return $this->getTotalSalesIva() - $this->getTotalPurchaseIva();
    }

    /**
     * Get all subappaltis where this client is the sub contractor
     */
    public function subappaltisAsSub()
    {
        return $this->morphMany(Subappalti::class, 'sub');
    }

    /**
     * Get all subappaltis where this client is the originator
     */
    public function subappaltisAsOriginator()
    {
        return $this->morphMany(Subappalti::class, 'originator');
    }

    /**
     * Get all subappaltis where this client is the originator and sub is also a client
     */
    public function subappaltiClientToClient()
    {
        return $this
            ->morphMany(Subappalti::class, 'originator')
            ->where('originator_type', 'client')
            ->where('sub_type', 'client');
    }

    /**
     * Get all subappaltis where this client is the originator and sub is an employee
     */
    public function subappaltiClientToEmployee()
    {
        return $this
            ->morphMany(Subappalti::class, 'originator')
            ->where('originator_type', 'client')
            ->where('sub_type', 'employee');
    }

    /**
     * Scope to get only active clients
     */
    public function scopeActive($query)
    {
        return $query->where('is_approved', true)->whereNull('deleted_at');
    }

    /**
     * Scope to get only person clients
     */
    public function scopePersons($query)
    {
        return $query->where('is_person', true);
    }

    /**
     * Scope to get only company clients
     */
    public function scopeCompanies($query)
    {
        return $query->where('is_person', false);
    }

    /**
     * Scope to get only leads
     */
    public function scopeLeads($query)
    {
        return $query->where('is_lead', true);
    }

    /**
     * Scope to get only GDPR consultants
     */
    public function scopeGdprConsultants($query)
    {
        return $query->where('is_consultant_gdpr', true);
    }

    /**
     * Scope to get only PEP (Politically Exposed Persons)
     */
    public function scopePep($query)
    {
        return $query->where('is_pep', true);
    }

    /**
     * Scope to get only sanctioned persons
     */
    public function scopeSanctioned($query)
    {
        return $query->where('is_sanctioned', true);
    }

    /**
     * Scope to get only ghost profiles
     */
    public function scopeGhost($query)
    {
        return $query->where('is_ghost', true);
    }

    /**
     * Scope to get by status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get clients requiring approval
     */
    public function scopeRequiresApproval($query)
    {
        return $query->where('is_requiredApprovation', true);
    }

    /**
     * Scope to get blacklisted clients
     */
    public function scopeBlacklisted($query)
    {
        return $query->whereNotNull('blacklist_at');
    }

    /**
     * Check if client is blacklisted
     */
    public function isBlacklisted(): bool
    {
        return !is_null($this->blacklist_at);
    }

    /**
     * Check if client has privacy consent
     */
    public function hasPrivacyConsent(): bool
    {
        return $this->privacy_consent;
    }

    /**
     * Check if client has general consent
     */
    public function hasGeneralConsent(): bool
    {
        return !is_null($this->general_consent_at);
    }

    /**
     * Get client status label
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'raccolta_dati' => 'Raccolta Dati',
            'contatto_iniziale' => 'Contatto Iniziale',
            'qualificazione' => 'Qualificazione',
            'proposta' => 'Proposta',
            'negoziazione' => 'Negoziazione',
            'chiuso' => 'Chiuso',
            'perso' => 'Perso',
            default => ucfirst($this->status),
        };
    }

    /**
     * Get client type label
     */
    public function getTypeLabelAttribute(): string
    {
        if ($this->is_person) {
            return 'Persona Fisica';
        }
        return 'Persona Giuridica';
    }

    /**
     * Get formatted VAT number
     */
    public function getFormattedVatNumberAttribute(): string
    {
        if (!$this->vat_number) {
            return 'N/A';
        }

        // Format Italian VAT numbers
        if (str_starts_with($this->vat_number, 'IT')) {
            return $this->vat_number;
        }

        return 'IT' . $this->vat_number;
    }

    /**
     * Get formatted tax code
     */
    public function getFormattedTaxCodeAttribute(): string
    {
        return $this->tax_code ?? 'N/A';
    }

    /**
     * Get primary contact email
     */
    public function getPrimaryEmailAttribute(): string
    {
        return $this->privacy_contact_email ?? $this->email ?? 'N/A';
    }

    /**
     * Check if client can be contacted for marketing
     */
    public function canContactForMarketing(): bool
    {
        return $this->hasGeneralConsent() &&
            !is_null($this->consent_marketing_at) &&
            !$this->isBlacklisted();
    }

    /**
     * Check if client can be contacted for profiling
     */
    public function canContactForProfiling(): bool
    {
        return $this->hasGeneralConsent() &&
            !is_null($this->consent_profiling_at) &&
            !$this->isBlacklisted();
    }

    /**
     * Get risk level based on various factors
     */
    public function getRiskLevelAttribute(): string
    {
        $risk = 0;

        if ($this->is_pep)
            $risk += 3;
        if ($this->is_sanctioned)
            $risk += 5;
        if ($this->is_ghost)
            $risk += 2;
        if ($this->is_remote_interaction)
            $risk += 1;

        return match (true) {
            $risk >= 5 => 'High',
            $risk >= 3 => 'Medium',
            $risk >= 1 => 'Low',
            default => 'Minimal',
        };
    }

    protected static function booted()
    {
        static::creating(function ($client) {
            if (auth()->check() && method_exists(auth()->user(), 'current_company_id')) {
                $client->company_id = auth()->user()->current_company_id;
            }
        });
    }
}
