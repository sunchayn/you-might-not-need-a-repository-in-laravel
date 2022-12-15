<?php

namespace App\Modules\Books\DataTransferObjects;

use App\Http\Requests\Api\Books\PublishBookStoreRequest;
use App\Models\Book;
use App\Models\User;

final class PublishBookData
{
    public function __construct(
        public readonly Book $book,
        public readonly User $user,
        public readonly bool $shouldBePremium,
    ) {
    }

    public static function fromBookPublishRequest(PublishBookStoreRequest $request): self
    {
        $data = $request->validated();

        $book = Book::findOrFail($request->route('book_id'));

        return new self(
            book: $book,
            user: $request->user(),
            shouldBePremium: $data['should_be_premium'] ?? false,
        );
    }
}
