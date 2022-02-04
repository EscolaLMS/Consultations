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

        Permission::findOrCreate(ConsultationsPermissionsEnum::CONSULTATION_LIST, 'api');
        Permission::findOrCreate(ConsultationsPermissionsEnum::CONSULTATION_UPDATE, 'api');
        Permission::findOrCreate(ConsultationsPermissionsEnum::CONSULTATION_DELETE, 'api');
        Permission::findOrCreate(ConsultationsPermissionsEnum::CONSULTATION_CREATE, 'api');
        Permission::findOrCreate(ConsultationsPermissionsEnum::CONSULTATION_ATTEND, 'api');

        Permission::findOrCreate(ConsultationsPermissionsEnum::CONSULTATION_UPDATE_OWNED, 'api');
        Permission::findOrCreate(ConsultationsPermissionsEnum::CONSULTATION_DELETE_OWNED, 'api');
        Permission::findOrCreate(ConsultationsPermissionsEnum::CONSULTATION_ATTEND_OWNED, 'api');

        $admin->givePermissionTo([
            ConsultationsPermissionsEnum::CONSULTATION_LIST,
            ConsultationsPermissionsEnum::CONSULTATION_UPDATE,
            ConsultationsPermissionsEnum::CONSULTATION_DELETE,
            ConsultationsPermissionsEnum::CONSULTATION_CREATE,
            ConsultationsPermissionsEnum::CONSULTATION_ATTEND,
            ConsultationsPermissionsEnum::CONSULTATION_UPDATE_OWNED,
            ConsultationsPermissionsEnum::CONSULTATION_DELETE_OWNED,
            ConsultationsPermissionsEnum::CONSULTATION_ATTEND_OWNED,
        ]);
        $tutor->givePermissionTo([
            ConsultationsPermissionsEnum::CONSULTATION_LIST,
            ConsultationsPermissionsEnum::CONSULTATION_CREATE,
            ConsultationsPermissionsEnum::CONSULTATION_UPDATE_OWNED,
            ConsultationsPermissionsEnum::CONSULTATION_DELETE_OWNED,
            ConsultationsPermissionsEnum::CONSULTATION_ATTEND_OWNED,
        ]);
    }
}
