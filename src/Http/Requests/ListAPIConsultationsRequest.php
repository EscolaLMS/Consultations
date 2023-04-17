<?php

namespace EscolaLms\Consultations\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class ListAPIConsultationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['string'],
            'status' => ['array'],
            'status.*' => ['string'],
            'categories' => ['sometimes', 'array', 'prohibited_unless:category_id,null'],
            'categories.*' => ['integer', 'exists:categories,id'],
            'category_id' => ['sometimes', 'integer', 'exists:categories,id'],
        ];
    }
}
