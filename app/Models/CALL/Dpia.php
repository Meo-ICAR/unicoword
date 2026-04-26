<?php

namespace App\Models\CALL;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\CALL\BaseModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Dpia extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'registro_trattamenti_item_id',
        'description_of_processing',
        'necessity_assessment',
        'is_necessary',
        'is_proportional',
        'status',
        'dpo_opinion',
        'completion_date',
        'next_review_date',
        'company_id',
    ];

    protected $casts = [
        'is_necessary' => 'boolean',
        'is_proportional' => 'boolean',
        'completion_date' => 'date',
        'next_review_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(App\Models\CALL\Company::class);
    }

    public function registroTrattamentiItem(): BelongsTo
    {
        return $this->belongsTo(App\Models\CALL\RegistroTrattamentiItem::class);
    }

    public function dpiaItems(): HasMany
    {
        return $this->hasMany(App\Models\CALL\DpiaItem::class);
    }

    // Scopes
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeUnderReview($query)
    {
        return $query->where('status', 'under_review');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeByCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeNecessary($query)
    {
        return $query->where('is_necessary', true);
    }

    public function scopeNotNecessary($query)
    {
        return $query->where('is_necessary', false);
    }

    public function scopeProportional($query)
    {
        return $query->where('is_proportional', true);
    }

    public function scopeNotProportional($query)
    {
        return $query->where('is_proportional', false);
    }

    public function scopeWithDpoOpinion($query)
    {
        return $query->whereNotNull('dpo_opinion');
    }

    public function scopeWithoutDpoOpinion($query)
    {
        return $query->whereNull('dpo_opinion');
    }

    public function scopeDueForReview($query)
    {
        return $query->where('next_review_date', '<=', now());
    }

    // Accessors
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'Bozza',
            'under_review' => 'In Revisione',
            'completed' => 'Completato',
            default => 'Sconosciuto',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'gray',
            'under_review' => 'warning',
            'completed' => 'success',
            default => 'gray',
        };
    }

    public function getFormattedCompletionDateAttribute(): string
    {
        return $this->completion_date ? $this->completion_date->format('d/m/Y') : 'N/A';
    }

    public function getFormattedNextReviewDateAttribute(): string
    {
        return $this->next_review_date ? $this->next_review_date->format('d/m/Y') : 'N/A';
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->next_review_date && $this->next_review_date->isPast();
    }

    public function getDaysUntilReviewAttribute(): int
    {
        if (!$this->next_review_date) {
            return -1;
        }

        return now()->diffInDays($this->next_review_date, false);
    }

    // Methods
    public function markAsDraft(): void
    {
        $this->status = 'draft';
        $this->save();
    }

    public function markAsUnderReview(): void
    {
        $this->status = 'under_review';
        $this->save();
    }

    public function markAsCompleted(): void
    {
        $this->status = 'completed';
        $this->completion_date = now();
        $this->next_review_date = now()->addYear();  // Revisione annuale di default
        $this->save();
    }

    public function setDpoOpinion(string $opinion): void
    {
        $this->dpo_opinion = $opinion;
        $this->save();
    }

    public function extendReviewDate(\DateInterval $interval): void
    {
        if ($this->next_review_date) {
            $this->next_review_date = $this->next_review_date->add($interval);
            $this->save();
        }
    }

    public function isReadyForCompletion(): bool
    {
        return $this->is_necessary &&
            $this->is_proportional &&
            !empty($this->dpo_opinion) &&
            !empty($this->description_of_processing) &&
            !empty($this->necessity_assessment);
    }

    // Constants
    const STATUS_DRAFT = 'draft';
    const STATUS_UNDER_REVIEW = 'under_review';
    const STATUS_COMPLETED = 'completed';

    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_DRAFT => 'Bozza',
            self::STATUS_UNDER_REVIEW => 'In Revisione',
            self::STATUS_COMPLETED => 'Completato',
        ];
    }
}
