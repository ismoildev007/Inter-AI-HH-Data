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
            //            if (User::where('email', $data['email'])->exists()) {
            //                return [
            //                    'status'  => 'error',
            //                    'message' => 'Email already exists',
            //                    'code'    => 422,
            //                ];
            //            }

            if (!empty($data['phone']) && User::where('phone', $data['phone'])->exists()) {
                return [
                    'status'  => 'error',
                    'message' => 'Phone already exists',
                    'code'    => 422,
                ];
            }

            //            $role = Role::where('name', 'job_seeker')->first();

            $user = User::create([
                'first_name'  => $data['first_name'],
                'last_name'   => $data['last_name'] ?? null,
                'email'       => $data['email'] ?? null,
                'phone'       => $data['phone'] ?? null,
//                'password'    => Hash::make($data['password']) ?? null,
                'chat_id'  => $data['chat_id'] ?? null,
                'language'    => $data['language'] ?? 'en',
                //                'birth_date'  => $data['birth_date'] ?? null,
                //                'role_id'     => $role?->id ?? null,
            ]);

            //            $user->preferences()->create([
            //                'experience_level'    => $data['experience'] ?? null,
            //                'desired_salary_from' => $data['salary_from'] ?? null,
            //                'desired_salary_to'   => $data['salary_to'] ?? null,
            //                'currency'            => 'USD',
            //                'work_mode'           => $data['employment_type'] ?? null,
            //            ]);
            //
            //            // Location
            //            if (!empty($data['location'])) {
            //                $user->locations()->create([
            //                    'text'       => $data['location'],
            //                    'is_primary' => true,
            //                ]);
            //            }
            //
            //            // Job type
            //            if (!empty($data['employment_type'])) {
            //                $user->jobTypes()->create([
            //                    'job_type' => $data['employment_type'],
            //                ]);
            //            }
            //
            //            // Settings
            //            $user->settings()->create([
            //                'auto_apply_enabled'    => false,
            //                'auto_apply_limit'      => 0,
            //                'notifications_enabled' => true,
            //                'language'              => 'uz',
            //            ]);

            // Initial credit balance
            $user->credit()->create([
                'balance' => 100,
            ]);



            $token = $user->createToken(
                'api_token',
                ['*'],
                now()->addYears(22)
            )->plainTextToken;



            // ✅ Avtomatik login qilingan formatda qaytarish
            return [
                'status' => 'success',
                'data'   => [
                    'user'       => $user->load([
                        //                        'role',
                        //                        'settings',
                        'credit',
                        //                        'preferences',
                        //                        'locations',
                        //                        'jobTypes',
                        'resumes',
                    ]),
                    'token'      => $token,
                    'expires_at' => now()->addYears(22)->toDateTimeString(),
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
//                'last_name'   => $data['last_name'] ?? $user->last_name,
                'phone'       => $data['phone'] ?? $user->phone,
//                'password'    => !empty($data['password']) ? Hash::make($data['password']) : $user->password,
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
            now()->addYears(22)
        )->plainTextToken;

        return [
            'status' => 'success',
            'data'   => [
                'user'       => $user,
                'token'      => $token,
                'expires_at' => now()->addYears(22)->toDateTimeString(),
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
