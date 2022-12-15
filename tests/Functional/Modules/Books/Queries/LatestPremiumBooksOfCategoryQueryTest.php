<?php

namespace Tests\Functional\Modules\Books\Queries;

use App\Models\Book;
use App\Models\Category;
use App\Modules\Books\Queries\LatestPremiumBooksOfCategoryQuery;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class LatestPremiumBooksOfCategoryQueryTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    private const LIMIT = 5;

    /** @test */
    public function it_works_for_non_archived_categories(): void
    {
        // Arrange

        $categories = Category::factory()->count(4)->create()->shuffle();

        $targetCategory = $categories->pop();

        $expectedBooks = $this->seedBooks($targetCategory);

        $categories
            ->each(function (Category $category) {
                // Seed books that should not be included.
                $this->seedBooks($category);
            });

        // Act

        $result = (new LatestPremiumBooksOfCategoryQuery($targetCategory, self::LIMIT))->get();

        // Assert

        $this->assertCount(self::LIMIT, $result);

        $this->assertEquals(
            $expectedBooks->toArray(),
            $result->toArray(),
        );
    }

    /** @test */
    public function it_works_for_archived_categories(): void
    {
        $this->markTestIncomplete();
    }

    private function seedBooks(Category $category): Collection
    {
        $randomBuffer = $this->faker->numberBetween(1, 4);

        $time = now()->subMonth();

        $eligibleBooks = Book::factory()
            ->for($category)
            ->premium()
            ->sequence(
                // Manually set the creation date in a sequence manner, so we can expect the order.
                fn (Sequence $sequence) => ['created_at' => $time->addMinute()],
            )
            ->count(self::LIMIT + $randomBuffer)
            ->create();

        // Ineligible books
        Book::factory()
            ->for($category)
            ->sequence(
                ['published_at' => null],
                ['is_premium' => false],
            )
            ->count($this->faker->numberBetween(4, 8))
            ->create();

        return $eligibleBooks->reverse()->take(self::LIMIT)->values();
    }
}
