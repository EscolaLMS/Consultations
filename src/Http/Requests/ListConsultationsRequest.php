<?php

namespace EscolaLms\Consultations\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListConsultationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = auth()->user();
        return isset($user);
    }

    public function rules(): array
    {
        return [
            'name' => ['string'],
            'status' => ['array'],
            'status.*' => ['string'],
            'order_by' => ['sometimes', 'string', 'in:id,name,status,duration,active_from,active_to'],
        ];
    }
}
