<?php

namespace App\Models\CALL;

use App\Models\CALL\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends BaseModel
{
    use SoftDeletes;

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
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function clientType(): BelongsTo
    {
        return $this->belongsTo(App\Models\CALL\ClientType::class);
    }

    public function leadSource(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'leadsource_id');
    }

    /**
     * Get the sales invoices for this client based on VAT number
     */
    public function salesInvoices(): HasMany
    {
        return $this->hasMany(App\Models\CALL\SalesInvoice::class, 'partita_iva', 'vat_number');
    }

    /**
     * Get the purchase invoices for this client based on VAT number
     */
    public function purchaseInvoices(): HasMany
    {
        return $this->hasMany(App\Models\CALL\PurchaseInvoice::class, 'partita_iva', 'vat_number');
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
        return $this->morphMany(App\Models\CALL\Subappalti::class, 'sub');
    }

    /**
     * Get all subappaltis where this client is the originator
     */
    public function subappaltisAsOriginator()
    {
        return $this->morphMany(App\Models\CALL\Subappalti::class, 'originator');
    }

    /**
     * Get all subappaltis where this client is the originator and sub is also a client
     */
    public function subappaltiClientToClient()
    {
        return $this
            ->morphMany(App\Models\CALL\Subappalti::class, 'originator')
            ->where('originator_type', 'client')
            ->where('sub_type', 'client');
    }

    /**
     * Get all subappaltis where this client is the originator and sub is an employee
     */
    public function subappaltiClientToEmployee()
    {
        return $this
            ->morphMany(App\Models\CALL\Subappalti::class, 'originator')
            ->where('originator_type', 'client')
            ->where('sub_type', 'employee');
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
