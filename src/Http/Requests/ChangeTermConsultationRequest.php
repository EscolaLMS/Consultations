<?php

namespace EscolaLms\Consultations\Http\Requests;

use EscolaLms\Consultations\Enum\ConsultationsPermissionsEnum;
use EscolaLms\Consultations\Models\ConsultationUserPivot;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class ChangeTermConsultationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows(ConsultationsPermissionsEnum::CONSULTATION_CHANGE_TERM, ConsultationUserPivot::class);
    }

    public function rules(): array
    {
        return [
            'executed_at' => ['date', 'after_or_equal:now'],
        ];
    }
}
