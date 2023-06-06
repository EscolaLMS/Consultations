<?php

namespace EscolaLms\Consultations\Http\Requests;

use App\Models\Consultation;
use EscolaLms\Consultations\Enum\ConsultationsPermissionsEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class ConsultationAssignableUserListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows(ConsultationsPermissionsEnum::CONSULTATION_CREATE, Consultation::class);
    }

    public function rules(): array
    {
        return [
            'search' => ['string'],
        ];
    }
}
