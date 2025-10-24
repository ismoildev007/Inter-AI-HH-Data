<?php

namespace Modules\Users\Http\Resources\User;

use App\Models\HhAccount;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Resumes\Http\Resources\ResumeResource;
use Modules\Users\Http\Resources\RoleResource;

class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        $hhAccount = HhAccount::where('user_id', $this->id)->first();

        $activeSubscription = $this->whenLoaded('subscriptions', function () {
            return $this->subscriptions
                ->where('status', 'active')
                ->sortByDesc('starts_at')
                ->first();
        });

        return [
            'id'         => $this->id,
            'first_name' => $this->first_name,
            'last_name'  => $this->last_name,
            'phone'      => $this->phone,
            'is_trial_active' => (bool) $this->is_trial_active,
            'status' => $this->status,
            'resumes' => ResumeResource::collection($this->whenLoaded('resumes')),
            'settings'   => new UserSettingResource($this->whenLoaded('settings')),
            'credit'     => new UserCreditResource($this->whenLoaded('credit')),
            'subscription' => $this->when($activeSubscription, fn () => new UserSubscriptionResource($activeSubscription)),
            'created_at' => $this->created_at,
            'hh_account_status' => $hhAccount ? true : false,
        ];
    }
}

