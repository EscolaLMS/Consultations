<?php

namespace EscolaLms\Consultations\Dto;

class ConsultationUserResourceDto
{
    public ?int $id;
    public ?string $first_name;
    public ?string $last_name;
    public ?string $email;
    public ?string $phone;
    public ?bool $is_active;
    public ?string $email_verified_at;
    public ?string $path_avatar;
    public ?string $gender;
    public ?string $age;
    public ?string $country;
    public ?string $city;
    public ?string $street;
    public ?string $postcode;
    public ?string $created_at;
    public ?string $updated_at;
    public ?int $points;
    public ?string $notification_channels;
    public ?string $access_to_directories;
    public ?string $current_timezone;
    public ?string $deleted_at;
    public ?string $delete_user_token;
    public ?string $avatar_url;
    public ?array $categories;
    public ?string $executed_status;

    /**
     * @param int|null $id
     * @param string|null $first_name
     * @param string|null $last_name
     * @param string|null $email
     * @param string|null $phone
     * @param bool|null $is_active
     * @param string|null $email_verified_at
     * @param string|null $path_avatar
     * @param string|null $gender
     * @param string|null $age
     * @param string|null $country
     * @param string|null $city
     * @param string|null $street
     * @param string|null $postcode
     * @param string|null $created_at
     * @param string|null $updated_at
     * @param int|null $points
     * @param string|null $notification_channels
     * @param string|null $access_to_directories
     * @param string|null $current_timezone
     * @param string|null $deleted_at
     * @param string|null $delete_user_token
     * @param string|null $avatar_url
     * @param array|null $categories
     * @param string|null $executed_status
     */
    public function __construct(?int $id, ?string $first_name, ?string $last_name, ?string $email, ?string $phone, ?bool $is_active, ?string $email_verified_at, ?string $path_avatar, ?string $gender, ?string $age, ?string $country, ?string $city, ?string $street, ?string $postcode, ?string $created_at, ?string $updated_at, ?int $points, ?string $notification_channels, ?string $access_to_directories, ?string $current_timezone, ?string $deleted_at, ?string $delete_user_token, ?string $avatar_url, ?array $categories, ?string $executed_status)
    {
        $this->id = $id;
        $this->first_name = $first_name;
        $this->last_name = $last_name;
        $this->email = $email;
        $this->phone = $phone;
        $this->is_active = $is_active;
        $this->email_verified_at = $email_verified_at;
        $this->path_avatar = $path_avatar;
        $this->gender = $gender;
        $this->age = $age;
        $this->country = $country;
        $this->city = $city;
        $this->street = $street;
        $this->postcode = $postcode;
        $this->created_at = $created_at;
        $this->updated_at = $updated_at;
        $this->points = $points;
        $this->notification_channels = $notification_channels;
        $this->access_to_directories = $access_to_directories;
        $this->current_timezone = $current_timezone;
        $this->deleted_at = $deleted_at;
        $this->delete_user_token = $delete_user_token;
        $this->avatar_url = $avatar_url;
        $this->categories = $categories;
        $this->executed_status = $executed_status;
    }
}
