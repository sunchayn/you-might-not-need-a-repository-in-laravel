<?php

namespace App\Http\Requests\Api\Books;

use Illuminate\Foundation\Http\FormRequest;

class PublishBookStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Ideally will authorize the user is allowed to perform the action here.
        // eg. $this->user()->can('publish', $this->route('book');
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'should_be_premium' => 'sometimes|boolean',
        ];
    }
}
