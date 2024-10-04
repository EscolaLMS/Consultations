<?php

namespace EscolaLms\Consultations\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConsultationScreenSaveRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'consultation_id' => ['required', 'exists:consultations,id'],
            'user_termin_id' => ['required', 'exists:consultation_user,id'],
            'file' => ['required'],
            'user_email' => ['required', 'email', 'exists:users,email'],
            'timestamp' => ['required', 'date_format:Y-m-d H:i:s'],
        ];
    }
}
