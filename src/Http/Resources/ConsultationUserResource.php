<?php

namespace EscolaLms\Consultations\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ConsultationUserResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->resource->id,
            'first_name' => $this->resource->first_name,
            'last_name' => $this->resource->last_name,
            'email' => $this->resource->email,
            'phone' => $this->resource->phone,
            'is_active' => $this->resource->is_active,
            'email_verified_at' => $this->resource->email_verified_at,
            'path_avatar' => $this->resource->path_avatar,
            'gender' => $this->resource->gender,
            'age' => $this->resource->age,
            'country' => $this->resource->country,
            'city' => $this->resource->city,
            'street' => $this->resource->street,
            'postcode' => $this->resource->postcode,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
            'points' => $this->resource->points,
            'notification_channels' => $this->resource->notification_channels,
            'access_to_directories' => $this->resource->access_to_directories,
            'current_timezone' => $this->resource->current_timezone,
            'deleted_at' => $this->resource->deleted_at,
            'delete_user_token' => $this->resource->delete_user_token,
            'avatar_url' => $this->resource->avatar_url,
            'categories' => $this->resource->categories,
            'executed_status' => $this->resource->executed_status,
        ];
    }
}
