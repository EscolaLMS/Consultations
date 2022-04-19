<?php

namespace EscolaLms\Consultations\Http\Requests;

use EscolaLms\Consultations\Enum\ConsultationsPermissionsEnum;
use EscolaLms\Consultations\Models\Consultation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

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
        ];
    }
}
