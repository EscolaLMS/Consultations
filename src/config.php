<?php

use EscolaLms\Consultations\Enum\ConsultationTermReminderStatusEnum;

return [
    'perPage' => 15,

    'modifier_date' => [
        ConsultationTermReminderStatusEnum::REMINDED_HOUR_BEFORE => '+1 minute',
        ConsultationTermReminderStatusEnum::REMINDED_DAY_BEFORE => '+1 hour',
    ],
    'exclusion_reminder_status' => [
        ConsultationTermReminderStatusEnum::REMINDED_DAY_BEFORE => [
            ConsultationTermReminderStatusEnum::REMINDED_DAY_BEFORE,
            ConsultationTermReminderStatusEnum::REMINDED_HOUR_BEFORE
        ],
        ConsultationTermReminderStatusEnum::REMINDED_HOUR_BEFORE => [
            ConsultationTermReminderStatusEnum::REMINDED_HOUR_BEFORE
        ]
    ]
];
