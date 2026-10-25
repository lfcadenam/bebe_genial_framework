<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->role,
            'status' => $this->status,
            'must_change_password' => (bool)$this->must_change_password,
            'baby_profile' => $this->whenLoaded('babyProfile', function () {
                return $this->babyProfile ? [
                    'id' => $this->babyProfile->id,
                    'full_name' => $this->babyProfile->full_name,
                    'birth_date' => $this->babyProfile->birth_date?->format('Y-m-d'),
                    'avatar' => $this->babyProfile->avatar,
                    'school_grade' => $this->babyProfile->school_grade,
                    'school_name' => $this->babyProfile->school_name,
                ] : null;
            }),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
