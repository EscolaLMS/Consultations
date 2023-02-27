<?php

namespace EscolaLms\Consultations\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReportTermConsultationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = auth()->user();
        return isset($user);
    }

    public function rules(): array
    {
        return [
            'proposed_dates' => ['array'],
            'proposed_dates.*' => ['required', 'date', 'after_or_equal:now']
        ];
    }
}
