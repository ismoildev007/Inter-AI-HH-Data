<?php

namespace Modules\Users\Repositories;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthRepository
{
    public function register(array $data): array
    {
        return \DB::transaction(function () use ($data) {
            if (User::where('email', $data['email'])->exists()) {
                return [
                    'status'  => 'error',
                    'message' => 'Email already exists',
                    'code'    => 422,
                ];
            }

            if (!empty($data['phone']) && User::where('phone', $data['phone'])->exists()) {
                return [
                    'status'  => 'error',
                    'message' => 'Phone already exists',
                    'code'    => 422,
                ];
            }

            $role = Role::where('name', 'job_seeker')->first();

            $user = User::create([
                'first_name'  => $data['first_name'],
                'last_name'   => $data['last_name'],
                'email'       => $data['email'],
                'phone'       => $data['phone'] ?? null,
                'password'    => Hash::make($data['password']),
                'birth_date'  => $data['birthDate'] ?? null,
                'role_id'     => $role?->id ?? null,
            ]);

            // Resume (agar matn bo‘lsa)
            if (!empty($data['resume_text'])) {
                $user->resumes()->create([
                    'title'       => 'Text Resume',
                    'description' => $data['resume_text'],
                    'parsed_text' => $data['resume_text'],
                    'is_primary'  => true,
                ]);
            }

            // Preferences
            $user->preferences()->create([
                'experience_level'    => $data['experience'] ?? null,
                'desired_salary_from' => $data['salary_from'] ?? null,
                'desired_salary_to'   => $data['salary_to'] ?? null,
                'currency'            => 'USD',
                'work_mode'           => $data['employment_type'] ?? null,
            ]);

            // Location
            if (!empty($data['location'])) {
                $user->locations()->create([
                    'text'       => $data['location'],
                    'is_primary' => true,
                ]);
            }

            // Job type
            if (!empty($data['employment_type'])) {
                $user->jobTypes()->create([
                    'job_type' => $data['employment_type'],
                ]);
            }

            // Settings
            $user->settings()->create([
                'auto_apply_enabled'    => false,
                'auto_apply_limit'      => 0,
                'notifications_enabled' => true,
                'language'              => 'uz',
            ]);

            // ✅ Token yaratish (login bilan bir xil)
            $token = $user->createToken(
                'api_token',
                ['*'],
                now()->addHours(4)
            )->plainTextToken;

            // ✅ Avtomatik login qilingan formatda qaytarish
            return [
                'status' => 'success',
                'data'   => [
                    'user'       => $user->load([
                        'role',
                        'settings',
                        'preferences',
                        'locations',
                        'jobTypes',
                        'resumes',
                    ]),
                    'token'      => $token,
                    'expires_at' => now()->addHours(4)->toDateTimeString(),
                ],
            ];
        });
    }
    public function update(User $user, array $data): array
    {
        return \DB::transaction(function () use ($user, $data) {

            if (!empty($data['email']) && User::where('email', $data['email'])->where('id', '!=', $user->id)->exists()) {
                return [
                    'status'  => 'error',
                    'message' => 'Email already exists',
                    'code'    => 422,
                ];
            }

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
                'email'       => $data['email'] ?? $user->email,
                'phone'       => $data['phone'] ?? $user->phone,
                'password'    => !empty($data['password']) ? Hash::make($data['password']) : $user->password,
                'birth_date'  => $data['birthDate'] ?? $user->birth_date,
            ]);

            if (!empty($data['resume_text'])) {
                $resume = $user->resumes()->first();
                if ($resume) {
                    $resume->update([
                        'description' => $data['resume_text'],
                        'parsed_text' => $data['resume_text'],
                    ]);
                } else {
                    $user->resumes()->create([
                        'title'       => 'Text Resume',
                        'description' => $data['resume_text'],
                        'parsed_text' => $data['resume_text'],
                        'is_primary'  => true,
                    ]);
                }
            }

            if (!empty($data['experience']) || !empty($data['salary_from']) || !empty($data['salary_to'])) {
                $user->preferences()->updateOrCreate([], [
                    'experience_level'    => $data['experience'] ?? $user->preferences->experience_level ?? null,
                    'desired_salary_from' => $data['salary_from'] ?? $user->preferences->desired_salary_from ?? null,
                    'desired_salary_to'   => $data['salary_to'] ?? $user->preferences->desired_salary_to ?? null,
                    'currency'            => 'USD',
                    'work_mode'           => $data['employment_type'] ?? $user->preferences->work_mode ?? null,
                ]);
            }

            if (!empty($data['location'])) {
                $user->locations()->updateOrCreate(['is_primary' => true], [
                    'text'       => $data['location'],
                    'is_primary' => true,
                ]);
            }

            if (!empty($data['employment_type'])) {
                $user->jobTypes()->delete();
                $user->jobTypes()->create([
                    'job_type' => $data['employment_type'],
                ]);
            }

            if (!empty($data['settings'])) {
                $user->settings()->updateOrCreate([], [
                    //'auto_apply_enabled'    => $data['settings']['auto_apply_enabled'] ?? $user->settings->auto_apply_enabled,
                    //'auto_apply_limit'      => $data['settings']['auto_apply_limit'] ?? $user->settings->auto_apply_limit,
                    'notifications_enabled' => $data['settings']['notifications_enabled'] ?? $user->settings->notifications_enabled,
                    'language'              => $data['settings']['language'] ?? $user->settings->language,
                ]);
            }

            return [
                'status' => 'success',
                'data'   => $user->load([
                    'role',
                    'settings',
                    'preferences',
                    'locations',
                    'jobTypes',
                    'resumes',
                ]),
            ];
        });
    }
    public function login(array $credentials): ?array
    {
        if (!Auth::attempt($credentials)) {
            return null;
        }

        $user  = Auth::user();
        $token = $user->createToken(
            'api_token',
            ['*'],
            now()->addHours(4)
        )->plainTextToken;

        return [
            'user'       => $user,
            'token'      => $token,
            'expires_at' => now()->addHours(4)->toDateTimeString(),
        ];
    }
}
