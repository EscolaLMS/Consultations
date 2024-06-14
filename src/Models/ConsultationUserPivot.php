<?php

namespace EscolaLms\Consultations\Models;

use EscolaLms\Consultations\Database\Factories\ConsultationUserFactory;
use EscolaLms\Consultations\Enum\ConsultationTermStatusEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property ConsultationTermStatusEnum $executed_status
 * @property int $user_id
 * @property int $consultation_id
 * @property Consultation $consultation
 * @property Carbon $executed_at
 */
class ConsultationUserPivot extends Model
{
    use HasFactory;

    protected $table = 'consultation_user';

    protected $fillable = [
        'user_id',
        'executed_at',
        'executed_status',
        'consultation_id',
        'product_id',
        'reminder_status',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id', 'users');
    }

    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class);
    }

    public function isApproved(): bool
    {
        return $this->executed_status->is(ConsultationTermStatusEnum::APPROVED);
    }

    public function isRejected(): bool
    {
        return $this->executed_status->is(ConsultationTermStatusEnum::REJECT);
    }

    protected static function newFactory(): ConsultationUserFactory
    {
        return ConsultationUserFactory::new();
    }
}
