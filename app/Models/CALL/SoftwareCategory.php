<?php

namespace App\Models\CALL;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\CALL\BaseModel;
use Illuminate\Database\Eloquent\Model;

class SoftwareCategory extends BaseModel
{
    use HasFactory;

    protected $connection = 'mysql_compliance';

    protected $fillable = [
        'name',
        'code',
        'description',
    ];

    public function softwareApplications(): HasMany
    {
        return $this->hasMany(App\Models\CALL\SoftwareApplication::class, 'software_category_id');
    }
}
