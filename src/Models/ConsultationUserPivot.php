<?php

namespace EscolaLms\Consultations\Models;

use EscolaLms\Consultations\Models\Consultation;
use EscolaLms\Core\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class ConsultationUserPivot extends Pivot
{
    protected $table = 'consultation_user';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class);
    }
}
