<?php

namespace EscolaLms\Consultations\Models;

use EscolaLms\Auth\Models\User;
use EscolaLms\Consultations\Database\Factories\ConsultationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Treestoneit\ShoppingCart\Buyable;
use Treestoneit\ShoppingCart\BuyableTrait;


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
 *          property="duration",
 *          description="duration",
 *          type="string"
 *      ),
 *     @OA\Property(
 *          property="description",
 *          description="description",
 *          type="string",
 *      ),
 *      @OA\Property(
 *          property="finished_at",
 *          description="finished_at",
 *          type="datetime",
 *      ),
 *      @OA\Property(
 *          property="started_at",
 *          description="started_at",
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
class Consultation extends Model implements Buyable
{
    use BuyableTrait;
    use HasFactory;

    protected $fillable = [
        'base_price',
        'name',
        'status',
        'description',
        'author_id',
        'duration',
        'started_at',
        'finished_at'
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(ConsultationParticipant::class);
    }

    protected static function newFactory(): ConsultationFactory
    {
        return ConsultationFactory::new();
    }
}
