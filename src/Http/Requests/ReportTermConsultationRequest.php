<?php

namespace EscolaLms\Consultations\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReportTermConsultationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = auth()->user();
        return isset($user) ? true : false;
    }

    public function rules(): array
    {
        return [
            ''
        ];
    }
}
