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
use Illuminate\Support\Facades\DB;

class VacancyRepository implements VacancyInterface
{


    public function firstOrCreateFromHH(array $data): Vacancy
    {
        return $this->bulkUpsertFromHH([$data])[$data['id']];
    }

    public function bulkUpsertFromHH(array $vacanciesData): array
    {
        if (empty($vacanciesData)) {
            return [];
        }

        $now = now();
        $employers = [];
        $areas = [];
        $schedules = [];
        $employments = [];
        $vacancies = [];

        foreach ($vacanciesData as $data) {
            $salary = is_array($data['salary'] ?? null) ? $data['salary'] : [];

            $publishedAt = null;
            if (!empty($data['published_at'])) {
                try {
                    $publishedAt = Carbon::parse($data['published_at'])->format('Y-m-d H:i:s');
                } catch (\Exception $e) {
                    Log::warning('Invalid published_at format', ['value' => $data['published_at']]);
                }
            }

            if (!empty($data['employer'])) {
                $employers[$data['employer']['id']] = [
                    'source' => 'hh',
                    'external_id' => $data['employer']['id'],
                    'name' => $data['employer']['name'] ?? '',
                    'url' => $data['employer']['url'] ?? null,
                    'raw_json' => json_encode($data['employer'], JSON_UNESCAPED_UNICODE),
                ];
            }

            if (!empty($data['area'])) {
                $areas[$data['area']['id']] = [
                    'source' => 'hh',
                    'external_id' => $data['area']['id'],
                    'name' => $data['area']['name'] ?? '',
                    'raw_json' => json_encode($data['area'], JSON_UNESCAPED_UNICODE),
                ];
            }

            if (!empty($data['schedule'])) {
                $schedules[$data['schedule']['id']] = [
                    'external_id' => $data['schedule']['id'],
                    'name'        => $data['schedule']['name'] ?? '',
                    'raw_json'    => json_encode($data['schedule'], JSON_UNESCAPED_UNICODE),
                ];
            }

            if (!empty($data['employment'])) {
                $employments[$data['employment']['id']] = [
                    'external_id' => $data['employment']['id'],
                    'name'        => $data['employment']['name'] ?? '',
                    'raw_json'    => json_encode($data['employment'], JSON_UNESCAPED_UNICODE),
                ];
            }
        }

        DB::transaction(function () use ($employers, $areas, $schedules, $employments) {
            if ($employers) {
                Employer::upsert(array_values($employers), ['source', 'external_id'], ['name', 'url', 'raw_json', 'updated_at']);
            }
            if ($areas) {
                Area::upsert(array_values($areas), ['source', 'external_id'], ['name', 'raw_json']);
            }
            if ($schedules) {
                HhSchedule::upsert(array_values($schedules), ['external_id'], ['name', 'raw_json']);
            }
            if ($employments) {
                HhEmployment::upsert(array_values($employments), ['external_id'], ['name', 'raw_json']);
            }
        });

        $employerMap   = Employer::whereIn('external_id', array_keys($employers))->pluck('id', 'external_id');
        $areaMap       = Area::whereIn('external_id', array_keys($areas))->pluck('id', 'external_id');
        $scheduleMap   = HhSchedule::whereIn('external_id', array_keys($schedules))->pluck('id', 'external_id');
        $employmentMap = HhEmployment::whereIn('external_id', array_keys($employments))->pluck('id', 'external_id');

        foreach ($vacanciesData as $data) {
            $salary = is_array($data['salary'] ?? null) ? $data['salary'] : [];
            $publishedAt = !empty($data['published_at']) ? Carbon::parse($data['published_at'])->format('Y-m-d H:i:s') : null;

            $vacancies[] = [
                'source'          => 'hh',
                'external_id'     => $data['id'],
                'employer_id'     => !empty($data['employer']['id']) ? $employerMap[$data['employer']['id']] ?? null : null,
                'area_id'         => !empty($data['area']['id']) ? $areaMap[$data['area']['id']] ?? null : null,
                'schedule_id'     => !empty($data['schedule']['id']) ? $scheduleMap[$data['schedule']['id']] ?? null : null,
                'employment_id'   => !empty($data['employment']['id']) ? $employmentMap[$data['employment']['id']] ?? null : null,
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
                'created_at'      => $now,
                'updated_at'      => $now,
            ];
        }

        Vacancy::upsert(
            $vacancies,
            ['source', 'external_id'],
            [
                'employer_id',
                'area_id',
                'schedule_id',
                'employment_id',
                'title',
                'description',
                'salary_from',
                'salary_to',
                'salary_currency',
                'salary_gross',
                'published_at',
                'apply_url',
                'raw_data',
                'updated_at'
            ]
        );

        $ids = array_column($vacancies, 'external_id');
        return Vacancy::whereIn('external_id', $ids)->get()->keyBy('external_id')->all();
    }


