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
        return Gate::allows(ConsultationsPermissionsEnum::CONSULTATION_LIST, Consultation::class);
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
