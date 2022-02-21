<?php

namespace EscolaLms\Consultations\Models;

use EscolaLms\Auth\Models\User;
use EscolaLms\Cart\Models\OrderItem;
use EscolaLms\Consultations\Database\Factories\ConsultationTermFactory;
use EscolaLms\Consultations\Enum\ConsultationTermStatusEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsultationTerm extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'order_item_id',
        'executed_at',
        'executed_status',
    ];

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isApproved(): bool
    {
        return $this->executed_status === ConsultationTermStatusEnum::APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->executed_status === ConsultationTermStatusEnum::REJECT;
    }

    protected static function newFactory(): ConsultationTermFactory
    {
        return ConsultationTermFactory::new();
    }
}
