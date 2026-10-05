<?php

namespace App\Models\CALL;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\CALL\BaseModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesInvoice extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'numero',
        'nome_file',
        'id_sdi',
        'data_invio',
        'data_documento',
        'tipo_documento',
        'tipo_cliente',
        'cliente',
        'partita_iva',
        'codice_fiscale',
        'indirizzo_telematico',
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
        'incassi',
        'data_incasso',
        'stato',
        'company_id',
    ];

    protected $casts = [
        'data_invio' => 'date',
        'data_documento' => 'date',
        'data_incasso' => 'date',
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
     * Get the client associated with this sales invoice based on VAT number
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

    public function scopeByCliente($query, $cliente)
    {
        return $query->where('cliente', 'like', "%{$cliente}%");
    }

    public function scopeByPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('data_documento', [$startDate, $endDate]);
    }

    public function scopePaid($query)
    {
        return $query->where('incassi', 'Incassata');
    }

    public function scopeUnpaid($query)
    {
        return $query->where('incassi', 'Non incassata');
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

    public function getDataInvioFormattedAttribute()
    {
        return $this->data_invio ? $this->data_invio->format('d/m/Y') : null;
    }

    public function getDataIncassoFormattedAttribute()
    {
        return $this->data_incasso ? $this->data_incasso->format('d/m/Y') : null;
    }

    // Methods
    public function isPaid(): bool
    {
        return $this->incassi === 'Incassata';
    }

    public function isDelivered(): bool
    {
        return $this->stato === 'Consegnata';
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

    protected static function booted()
    {
        static::creating(function ($salesInvoice) {
            if (auth()->check() && method_exists(auth()->user(), 'current_company_id')) {
                $salesInvoice->company_id = auth()->user()->current_company_id;
            }
        });
    }
}
