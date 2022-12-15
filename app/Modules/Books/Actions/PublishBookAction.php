<?php

namespace App\Modules\Books\Actions;

use App\Modules\Books\DataTransferObjects\PublishBookData;
use App\Modules\Books\Events\BookPublishedEvent;

class PublishBookAction
{
    public function execute(PublishBookData $data): void
    {
        $data->book->markAsPublishedBy($data->user);

        if ($data->shouldBePremium) {
            $data->book->markAsPremium();
        }

        $data->book->save();

        event(new BookPublishedEvent($data->book));
    }
}
