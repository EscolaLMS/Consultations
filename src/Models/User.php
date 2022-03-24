<?php

namespace EscolaLms\Consultations\Models;

use EscolaLms\Core\Models\User as CoreUser;
use EscolaLms\Categories\Models\Category;
use EscolaLms\Consultations\Models\Traits\HasConsultations;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends CoreUser
{
    use HasConsultations;

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_user');
    }
}
