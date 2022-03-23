<?php

namespace EscolaLms\Consultations\Models;

use EscolaLms\Consultations\Database\Factories\ConsultationUserFactory;
use EscolaLms\Consultations\Enum\ConsultationTermStatusEnum;
use EscolaLms\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsultationUserPivot extends Model
{
    use HasFactory;

    protected $table = 'consultation_user';

    protected $fillable = [
        'user_id',
        'executed_at',
        'executed_status',
        'consultation_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class);
    }

    public function isApproved(): bool
    {
        return $this->executed_status === ConsultationTermStatusEnum::APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->executed_status === ConsultationTermStatusEnum::REJECT;
    }

    protected static function newFactory(): ConsultationUserFactory
    {
        return ConsultationUserFactory::new();
    }
}
