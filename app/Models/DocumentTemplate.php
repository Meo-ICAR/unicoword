<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class DocumentTemplate extends Model
{
    protected $fillable = [
        'name',
        'body',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the variables for this template.
     */
    public function variables(): HasMany
    {
        return $this->hasMany(DocumentTemplateVar::class);
    }

    /**
     * Get active templates only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the template body.
     */
    public function getBody(): string
    {
        return $this->body ?? '';
    }

    /**
     * Get template name.
     */
    public function getName(): string
    {
        return $this->name ?? '';
    }

    /**
     * Check if template is active.
     */
    public function isActive(): bool
    {
        return $this->is_active ?? false;
    }
}
