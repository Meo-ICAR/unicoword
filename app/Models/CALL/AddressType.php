<?php

namespace App\Models\CALL;

use App\Models\CALL\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AddressType extends BaseModel
{
    /** @use HasFactory<\Database\Factories\AddressTypeFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'is_person',
    ];

    protected $casts = [
        'is_person' => 'boolean',
    ];

    public function addresses(): HasMany
    {
        return $this->hasMany(App\Models\CALL\Address::class);
    }

    public function scopeForPersons($query)
    {
        return $query->where('is_person', true);
    }

    public function scopeForCompanies($query)
    {
        return $query->where('is_person', false);
    }
}
