<?php

namespace EscolaLms\Consultations\Models;

use EscolaLms\Auth\Models\User;
use EscolaLms\Cart\Models\OrderItem;
use EscolaLms\Categories\Models\Category;
use EscolaLms\Consultations\Database\Factories\ConsultationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
 *      @OA\Property(
 *          property="categories",
 *          description="categories",
 *          type="array",
 *          @OA\Items(
 *             @OA\JsonContent(
 *                  @OA\Property(
 *                     property="name",
 *                     type="string",
 *                     example="Dokumentacja",
 *                  ),
 *                  @OA\Property(
 *                     property="icon_class",
 *                     type="string",
 *                     example="fa-business-time",
 *                  ),
 *                  @OA\Property(
 *                    property="is_active",
 *                    type="bool",
 *                    example="true",
 *                  ),
 *                  @OA\Property(
 *                      property="parent_id",
 *                      type="?integer",
 *                      example="null",
 *                 ),
 *              )
 *          )
 *      ),
 *      @OA\Property(
 *          property="proposed_terms",
 *          description="proposed_terms",
 *          type="array"
 *      ),
 * )
 *
 */
class Consultation extends Model
{
    use HasFactory;

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

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function getBuyableDescription(): string
    {
        return '';
    }

    public function getBuyablePrice(?array $options = null): int
    {
        return $this->base_price ?? 0;
    }
}
