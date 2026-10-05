<?php

namespace App\Models\CALL;

use App\Models\CALL\BaseModel;
use Illuminate\Database\Eloquent\Model;

class Website extends BaseModel
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_typical' => 'boolean',
            'is_footercompilant' => 'boolean',
            'is_iso27001_certified' => 'boolean',
            'privacy_date' => 'date',
            'transparency_date' => 'date',
            'privacy_prior_date' => 'date',
            'transparency_prior_date' => 'date',
        ];
    }

    public function company()
    {
        return $this->belongsTo(App\Models\CALL\Company::class);
    }

    public function client()
    {
        return $this->belongsTo(App\Models\CALL\Client::class, 'clienti_id');
    }
}
