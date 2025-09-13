<?php

namespace Modules\Vacancies\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Vacancies\Interfaces\HHVacancyInterface;

class HHVacancyController extends Controller
{
    protected HHVacancyInterface $hh;

    public function __construct(HHVacancyInterface $hh)
    {
        $this->hh = $hh;
    }

    public function index(Request $request)
    {
        $query   = $request->get('query', 'laravel developer'); 
        $page    = (int) $request->get('page', 0);
        $perPage = (int) $request->get('per_page', 20);

        $vacancies = $this->hh->search($query, $page, $perPage);

        return response()->json([
            'success' => true,
            'data'    => $vacancies,
        ]);
    }

    public function show(string $id)
    {
        $vacancy = $this->hh->getById($id);

        return response()->json([
            'success' => true,
            'data'    => $vacancy,
        ]);
    }
}
