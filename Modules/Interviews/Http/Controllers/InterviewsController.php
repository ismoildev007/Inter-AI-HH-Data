<?php

namespace Modules\Interviews\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Interview;
use Illuminate\Support\Facades\Auth;

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
            $vacancy = optional($i->application->vacancy);
            return [
                'id' => $i->id,
                'status' => $i->status,
                'created_at' => $i->created_at,
                'vacancy' => [
                    'id' => $i->application->vacancy_id ?? null,
                    'title' => $vacancy->title ?? null,
                    'company' => $vacancy->company ?? null,
                ],
                'questions_preview' => $preview,
                'total_questions' => $total,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => [
                'current_page' => $interviews->currentPage(),
                'per_page' => $interviews->perPage(),
                'total' => $interviews->total(),
            ],
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

        $vacancy = optional($i->application->vacancy);
        $questions = $i->preparations->pluck('question')->values()->all();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $i->id,
                'status' => $i->status,
                'created_at' => $i->created_at,
                'vacancy' => [
                    'id' => $i->application->vacancy_id ?? null,
                    'title' => $vacancy->title ?? null,
                    'company' => $vacancy->company ?? null,
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
}
