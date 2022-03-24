<?php

namespace EscolaLms\Consultations\Models;

use EscolaLms\Auth\Models\User as AuthUser;
use EscolaLms\Categories\Models\Category;
use EscolaLms\Consultations\Models\Traits\HasConsultations;
use EscolaLms\Consultations\Tests\Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends AuthUser
{
    use HasConsultations;

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_user');
    }

    public static function newFactory()
    {
        return UserFactory::new();
    }
}
