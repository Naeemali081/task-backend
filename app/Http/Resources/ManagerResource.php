<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class ManagerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'user_type' => $this->user_type,
            'remember_token' => $this->remember_token,
            'admin' => new UserResource($this->added_By), // Fetch admin details
            'deactivated_at' => $this->deactivated_at?->toDateTimeString(),
            'last_login_at' => $this->last_login_at?->toDateTimeString(),
            'image_url' => $this->image_url,
        ];
    }
}
