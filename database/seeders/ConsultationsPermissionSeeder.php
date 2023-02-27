<?php

namespace EscolaLms\Consultations\Database\Seeders;

use EscolaLms\Consultations\Enum\ConsultationsPermissionsEnum;
use EscolaLms\Core\Enums\UserRole;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

use Illuminate\Database\Seeder;

class ConsultationsPermissionSeeder extends Seeder
{
    public function run()
    {
        // create permissions
        $admin = Role::findOrCreate(UserRole::ADMIN, 'api');
        $tutor = Role::findOrCreate(UserRole::TUTOR, 'api');

        foreach (ConsultationsPermissionsEnum::getValues() as $permission) {
            Permission::findOrCreate($permission, 'api');
        }

        $admin->givePermissionTo(ConsultationsPermissionsEnum::getValues());
        $tutor->givePermissionTo(ConsultationsPermissionsEnum::getValues());
    }
}
