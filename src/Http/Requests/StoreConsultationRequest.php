<?php

namespace EscolaLms\Consultations\Http\Requests;

use EscolaLms\Consultations\Enum\ConsultationStatusEnum;
use EscolaLms\Consultations\Models\Consultation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreConsultationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', Consultation::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'min:3'],
            'status' => ['required', 'string', Rule::in(ConsultationStatusEnum::getValues())],
            'description' => ['required', 'string', 'min:3'],
            'duration' => ['nullable', 'string', 'max:80'],
            'image' => ['nullable', 'file', 'image'],
            'author_id' => ['required', 'integer', 'exists:users,id'],
            'active_from' => ['date'],
            'active_to' => ['date', 'after_or_equal:active_from'],
            'proposed_dates' => ['array'],
            'proposed_dates.*' => ['date', 'after_or_equal:active_from'],
            'categories' => ['array'],
            'categories.*' => ['integer', 'exists:categories,id'],
        ];
    }
}
