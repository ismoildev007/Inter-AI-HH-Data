<?php

namespace Modules\Users\Http\Resources\User;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Users\Http\Resources\RoleResource;

class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'         => $this->id,
            'first_name' => $this->first_name,
            'last_name'  => $this->last_name,
            'email'      => $this->email,
            'phone'      => $this->phone,
            'birth_date' => $this->birth_date,
            'avatar'     => $this->avatar_path,
            'role'       => new RoleResource($this->whenLoaded('role')),
            'settings'   => new UserSettingResource($this->whenLoaded('settings')),
            'credit'     => new UserCreditResource($this->whenLoaded('credit')),
            'preferences'=> UserPreferenceResource::collection($this->whenLoaded('preferences')),
            'locations'  => UserLocationResource::collection($this->whenLoaded('locations')),
            'job_types'  => UserJobTypeResource::collection($this->whenLoaded('jobTypes')),
            'profile_views' => UserProfileViewResource::collection($this->whenLoaded('profileViews')),
            'created_at' => $this->created_at,
        ];
    }
}
