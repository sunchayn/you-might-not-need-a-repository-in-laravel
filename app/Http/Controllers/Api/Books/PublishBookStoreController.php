<?php

namespace App\Http\Controllers\Api\Books;

use App\Http\Requests\Api\Books\PublishBookStoreRequest;
use App\Modules\Books\Actions\PublishBookAction;
use App\Modules\Books\DataTransferObjects\PublishBookData;
use Illuminate\Http\JsonResponse;

class PublishBookStoreController
{
    public function __invoke(PublishBookStoreRequest $request, PublishBookAction $publishBookAction): JsonResponse
    {
        $publishBookAction->execute(
            PublishBookData::fromBookPublishRequest($request),
        );

        return response()->json(['message' => 'success!']);
    }
}
