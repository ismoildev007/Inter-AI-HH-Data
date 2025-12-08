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

    

        $perPage = (int) $request->get('per_page', 15);
        $interviews = $query->paginate($perPage);

        $data = $interviews->getCollection()->map(function (Interview $i) {
            $preview = $i->preparations()->limit(4)->pluck('question')->all();
            $total = $i->preparations()->count();
            $vacancy = $i->application->vacancy;



            return [
                'id' => $i->id,
                'status' => $i->status,
                'created_at' => $i->created_at,
                'vacancy' => [
                    'id' => $vacancy?->id ?? $i->application->vacancy_id ?? null,
                    'title' => $vacancy?->title,
                    'company' => $this->resolveVacancyCompany($vacancy),
                ],
              
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

}
