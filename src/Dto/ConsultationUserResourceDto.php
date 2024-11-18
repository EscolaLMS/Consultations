<?php

namespace EscolaLms\Consultations\Dto;

class ConsultationUserResourceDto extends BaseDto
{
    public ?int $id;
    public ?string $firstName;
    public ?string $lastName;
    public ?string $email;
    public ?string $phone;
    public ?bool $isActive;
    public ?string $emailVerifiedAt;
    public ?string $pathAvatar;
    public ?string $gender;
    public ?string $age;
    public ?string $country;
    public ?string $city;
    public ?string $street;
    public ?string $postcode;
    public ?string $createdAt;
    public ?string $updatedAt;
    public ?int $points;
    public ?string $notificationChannels;
    public ?string $accessToDirectories;
    public ?string $currentTimezone;
    public ?string $deletedAt;
    public ?string $deleteUserToken;
    public ?string $avatarUrl;
    public ?array $categories;
    public ?string $executedStatus;
}
