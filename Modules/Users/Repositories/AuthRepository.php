<?php

namespace Modules\Users\Repositories;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthRepository
{
    public function register(array $data): array
    {
        return \DB::transaction(function () use ($data) {
            // check email already exists
            if (User::where('email', $data['email'])->exists()) {
                return [
                    'error' => true,
                    'status' => 422,
                    'message' => 'Email already exists',
                ];
            }

            if (!empty($data['phone']) && User::where('phone', $data['phone'])->exists()) {
                return [
                    'error' => true,
                    'status' => 422,
                    'message' => 'Phone already exists',
                ];
            }

            // 1) User yaratamiz
            $user = User::create([
                'first_name'  => $data['first_name'],
                'last_name'   => $data['last_name'],
                'email'       => $data['email'],
                'phone'       => $data['phone'] ?? null,
                'password'    => Hash::make($data['password']),
                'birth_date'  => $data['birth_date'] ?? null,
                'avatar_path' => $data['avatar_path'] ?? null,
                'verify_code' => $data['verify_code'] ?? null,
                'role_id'     => $data['role_id'] ?? null,
            ]);

            // 2) Resume saqlash (file + text bo‘lsa ikkalasini ham saqlash)
            if (!empty($data['resume_file'])) {
                $path = $data['resume_file']->store('resumes', 'public');

                $user->resumes()->create([
                    'title'       => $data['resume_title'] ?? 'My Resume',
                    'description' => $data['resume_description'] ?? null,
                    'file_path'   => $path,
                    'file_mime'   => $data['resume_file']->getClientMimeType(),
                    'file_size'   => $data['resume_file']->getSize(),
                    'parsed_text' => $data['parsed_text'] ?? null,
                    'is_primary'  => true,
                ]);
            }

            if (!empty($data['resume_text'])) {
                $user->resumes()->create([
                    'title'       => $data['resume_title'] ?? 'Text Resume',
                    'description' => $data['resume_text'],
                    'file_path'   => null,
                    'file_mime'   => null,
                    'file_size'   => null,
                    'parsed_text' => $data['resume_text'],
                    'is_primary'  => empty($data['resume_file']), // faqat file yo‘q bo‘lsa asosiy
                ]);
            }

            // 3) Preferences
            if (!empty($data['preferences'])) {
                foreach ($data['preferences'] as $pref) {
                    $user->preferences()->create([
                        'industry_id'         => $pref['industry_id'] ?? null,
                        'experience_level'    => $pref['experience_level'] ?? null,
                        'desired_salary_from' => $pref['desired_salary_from'] ?? null,
                        'desired_salary_to'   => $pref['desired_salary_to'] ?? null,
                        'currency'            => $pref['currency'] ?? 'USD',
                        'work_mode'           => $pref['work_mode'] ?? null,
                        'notes'               => $pref['notes'] ?? null,
                        'cover_letter'        => $pref['cover_letter'] ?? null,
                    ]);
                }
            }

            // 4) Locations
            if (!empty($data['locations'])) {
                foreach ($data['locations'] as $loc) {
                    $user->locations()->create([
                        'text'       => $loc['text'] ?? null,
                        'area_id'    => $loc['area_id'] ?? null,
                        'is_primary' => $loc['is_primary'] ?? false,
                    ]);
                }
            }

            // 5) Job Types
            if (!empty($data['job_types'])) {
                foreach ($data['job_types'] as $jobType) {
                    $user->jobTypes()->create([
                        'job_type' => $jobType,
                    ]);
                }
            }

            // 6) Settings
            $user->settings()->create([
                'auto_apply_enabled'    => $data['auto_apply_enabled'] ?? false,
                'auto_apply_limit'      => $data['auto_apply_limit'] ?? 0,
                'notifications_enabled' => $data['notifications_enabled'] ?? true,
                'language'              => $data['language'] ?? 'ru',
            ]);

            // 7) Token
            $token = $user->createToken(
                'api_token',
                ['*'],
                now()->addHours(4)
            )->plainTextToken;

            return [
                'user'  => $user->load([
                    'role',
                    'settings',
                    'credit',
                    'preferences.industry',
                    'locations.area',
                    'jobTypes',
                    'profileViews.employer',
                    'resumes',
                ]),
                'token' => $token,
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
            'user'  => $user,
            'token' => $token,
            'expires_at' => now()->addHours(4)->toDateTimeString(),
        ];
    }
}