    public function createFromHH(array $hhVacancy): Vacancy
    {
        $now = now();

        // Employer
        $employerId = null;
        if (!empty($hhVacancy['employer'])) {
            $employer = Employer::updateOrCreate(
                [
                    'source'      => 'hh',
                    'external_id' => $hhVacancy['employer']['id'],
                ],
                [
                    'name'     => $hhVacancy['employer']['name'] ?? '',
                    'url'      => $hhVacancy['employer']['url'] ?? null,
                    'raw_json' => json_encode($hhVacancy['employer'], JSON_UNESCAPED_UNICODE),
                ]
            );
            $employerId = $employer->id;
        }

        // Area
        $areaId = null;
        if (!empty($hhVacancy['area'])) {
            $area = Area::updateOrCreate(
                [
                    'source'      => 'hh',
                    'external_id' => $hhVacancy['area']['id'],
                ],
                [
                    'name'     => $hhVacancy['area']['name'] ?? '',
                    'raw_json' => json_encode($hhVacancy['area'], JSON_UNESCAPED_UNICODE),
                ]
            );
            $areaId = $area->id;
        }

        // Schedule
        $scheduleId = null;
        if (!empty($hhVacancy['schedule'])) {
            $schedule = HhSchedule::updateOrCreate(
                [
                    'external_id' => $hhVacancy['schedule']['id'],
                ],
                [
                    'name'     => $hhVacancy['schedule']['name'] ?? '',
                    'raw_json' => json_encode($hhVacancy['schedule'], JSON_UNESCAPED_UNICODE),
                ]
            );
            $scheduleId = $schedule->id;
        }

        // Employment
        $employmentId = null;
        if (!empty($hhVacancy['employment'])) {
            $employment = HhEmployment::updateOrCreate(
                [
                    'external_id' => $hhVacancy['employment']['id'],
                ],
                [
                    'name'     => $hhVacancy['employment']['name'] ?? '',
                    'raw_json' => json_encode($hhVacancy['employment'], JSON_UNESCAPED_UNICODE),
                ]
            );
            $employmentId = $employment->id;
        }

        // Salary
        $salary = is_array($hhVacancy['salary'] ?? null) ? $hhVacancy['salary'] : [];

        // Published date
        $publishedAt = null;
        if (!empty($hhVacancy['published_at'])) {
            try {
                $publishedAt = \Carbon\Carbon::parse($hhVacancy['published_at'])->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
                Log::warning('Invalid published_at format', ['value' => $hhVacancy['published_at']]);
            }
        }

        // Create or update vacancy
        $vacancy = Vacancy::updateOrCreate(
            [
                'source'      => 'hh',
                'external_id' => $hhVacancy['id'],
            ],
            [
                'employer_id'     => $employerId,
                'area_id'         => $areaId,
                'schedule_id'     => $scheduleId,
                'employment_id'   => $employmentId,
                'title'           => $hhVacancy['name'] ?? '',
                'description'     => $hhVacancy['description']
                    ?? (($hhVacancy['snippet']['requirement'] ?? '') . "\n" . ($hhVacancy['snippet']['responsibility'] ?? '')),
                'salary_from'     => $salary['from'] ?? null,
                'salary_to'       => $salary['to'] ?? null,
                'salary_currency' => $salary['currency'] ?? null,
                'salary_gross'    => $salary['gross'] ?? false,
                'published_at'    => $publishedAt,
                'apply_url'       => $hhVacancy['alternate_url'] ?? null,
                'raw_data'        => json_encode($hhVacancy, JSON_UNESCAPED_UNICODE),
                'updated_at'      => $now,
            ]
        );

        return $vacancy;
    }
}
