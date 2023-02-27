<?php

namespace EscolaLms\Consultations\Models;

use EscolaLms\Consultations\Database\Factories\ConsultationUserProposedTermFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 *
 * ConsultationUserProposedTerm
 *
 * @property int $id
 * @property int $consultation_user_id
 * @property Carbon $proposed_at
 *
 * @property-read ConsultationUserPivot $consultationUser
**/
class ConsultationUserProposedTerm extends Model
{
    use HasFactory;

    protected $fillable = [
        'consultation_user_id',
        'proposed_at',
    ];

    protected $casts = [
        'proposed_at' => 'datetime',
    ];

    public function consultationUser(): BelongsTo
    {
        return $this->belongsTo(ConsultationUserPivot::class);
    }

    protected static function newFactory(): ConsultationUserProposedTermFactory
    {
        return ConsultationUserProposedTermFactory::new();
    }
}
