<?php

namespace EscolaLms\Consultations\Enum;

use EscolaLms\Core\Enums\BasicEnum;

class ConsultationsPermissionsEnum extends BasicEnum
{
    const CONSULTATION_LIST = 'consultation_list';
    const CONSULTATION_CREATE = 'consultation_create';
    const CONSULTATION_UPDATE = 'consultation_update';
    const CONSULTATION_DELETE = 'consultation_delete';
    const CONSULTATION_ATTEND = 'consultation_read';

    const CONSULTATION_UPDATE_OWNED = 'consultation_update_authored';
    const CONSULTATION_DELETE_OWNED = 'consultation_delete_authored';
    const CONSULTATION_ATTEND_OWNED = 'consultation_read_authored';
}
