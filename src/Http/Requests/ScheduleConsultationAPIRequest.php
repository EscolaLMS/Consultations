<?php

namespace EscolaLms\Consultations\Http\Requests;

use EscolaLms\Consultations\Enum\ConsultationsPermissionsEnum;
use EscolaLms\Consultations\Enum\ConsultationTermStatusEnum;
use EscolaLms\Consultations\Models\Consultation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ScheduleConsultationAPIRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows(ConsultationsPermissionsEnum::CONSULTATION_READ, Consultation::class);
    }

    public function rules(): array
    {
        return [
            'date_from' => ['date'],
            'date_to' => ['date', 'after_or_equal:date_from'],
            'status' => ['string', Rule::in(ConsultationTermStatusEnum::getValues())],
        ];
    }
}
