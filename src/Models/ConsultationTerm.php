<?php

namespace EscolaLms\Consultations\Models;

use EscolaLms\Cart\Models\OrderItem;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsultationTerm extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'order_item_id',
        'executed_at',
        'executed_status',
    ];

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }
}
