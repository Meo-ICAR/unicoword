<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class DocumentTemplateVar extends Model
{
    protected $fillable = [
        'document_template_id',
        'var',
        'model',
        'value',
    ];

    protected $casts = [
        'value' => 'json',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the document template that owns the variable.
     */
    public function documentTemplate(): BelongsTo
    {
        return $this->belongsTo(DocumentTemplate::class);
    }

    /**
     * Get the model class name for this variable.
     */
    public function getModelClass(): ?string
    {
        return $this->model;
    }

    /**
     * Get the variable name.
     */
    public function getVariableName(): string
    {
        return $this->var;
    }

    /**
     * Get the value configuration.
     */
    public function getValueConfig(): mixed
    {
        return $this->value;
    }

    /**
     * Scope to get variables for a specific template.
     */
    public function scopeForTemplate($query, $templateId)
    {
        return $query->where('document_template_id', $templateId);
    }

    /**
     * Scope to get variables by variable name.
     */
    public function scopeByVar($query, $varName)
    {
        return $query->where('var', $varName);
    }
}
