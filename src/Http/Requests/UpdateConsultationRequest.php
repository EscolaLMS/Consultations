<?php

namespace EscolaLms\Consultations\Http\Requests;

use EscolaLms\Consultations\Enum\ConstantEnum;
use EscolaLms\Consultations\Enum\ConsultationsPermissionsEnum;
use EscolaLms\Consultations\Enum\ConsultationStatusEnum;
use EscolaLms\Consultations\Models\Consultation;
use EscolaLms\Files\Rules\FileOrStringRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateConsultationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows(ConsultationsPermissionsEnum::CONSULTATION_UPDATE, Consultation::class);
    }

    public function rules(): array
    {
        $prefixPath = ConstantEnum::DIRECTORY . '/' . $this->route('id');

        return [
            'name' => ['string', 'max:255', 'min:3'],
            'status' => ['string', Rule::in(ConsultationStatusEnum::getValues())],
            'description' => ['string', 'min:3'],
            'duration' => ['nullable', 'string', 'max:80'],
            'image' => [new FileOrStringRule(['image'], $prefixPath)],
            'author_id' => ['integer', 'exists:users,id'],
            'active_from' => ['date'],
            'active_to' => ['date', 'after_or_equal:active_from'],
            'proposed_dates' => ['array'],
            'proposed_dates.*' => ['date', 'after_or_equal:active_from'],
            'categories' => ['array'],
            'categories.*' => ['integer', 'exists:categories,id'],
        ];
    }
}
