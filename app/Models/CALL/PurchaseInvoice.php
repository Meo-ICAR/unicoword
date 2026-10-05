<?php

namespace App\Models\CALL;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\CALL\BaseModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseInvoice extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'numero',
        'nome_file',
        'id_sdi',
        'data_ricezione',
        'data_documento',
        'tipo_documento',
        'fornitore',
        'partita_iva',
        'codice_fiscale',
        'metodo_pagamento',
        'totale_imponibile',
        'totale_escluso_iva_n1',
        'totale_non_soggetto_iva_n2',
        'totale_non_imponibile_iva_n3',
        'totale_esente_iva_n4',
        'totale_regime_margine_iva_n5',
        'totale_inversione_contabile_n6',
        'totale_iva_assolta_altro_stato_ue_n7',
        'totale_iva',
        'totale_documento',
        'netto_a_pagare',
        'pagamenti',
        'data_pagamento',
        'stato',
        'company_id',
    ];

    protected $casts = [
        'data_ricezione' => 'date',
        'data_documento' => 'date',
        'data_pagamento' => 'date',
        'totale_imponibile' => 'decimal:2',
        'totale_escluso_iva_n1' => 'decimal:2',
        'totale_non_soggetto_iva_n2' => 'decimal:2',
        'totale_non_imponibile_iva_n3' => 'decimal:2',
        'totale_esente_iva_n4' => 'decimal:2',
        'totale_regime_margine_iva_n5' => 'decimal:2',
        'totale_inversione_contabile_n6' => 'decimal:2',
        'totale_iva_assolta_altro_stato_ue_n7' => 'decimal:2',
        'totale_iva' => 'decimal:2',
        'totale_documento' => 'decimal:2',
        'netto_a_pagare' => 'decimal:2',
    ];

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(App\Models\CALL\Company::class);
    }

    /**
     * Get the client associated with this purchase invoice based on VAT number
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(App\Models\CALL\Client::class, 'partita_iva', 'vat_number');
    }

    // Scopes
    public function scopeByCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeByStato($query, $stato)
    {
        return $query->where('stato', $stato);
    }

    public function scopeByFornitore($query, $fornitore)
    {
        return $query->where('fornitore', 'like', "%{$fornitore}%");
    }

    public function scopeByPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('data_documento', [$startDate, $endDate]);
    }

    public function scopePaid($query)
    {
        return $query->where('pagamenti', 'Pagata');
    }

    public function scopeUnpaid($query)
    {
        return $query->where('pagamenti', 'Non pagata');
    }

    public function scopeRead($query)
    {
        return $query->where('stato', 'Letta');
    }

    public function scopeUnread($query)
    {
        return $query->where('stato', '!=', 'Letta');
    }

    // Accessors
    public function getTotaleImponibileFormattedAttribute()
    {
        return 'EUR ' . number_format($this->totale_imponibile, 2, ',', '.');
    }

    public function getTotaleDocumentoFormattedAttribute()
    {
        return 'EUR ' . number_format($this->totale_documento, 2, ',', '.');
    }

    public function getNettoAPagareFormattedAttribute()
    {
        return 'EUR ' . number_format($this->netto_a_pagare, 2, ',', '.');
    }

    public function getDataDocumentoFormattedAttribute()
    {
        return $this->data_documento ? $this->data_documento->format('d/m/Y') : null;
    }

    public function getDataRicezioneFormattedAttribute()
    {
        return $this->data_ricezione ? $this->data_ricezione->format('d/m/Y') : null;
    }

    public function getDataPagamentoFormattedAttribute()
    {
        return $this->data_pagamento ? $this->data_pagamento->format('d/m/Y') : null;
    }

    // Methods
    public function isPaid(): bool
    {
        return $this->pagamenti === 'Pagata';
    }

    public function isRead(): bool
    {
        return $this->stato === 'Letta';
    }

    public function getImportoIva(): float
    {
        return $this->totale_documento - $this->totale_imponibile;
    }

    public function getAliquotaIva(): float
    {
        if ($this->totale_imponibile > 0) {
            return ($this->getImportoIva() / $this->totale_imponibile) * 100;
        }
        return 0;
    }

    public function markAsRead(): bool
    {
        return $this->update(['stato' => 'Letta']);
    }

    public function markAsPaid(): bool
    {
        return $this->update([
            'pagamenti' => 'Pagata',
            'data_pagamento' => now(),
        ]);
    }

    protected static function booted()
    {
        static::creating(function ($purchaseInvoice) {
            if (auth()->check() && method_exists(auth()->user(), 'current_company_id')) {
                $purchaseInvoice->company_id = auth()->user()->current_company_id;
            }
        });
    }
}
