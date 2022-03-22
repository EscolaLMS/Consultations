<?php

namespace EscolaLms\Consultations\Models;

use EscolaLms\Auth\Models\User;
use EscolaLms\Cart\Models\OrderItem;
use EscolaLms\Categories\Models\Category;
use EscolaLms\Consultations\Database\Factories\ConsultationFactory;
use EscolaLms\Consultations\Models\Traits\HasConsultations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Storage;


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
 *          type="string",
 *      ),
 *      @OA\Property(
 *          property="status",
 *          description="status",
 *          type="string",
 *      ),
 *      @OA\Property(
 *          property="description",
 *          description="description",
 *          type="string",
 *      ),
 *      @OA\Property(
 *          property="duration",
 *          description="duration",
 *          type="string",
 *      ),
 *      @OA\Property(
 *          property="author_id",
 *          description="author_id",
 *          type="integer",
 *      ),
 *      @OA\Property(
 *          property="base_price",
 *          description="base_price",
 *          type="integer",
 *      ),
 *      @OA\Property(
 *          property="active_to",
 *          description="active_to",
 *          type="datetime",
 *      ),
 *      @OA\Property(
 *          property="active_from",
 *          description="active_from",
 *          type="datetime",
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
 *             @OA\Property(
 *                 property="name",
 *                 type="string",
 *                 example="Dokumentacja",
 *             ),
 *             @OA\Property(
 *                 property="icon_class",
 *                 type="string",
 *                 example="fa-business-time",
 *             ),
 *             @OA\Property(
 *                 property="is_active",
 *                 type="bool",
 *                 example="true",
 *             ),
 *             @OA\Property(
 *                 property="parent_id",
 *                 type="?integer",
 *                 example="null",
 *             ),
 *          ),
 *      ),
 *      @OA\Property(
 *          property="proposed_terms",
 *          description="proposed_terms",
 *          type="array",
 *          @OA\Items(
 *             @OA\Property(
 *                  property="",
 *                  type="string",
 *                  example="12-12-2022 11:30",
 *             ),
 *          ),
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
        'short_desc',
        'image_path',
        'author_id',
        'active_from',
        'active_to',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function orderItems(): MorphMany
    {
        return $this->morphMany(OrderItem::class, 'buyable');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'consultation_user');
    }

    public function proposedTerms(): HasMany
    {
        return $this->hasMany(ConsultationProposedTerm::class, 'consultation_id');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function terms(): HasMany
    {
        return $this->hasMany(ConsultationTerm::class);
    }

    public function getBuyableDescription(): string
    {
        return '';
    }

    public function getBuyablePrice(?array $options = null): int
    {
        return $this->base_price ?? 0;
    }

    public function getImageUrlAttribute(): string
    {
        if ($this->attributes['image_path'] ?? null) {
            return url(Storage::disk('public')->url($this->attributes['image_path']));
        }
        return '';
    }

    protected static function newFactory(): ConsultationFactory
    {
        return ConsultationFactory::new();
    }
}
