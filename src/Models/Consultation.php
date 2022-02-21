<?php

namespace EscolaLms\Consultations\Models;

use EscolaLms\Auth\Models\User;
use EscolaLms\Cart\Contracts\Base\BuyableTrait;
use EscolaLms\Cart\Models\OrderItem;
use EscolaLms\Consultations\Database\Factories\ConsultationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;


/**
 * @OA\Schema(
 *      schema="Consultation",
 *      required={"name", "status", "description", "author_id"},
 *      @OA\Property(
 *          property="id",
 *          description="id",
 *          type="integer",
 *      ),
 *      @OA\Property(
 *          property="name",
 *          description="name",
 *          type="string"
 *      ),
 *      @OA\Property(
 *          property="status",
 *          description="status",
 *          type="string"
 *      ),
 *      @OA\Property(
 *          property="description",
 *          description="description",
 *          type="string"
 *      ),
 *      @OA\Property(
 *          property="duration",
 *          description="duration",
 *          type="string"
 *      ),
 *      @OA\Property(
 *          property="author_id",
 *          description="author_id",
 *          type="integer"
 *      ),
 *      @OA\Property(
 *          property="base_price",
 *          description="base_price",
 *          type="integer"
 *      ),
 *      @OA\Property(
 *          property="active_to",
 *          description="active_to",
 *          type="datetime",
 *      ),
 *      @OA\Property(
 *          property="active_from",
 *          description="active_from",
 *          type="datetime"
 *      ),
 *      @OA\Property(
 *          property="created_at",
 *          description="created_at",
 *          type="datetime",
 *      ),
 *      @OA\Property(
 *          property="updated_at",
 *          description="updated_at",
 *          type="datetime",
 *      ),
 * )
 *
 */
class Consultation extends Model
{
    use HasFactory;
    use BuyableTrait;

    protected $fillable = [
        'base_price',
        'name',
        'status',
        'duration',
        'description',
        'author_id',
        'active_from',
        'active_to'
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function orderItems(): MorphMany
    {
        return $this->morphMany(OrderItem::class, 'buyable');
    }

    public function proposedTerms(): HasMany
    {
        return $this->hasMany(ConsultationProposedTerm::class, 'consultation_id');
    }

    protected static function newFactory(): ConsultationFactory
    {
        return ConsultationFactory::new();
    }

    public function getBuyableDescription(): string
    {
        // TODO: Implement getBuyableDescription() method.
    }

    public function getBuyablePrice(?array $options = null): int
    {
        return $this->base_price ?? 0;
    }
}
