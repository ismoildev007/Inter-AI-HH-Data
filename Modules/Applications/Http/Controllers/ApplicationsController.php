<?php

namespace Modules\Applications\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Vacancies\Interfaces\HHVacancyInterface;
use App\Models\Application;
use App\Models\Vacancy;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class ApplicationsController extends Controller
{
    public function __construct(private readonly HHVacancyInterface $hh) {}
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        $perPage = (int) $request->get('per_page', 15);

        $paginator = Application::query()
            ->where('user_id', $user->id)
            ->with(['vacancy' => function ($q) {
                $q->select('id', 'source', 'external_id', 'title', 'raw_data', 'target_message_id', 'area_id');
            }, 'vacancy.area:id,name'])
            ->orderByDesc('id')
            ->paginate($perPage);

        $data = $paginator->getCollection()->map(function (Application $app) use ($user) {
            $vacancy = $app->vacancy ?: Vacancy::find($app->vacancy_id);
            $raw = $vacancy?->raw_data ? json_decode($vacancy->raw_data, true) : [];

            // In applications list, user has already applied => true
            $applied = true;

            $vacancyData = [
                'id'          => $vacancy?->id,
                'source'      => $vacancy->source ?? 'telegram',
                'external_id' => $vacancy?->external_id ?? null,
                'company'     => $raw['employer']['name'] ?? null,
                'title'       => $vacancy?->title,
                'location'    => $vacancy?->area?->name
                    ?? ($raw['area']['name'] ?? null),
                'experience'  => $raw['experience']['name'] ?? null,
                'salary'      => $raw['salary'] ?? null,
                'published_at' => isset($raw['published_at'])
                    ? Carbon::parse($raw['published_at'])->format('Y-m-d H:i:s')
                    : null,
            ];

            if (($vacancy?->source ?? null) === 'telegram') {
                $vacancyData['message_id'] = $vacancy->target_message_id;
            }

            return [
                'resume_id'     => $app->resume_id,
                'vacancy_id'    => $app->vacancy_id,
                'score_percent' => (int) round((float) ($app->match_score ?? 0)),
                'status'        => $applied,
                'vacancy'       => $vacancyData,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('applications::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {}

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('applications::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('applications::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id) {}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id) {}

    /**
     * Return HH negotiations for the authenticated user.
     */
    public function negotiations(Request $request)
    {
        $page    = (int) $request->get('page', 0);
        $perPage = (int) $request->get('per_page', 100);

        $result = $this->hh->listNegotiations($page, $perPage);
        $status = $result['status'] ?? 200;

        return response()->json([
            'success' => $result['success'] ?? false,
            'data'    => $result['data'] ?? null,
            'message' => $result['message'] ?? null,
        ], $status);
    }
}
