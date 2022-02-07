<?php

namespace EscolaLms\Consultations\Http\Requests;

use EscolaLms\Consultations\Enum\ConsultationsPermissionsEnum;
use EscolaLms\Consultations\Models\Consultation;
use Illuminate\Foundation\Http\FormRequest;

class ListConsultationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = auth()->user();
        return isset($user) ? $user->can(ConsultationsPermissionsEnum::CONSULTATION_LIST, Consultation::class) : true;
    }

    public function rules(): array
    {
        return [
            'name' => 'string',
            'base_price' => 'integer',
            'status' => 'array',
        ];
    }
}
