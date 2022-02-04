<?php

namespace EscolaLms\Consultations\Http\Requests;

use EscolaLms\Consultations\Enum\ConsultationsPermissionsEnum;
use EscolaLms\Consultations\Models\Consultation;
use Illuminate\Foundation\Http\FormRequest;

class EditConsultationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = auth()->user();
        return isset($user) ? $user->can(ConsultationsPermissionsEnum::CONSULTATION_CREATE, Consultation::class) : true;
    }

    public function rules(): array
    {
        return [];
    }
}
