<?php

namespace EscolaLms\Consultations\Http\Requests;

use EscolaLms\Consultations\Enum\ConsultationsPermissionsEnum;
use EscolaLms\Consultations\Enum\ConsultationStatusEnum;
use EscolaLms\Consultations\Models\Consultation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreConsultationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = auth()->user();
        return isset($user) ? $user->can(ConsultationsPermissionsEnum::CONSULTATION_CREATE, Consultation::class) : true;
    }

    public function rules(): array
    {
        return [
            'base_price' => 'integer',
            'name' => 'required|string|max:255|min:3',
            'status' => ['required', 'string', Rule::in(ConsultationStatusEnum::getValues())],
            'description' => 'required|string|min:3',
            'author_id' => 'required|integer|exists:users,id',
            'duration' => 'string',
            'started_at' => 'date',
            'finished_at' => 'date|after_or_equal:started_at',
        ];
    }
}
