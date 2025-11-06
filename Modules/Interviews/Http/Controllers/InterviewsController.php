<?php

namespace Modules\Interviews\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Interview;
use App\Models\Vacancy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Modules\Vacancies\Interfaces\HHVacancyInterface;

class InterviewsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $userId = Auth::id();

        $query = Interview::query()
            ->whereHas('application', fn($q) => $q->where('user_id', $userId))
            ->with(['application.vacancy'])
            ->orderByDesc('id');

        // Optional filters for status and vacancy source
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }
        if ($source = $request->get('source')) {
            $query->whereHas('application.vacancy', function ($q) use ($source) {
                $q->where('source', $source);
            });
        }

        $perPage = (int) $request->get('per_page', 15);
        $interviews = $query->paginate($perPage);

        $data = $interviews->getCollection()->map(function (Interview $i) {
            $preview = $i->preparations()->limit(4)->pluck('question')->all();
            $total = $i->preparations()->count();
            $vacancy = $i->application->vacancy;

            $raw = $this->decodeVacancyRaw($vacancy);
            $title = $vacancy?->title ?? ($raw['name'] ?? null);
            $company = $this->resolveVacancyCompany($vacancy);
            $experience = $raw['experience']['name'] ?? null;

            // published_at: prefer raw published_at, fallback to DB field
            $publishedAt = $raw['published_at'] ?? ($vacancy?->published_at ?? null);

            return [
                'id' => $i->id,
                'status' => $i->status,
                'created_at' => $i->created_at,
                'vacancy' => [
                    'id' => $vacancy?->id ?? $i->application->vacancy_id ?? null,
                    'source' => $vacancy?->source,
                    'external_id' => $vacancy?->external_id ?? null,
                    'title' => $title,
                    'company' => $company,
                    'experience' => $experience,
                    'published_at' => $publishedAt,
                ],
                // Keep existing fields for backward compatibility
                'questions_preview' => $preview,
                'total_questions' => $total,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            // 'meta' => [
            //     'current_page' => $interviews->currentPage(),
            //     'per_page' => $interviews->perPage(),
            //     'total' => $interviews->total(),
            // ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create() { abort(404); }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) { abort(404); }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        $userId = Auth::id();
        $i = Interview::where('id', $id)
            ->whereHas('application', fn($q) => $q->where('user_id', $userId))
            ->with(['application.vacancy', 'preparations'])
            ->firstOrFail();

        $vacancy = $i->application->vacancy;
        $questions = $i->preparations->pluck('question')->values()->all();

        // Build HH-style raw block if available (for discard flow)
        $raw = $this->decodeVacancyRaw($vacancy);
        if (!$raw && $vacancy?->external_id && $vacancy?->source === 'hh') {
            try {
                $raw = app(HHVacancyInterface::class)->getById($vacancy->external_id);
            } catch (\Throwable $e) {
                Log::warning('HH getById failed in InterviewsController@show', [
                    'id' => $vacancy->external_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $i->id,
                'status' => $i->status,
                'created_at' => $i->created_at,
                'vacancy' => [
                    'id' => $vacancy?->id ?? $i->application->vacancy_id ?? null,
                    'title' => $vacancy?->title,
                    'company' => $this->resolveVacancyCompany($vacancy),
                    'source' => $vacancy?->source,
                    'external_id' => $vacancy?->external_id ?? null,
                    // HH-style raw payload (used by frontend only for discard flow)
                    'raw' => $raw,
                ],
                'questions' => $questions,
            ],
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id) { abort(404); }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id) { abort(404); }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id) { abort(404); }

    /**
     * Extract company name from vacancy using stored data.
     */
    protected function resolveVacancyCompany(?Vacancy $vacancy): ?string
    {
        if (!$vacancy) {
            return null;
        }

        if (!empty($vacancy->company)) {
            return $vacancy->company;
        }

        if (empty($vacancy->raw_data)) {
            return null;
        }

        $raw = json_decode($vacancy->raw_data, true);

        return is_array($raw) ? data_get($raw, 'employer.name') : null;
    }

    /**
     * Helper: decode raw vacancy JSON if available.
     */
    protected function decodeVacancyRaw(?Vacancy $vacancy): ?array
    {
        if (!$vacancy || empty($vacancy->raw_data)) {
            return null;
        }
        $raw = json_decode($vacancy->raw_data, true);
        return is_array($raw) ? $raw : null;
    }
}
