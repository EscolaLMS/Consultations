<?php

namespace EscolaLms\Consultations\Enum;

use EscolaLms\Core\Enums\BasicEnum;

class ConsultationsPermissionsEnum extends BasicEnum
{
    const CONSULTATION_LIST = 'consultation_list';
    const CONSULTATION_CREATE = 'consultation_create';
    const CONSULTATION_UPDATE = 'consultation_update';
    const CONSULTATION_DELETE = 'consultation_delete';
    const CONSULTATION_READ = 'consultation_read';
    const CONSULTATION_CHANGE_TERM = 'consultation_change_term';
    const CONSULTATION_REJECT_TERM = 'consultation_reject-term';
    const CONSULTATION_APPROVE_TERM = 'consultation_approve-term';
}
