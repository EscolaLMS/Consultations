<?php

namespace EscolaLms\Consultations\Models;

use EscolaLms\Auth\Models\User as AuthUser;
use EscolaLms\Consultations\Models\Traits\HasConsultations;
use EscolaLms\Consultations\Tests\Database\Factories\UserFactory;

class User extends AuthUser
{
    use HasConsultations;

    public static function newFactory()
    {
        return UserFactory::new();
    }
}
