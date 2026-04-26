<?php

namespace App\Models\CALL;

use App\Models\CALL\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
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
        'zip_code',
        'address_type_id',
    ];

    public function addressable(): MorphTo
    {
        return $this->morphTo();
    }

    public function addressType(): BelongsTo
    {
        return $this->belongsTo(\App\Models\CALL\AddressType::class);
    }

    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->street,
            $this->city,
            $this->zip_code,
        ]);

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
