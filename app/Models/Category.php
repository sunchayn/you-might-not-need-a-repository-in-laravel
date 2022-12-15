<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $casts = [
        'is_archived' => 'boolean',
    ];

    /*
     * Relationships.
     */

    public function books(): HasMany
    {
        return $this->hasMany(Book::class);
    }

    /*
     * States.
     */

    public function isArchived(): bool
    {
        return $this->is_archived;
    }
}
