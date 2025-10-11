<?php

namespace Modules\Users\Repositories;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use App\Notifications\VerifyEmailCodeNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AuthRepository
{
    public function register(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $user = null;
            if (!empty($data['chat_id'])) {
                $user = User::where('chat_id', $data['chat_id'])->first();
            }

            if (!$user) {
                if (!empty($data['phone']) && User::where('phone', $data['phone'])->exists()) {
                    return [
                        'status'  => 'error',
                        'message' => 'Phone already exists',
                        'code'    => 422,
                    ];
                }
                
                $user = User::updateOrCreate(
                    ['chat_id' => $data['chat_id']],
                    [
                        'first_name' => $data['first_name'] ?? null,
                        'last_name'  => $data['last_name'] ?? null,
                        'phone'      => $data['phone'] ?? null,
                        'password'   => isset($data['password']) ? Hash::make($data['password']) : null,
                    ]
                );

                $user->credit()->create([
                    'balance' => 50,
                ]);
                Log::info("new balance". $user->credit->balance);
            } else {
                $user->update([
                    'first_name' => $data['first_name'] ?? $user->first_name,
                    'last_name'  => $data['last_name'] ?? $user->last_name,
                    'phone'      => $data['phone'] ?? $user->phone,
                    'password'   => isset($data['password']) ? Hash::make($data['password']) : $user->password,
                ]);

                if (!$user->credit) {
                    $user->credit()->create([
                        'balance' => 50,
                    ]);
                    Log::info("existing user new balance". $user->credit->balance);
                }
            }

            $token = $user->createToken(
                'api_token',
                ['*'],
                now()->addHours(4)
            )->plainTextToken;

            return [
                'status' => 'success',
                'data'   => [
                    'user'       => $user->load(['credit', 'resumes']),
                    'token'      => $token,
                    'expires_at' => now()->addHours(4)->toDateTimeString(),
                ],
            ];
        });
    }

    public function update(User $user, array $data): array
    {
        return DB::transaction(function () use ($user, $data) {
            if (!empty($data['phone']) && User::where('phone', $data['phone'])->where('id', '!=', $user->id)->exists()) {
                return [
                    'status'  => 'error',
                    'message' => 'Phone already exists',
                    'code'    => 422,
                ];
            }

            $user->update([
                'first_name'  => $data['first_name'] ?? $user->first_name,
                'last_name'   => $data['last_name'] ?? $user->last_name,
                'phone'       => $data['phone'] ?? $user->phone,
                'password'    => !empty($data['password']) ? Hash::make($data['password']) : $user->password,
            ]);

            

//            if (!empty($data['experience']) || !empty($data['salary_from']) || !empty($data['salary_to'])) {
//                $pref = $user->preferences()->first();
//
//                $user->preferences()->updateOrCreate(
//                    ['user_id' => $user->id],
//                    [
//                        'experience_level'    => $data['experience'] ?? $pref?->experience_level,
//                        'desired_salary_from' => $data['salary_from'] ?? $pref?->desired_salary_from,
//                        'desired_salary_to'   => $data['salary_to'] ?? $pref?->desired_salary_to,
//                        'currency'            => 'USD',
//                        'work_mode'           => $data['employment_type'] ?? $pref?->work_mode,
//                    ]
//                );
//            }
//
//            if (!empty($data['location'])) {
//                $user->locations()->updateOrCreate(['is_primary' => true], [
//                    'text'       => $data['location'],
//                    'is_primary' => true,
//                ]);
//            }
//
//            if (!empty($data['employment_type'])) {
//                $user->jobTypes()->delete();
//                $user->jobTypes()->create([
//                    'job_type' => $data['employment_type'],
//                ]);
//            }

            return [
                'status' => 'success',
                'data'   => $user->load([
//                    'preferences',
//                    'locations',
//                    'jobTypes',
                    'resumes',
                ])->toArray(),
            ];
        });
    }
    public function login(array $credentials): ?array
    {
        if (!Auth::attempt($credentials)) {
            return null;
        }

        $user = Auth::user();

        $token = $user->createToken(
            'api_token',
            ['*'],
            now()->addHours(4)
        )->plainTextToken;

        return [
            'status' => 'success',
            'data'   => [
                'user'       => $user,
                'token'      => $token,
                'expires_at' => now()->addHours(4)->toDateTimeString(),
            ]
        ];
    }

    public function requestVerificationCode(string $email): array
    {
        if (User::where('email', $email)->exists()) {
            return [
                'status'  => 'error',
                'message' => 'Email already exists',
                'code'    => 422,
            ];
        }

        $code = rand(100000, 999999);

        // Cache ga saqlash (10 daqiqa amal qiladi)
        cache()->put("verify_code:$email", $code, now()->addMinutes(10));

        // Emailga yuborish
        Notification::route('mail', $email)
        ->notify(new VerifyEmailCodeNotification($code));

        return [
            'status'  => 'success',
            'message' => 'Verification code sent to your email',
        ];
    }
}
