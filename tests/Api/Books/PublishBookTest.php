<?php

namespace Tests\Api\Books;

use App\Models\Book;
use App\Models\User;
use App\Modules\Books\Events\BookPublishedEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class PublishBookTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @dataProvider publishBookDataProvider
     * @test
     */
    public function it_publishes_books(array $requestParams, bool $expectedToBePremium): void
    {
        // Arrange

        Event::fake();

        $book = Book::factory()->free()->unpublished()->create();

        $user = User::factory()->create();

        Carbon::setTestNow(today());

        // Act

        $response = $this
            ->actingAs($user)
            ->postJson(route('books.publish', ['book_id' => $book->id, ...$requestParams]));

        // Assert

        $response->assertOk();

        $book->refresh();

        $this->assertEquals($expectedToBePremium, $book->isPremium());

        $this->assertEquals($user->id, $book->published_by);

        $this->assertEquals(today(), $book->published_at);

        Event::assertDispatched(
            BookPublishedEvent::class,
            fn (BookPublishedEvent $event) => $event->book->is($book),
        );
    }

    public function publishBookDataProvider(): array
    {
        return [
            'it publish book as free book [explicit]' => [
                'params' => [
                    'should_be_premium' => false,
                ],
                'expectedToBePremium' => false,
            ],
            'it publish book as free book [implicit]' => [
                'params' => [],
                'expectedToBePremium' => false,
            ],
            'it publish book as premium book' => [
                'params' => [
                    'should_be_premium' => true,
                ],
                'expectedToBePremium' => true,
            ],
        ];
    }

    /** @test */
    public function it_requires_authentication(): void
    {
        // Arrange

        $book = Book::factory()->free()->unpublished()->create();

        // Act

        $response = $this->postJson(route('books.publish', ['book_id' => $book->id]));

        // Assert

        $response->assertUnauthorized();
    }

    /** @test */
    public function it_authorizes_users(): void
    {
        $this->markTestIncomplete('Ideally we have admins that can publish books. Only they should be authorized to do it.');
    }

    /** @test */
    public function it_only_publishes_existent_books(): void
    {
        // Arrange

        $user = User::factory()->create();

        // Act

        $response = $this
            ->actingAs($user)
            ->postJson(route('books.publish', ['book_id' => PHP_INT_MAX]));

        // Assert

        $response->assertNotFound();
    }
}
