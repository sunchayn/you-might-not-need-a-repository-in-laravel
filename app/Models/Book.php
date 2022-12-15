<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Book extends Model
{
    use HasFactory;

    protected $casts = [
        'published_at' => 'datetime',
        'content' => 'json',
        'is_premium' => 'boolean',
    ];

    /*
     * Scopes.
     */

    public function scopeWhereFree(Builder $query): void
    {
        $query->where('is_premium', false);
    }

    public function scopeWherePremium(Builder $query): void
    {
        $query->where('is_premium', true);
    }

    public function scopeWherePublished(Builder $query): void
    {
        $query->whereNotNull('published_at');
    }

    /*
     * Relationships.
     */

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function publishedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    /*
     * State Modifiers.
     */

    public function markAsPublishedBy(User $user)
    {
        $this->publishedBy()->associate($user);
        $this->published_at = now();
    }

    public function markAsPremium(): void
    {
        $this->setAttribute('is_premium', true);
    }

    /*
     * Asserts.
     */

    public function isPremium(): bool
    {
        return $this->is_premium;
    }
}
