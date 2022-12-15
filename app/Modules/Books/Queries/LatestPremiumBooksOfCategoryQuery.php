<?php

namespace App\Modules\Books\Queries;

use App\Models\Category;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class LatestPremiumBooksOfCategoryQuery
{
    public function __construct(
        private readonly Category $category,
        private ?int $limit = null,
    ) {
        $this->limit ??= config('categories.limit');
    }

    public function get(): Collection
    {
        return $this
            ->category
            ->books()
            ->wherePublished()
            ->wherePremium()
            ->when($this->category->isArchived(), fn (Builder $builder) => $builder->whereNull('books.user_id'))
            ->latest()
            ->take($this->limit)
            ->get();
    }
}
