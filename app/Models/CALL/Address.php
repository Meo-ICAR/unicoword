<?php

namespace App\Models\CALL;

use App\Models\CALL\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Address extends BaseModel
{
    /** @use HasFactory<\Database\Factories\AddressFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'addressable_type',
        'addressable_id',
        'name',
        'numero',
        'street',
        'city',
        'province',
        'zip_code',
        'address_type_id',
    ];

    public function addressable(): MorphTo
    {
        return $this->morphTo();
    }

    public function addressType(): BelongsTo
    {
        return $this->belongsTo(AddressType::class);
    }

    public function getFullAddressAttribute(): string
    {
        $parts = [];

        // Via e numero civico
        if ($this->street) {
            $parts[] = $this->street;
        }
        if ($this->numero) {
            $parts[] = $this->numero;
        }

        // Città con provincia tra parentesi
        if ($this->city) {
            $cityPart = $this->city;
            if ($this->province) {
                $cityPart .= ' (' . strtoupper($this->province) . ')';
            }
            $parts[] = $cityPart;
        }

        // CAP (se presente)
        if ($this->zip_code) {
            $parts[] = $this->zip_code;
        }

        return implode(', ', $parts);
    }

    public function scopeByType($query, $typeId)
    {
        return $query->where('address_type_id', $typeId);
    }

    public function scopeForModel($query, $model)
    {
        return $query
            ->where('addressable_type', get_class($model))
            ->where('addressable_id', $model->getKey());
    }
}
