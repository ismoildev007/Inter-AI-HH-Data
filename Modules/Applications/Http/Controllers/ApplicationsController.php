<?php

namespace Modules\Applications\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Vacancies\Interfaces\HHVacancyInterface;
use App\Models\Application;
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
            ->with(['vacancy:id,title,company,source'])
            ->orderByDesc('id')
            ->paginate($perPage);

        $data = $paginator->getCollection()->map(function (Application $app) {
            $vac = optional($app->vacancy);
            return [
                'id' => $app->id,
                'status' => $app->status,
                'hh_status' => $app->hh_status,
                'match_score' => $app->match_score,
                'submitted_at' => $app->submitted_at,
                'external_id' => $app->external_id,
                'vacancy' => [
                    'id' => $app->vacancy_id,
                    'title' => $vac->title,
                    'company' => $vac->company,
                    'source' => $vac->source,
                ],
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
