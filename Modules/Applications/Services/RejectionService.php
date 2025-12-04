<?php

namespace Modules\Applications\Services;

use App\Models\Application;
use App\Models\User;
use App\Models\Vacancy;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class RejectionService
{
    /**
     * HH dan keladigan otkaz statuslari.
     *
     * Bu ro'yxat faqat HH integratsiyasi uchun ishlatiladi
     * va faqat applications.status / applications.hh_status
     * qiymatlarini filtrlashda qo'llaniladi.
     */
    public const REJECTION_STATUSES = [
        'rejected',
        'discard',
        'discarded',
        'declined',
        'refusal',
    ];

    /**
     * Foydalanuvchining HH otkazlari ro'yxatini qaytaradi.
     *
     * Hozircha bu faqat backend ichida ishlatiladi,
     * HTTP API orqali chiqmaydi.
     */
    public function listForUser(User $user): Collection
    {
        $applications = Application::query()
            ->where('user_id', $user->id)
            ->whereIn('status', self::REJECTION_STATUSES)
            ->whereHas('vacancy', function ($q) {
                $q->where('source', 'hh');
            })
            ->with(['vacancy'])
            ->orderByDesc('updated_at')
            ->get();

        return $applications->map(function (Application $app) {
            return $this->mapApplicationToPayload($app);
        });
    }

    /**
     * Bitta application bo'yicha otkaz ma'lumotini qaytaradi.
     */
    public function findForUser(User $user, int $applicationId): ?array
    {
        $application = Application::query()
            ->where('id', $applicationId)
            ->where('user_id', $user->id)
            ->whereIn('status', self::REJECTION_STATUSES)
            ->whereHas('vacancy', function ($q) {
                $q->where('source', 'hh');
            })
            ->with(['vacancy'])
            ->first();

        if (! $application) {
            return null;
        }

        return $this->mapApplicationToPayload($application);
    }

    /**
     * Foydalanuvchining HH otkazlari umumiy soni.
     */
    public function countForUser(User $user): int
    {
        return Application::query()
            ->where('user_id', $user->id)
            ->whereIn('status', self::REJECTION_STATUSES)
            ->whereHas('vacancy', function ($q) {
                $q->where('source', 'hh');
            })
            ->count();
    }

    /**
     * Application modelini frontend kutayotgan payload strukturaga map qiladi.
     */
    protected function mapApplicationToPayload(Application $app): array
    {
        $vacancy = $app->vacancy ?: Vacancy::find($app->vacancy_id);
        $raw = $vacancy?->raw_data ? json_decode($vacancy->raw_data, true) : [];

        $vacancyData = [
            'id'          => $vacancy?->id,
            'external_id' => $vacancy?->external_id,
            'title'       => $vacancy?->title,
            'company'     => $raw['employer']['name'] ?? null,
            'experience'  => $raw['experience']['name'] ?? null,
            'published_at' => isset($raw['published_at'])
                ? Carbon::parse($raw['published_at'])->toISOString()
                : null,
        ];

        return [
            'id'         => $app->id,
            'status'     => $app->status,
            'created_at' => $app->updated_at,
            'vacancy'    => $vacancyData,
        ];
    }
}

