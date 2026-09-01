<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\LayoutSectionBlock;
class UpdateLayoutSectionBlockRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var LayoutSectionBlock $block */
        $block = $this->route('block');

        return match ($block->type) {
            'heading', 'text' => [
                'content.text' => ['required', 'string', 'max:2000'],
            ],
            'image' => [
                'content.url' => ['required', 'string', 'url'],
                'content.alt' => ['nullable', 'string', 'max:255'],
            ],
            'button' => [
                'content.label' => ['required', 'string', 'max:100'],
                'content.href'  => ['required', 'string'],
            ],
            'list-item' => [
                'content.items'   => ['required', 'array', 'min:1'],
                'content.items.*' => ['string', 'max:255'],
            ],
            default => [
                'content' => ['required', 'array'],
            ],
        };
    }
}
