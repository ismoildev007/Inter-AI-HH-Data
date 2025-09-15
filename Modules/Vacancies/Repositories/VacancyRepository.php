<?php

namespace Modules\Vacancies\Repositories;

use App\Models\Vacancy;
use App\Models\Employer;
use App\Models\Area;
use App\Models\HhSchedule;
use App\Models\HhEmployment;
use Illuminate\Support\Facades\Log;
use Modules\Vacancies\Interfaces\VacancyInterface;
use Carbon\Carbon;

class VacancyRepository implements VacancyInterface
{
    public function firstOrCreateFromHH(array $data): Vacancy
    {
        Log::info(['data' => $data]);

        $salary = is_array($data['salary'] ?? null) ? $data['salary'] : [];

        // Convert published_at safely
        $publishedAt = null;
        if (!empty($data['published_at'])) {
            try {
                $publishedAt = Carbon::parse($data['published_at'])->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
                Log::warning('Invalid published_at format', ['value' => $data['published_at']]);
            }
        }

        // --- Employer ---
        $employerId = null;
        if (!empty($data['employer'])) {
            $employer = Employer::firstOrCreate(
                [
                    'source'      => 'hh',
                    'external_id' => $data['employer']['id'],
                ],
                [
                    'name'     => $data['employer']['name'] ?? '',
                    'url'      => $data['employer']['url'] ?? null,
                    'raw_json' => $data['employer'],
                ]
            );
            $employerId = $employer->id;
        }

        // --- Area ---
        $areaId = null;
        if (!empty($data['area'])) {
            $area = Area::firstOrCreate(
                [
                    'source'      => 'hh',
                    'external_id' => $data['area']['id'],
                ],
                [
                    'name'     => $data['area']['name'] ?? '',
                    'raw_json' => $data['area'],
                ]
            );
            $areaId = $area->id;
        }

        // --- Schedule ---
        $scheduleId = null;
        if (!empty($data['schedule'])) {
            $schedule = HhSchedule::firstOrCreate(
                [
                    'external_id' => $data['schedule']['id'],
                ],
                [
                    'name'     => $data['schedule']['name'] ?? '',
                    'raw_json' => $data['schedule'],
                ]
            );
            $scheduleId = $schedule->id;
        }

        // --- Employment ---
        $employmentId = null;
        if (!empty($data['employment'])) {
            $employment = HhEmployment::firstOrCreate(
                [
                    'external_id' => $data['employment']['id'],
                ],
                [
                    'name'     => $data['employment']['name'] ?? '',
                    'raw_json' => $data['employment'],
                ]
            );
            $employmentId = $employment->id;
        }

        // --- Vacancy ---
        return Vacancy::firstOrCreate(
            [
                'source'      => 'hh',
                'external_id' => $data['id'],
            ],
            [
                'employer_id'     => $employerId,
                'area_id'         => $areaId,
                'schedule_id'     => $scheduleId,
                'employment_id'   => $employmentId,
                'title'           => $data['name'] ?? '',
                'description'     => $data['description']
                    ?? (($data['snippet']['requirement'] ?? '') . "\n" . ($data['snippet']['responsibility'] ?? '')),
                'salary_from'     => $salary['from'] ?? null,
                'salary_to'       => $salary['to'] ?? null,
                'salary_currency' => $salary['currency'] ?? null,
                'salary_gross'    => $salary['gross'] ?? false,
                'published_at'    => $publishedAt,
                'apply_url'       => $data['alternate_url'] ?? null,
                'raw_data'        => json_encode($data, JSON_UNESCAPED_UNICODE),
            ]
        );
    }

    // public function firstOrCreateFromHH(array $data): Vacancy
    // {
    //     return $this->bulkUpsertFromHH([$data])[$data['id']];
    // }

    // public function bulkUpsertFromHH(array $vacanciesData): array
    // {
    //     if (empty($vacanciesData)) {
    //         return [];
    //     }

    //     $now = now();
    //     $employers = [];
    //     $areas = [];
    //     $schedules = [];
    //     $employments = [];
    //     $vacacies = [];

    //     foreach ($vacanciesData as $data) {
    //         $salary = is_array($data['salary'] ?? null) ? $data['salary'] : [];

    //         $publishedAt = null;
    //         if (!empty($data['published_at'])) {
    //             try {
    //                 $publishedAt = Carbon::parse($data['published_at'])->format('Y-m-d H:i:s');
    //             } catch (\Exception $e) {
    //                 Log::warning('Invalid published_at format', ['value' => $data['published_at']]);
    //             }
    //         }

    //         if (!empty($data['employer'])) {
    //             $employers[$data['employer']['id']] = [
    //                 'source' => 'hh',
    //                 'external_id' => $data['employer']['id'],
    //                 'name' => $data['employer']['name'] ?? '',
    //                 'url' => $data['employer']['url'] ?? null,
    //                 'raw_json' => json_encode($data['employer'], JSON_UNESCAPED_UNICODE),
    //             ];
    //         }

    //         if (!empty($data['area'])) {
    //             $areas[$data['area']['id']] = [
    //                 'source' => 'hh',
    //                 'external_id' => $data['area']['id'],
    //                 'name' => $data['area']['name'] ?? '',
    //                 'raw_json' => json_encode($data['area'], JSON_UNESCAPED_UNICODE),
    //             ];
    //         }

    //         if (!empty($data['schedule'])) {
    //             $schedules[$data['schedule']['id']] = [
    //                 'external_id' => $data['schedule']['id'],
    //                 'name'        => $data['schedule']['name'] ?? '',
    //                 'raw_json'    => json_encode($data['schedule'], JSON_UNESCAPED_UNICODE),
    //             ];
    //         }


    //     }


    // }
}
