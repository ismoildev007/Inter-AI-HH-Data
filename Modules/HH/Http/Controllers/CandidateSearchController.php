<?php

namespace Modules\HH\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\HH\Models\SearchRequest;
use Modules\HH\Jobs\ProcessCandidateSearch;

class CandidateSearchController extends Controller
{
    public function create()
    {
        // Show the search form
        return view('hh::search.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'filters' => 'required|array',
            'custom_requirements' => 'required|string|min:50',
        ]);

        // For now, let's assume the logged-in user is associated with a company.
        // This part needs proper authentication and company management later.
        $company = auth()->user()->company; // Simplified for now

        $searchRequest = SearchRequest::create([
            'company_id' => $company->id,
            'filters' => $request->input('filters'),
            'custom_requirements' => $request->input('custom_requirements'),
        ]);

        // Dispatch the job to process the search in the background
        ProcessCandidateSearch::dispatch($searchRequest);

        // Redirect the user to a page explaining the process
        return redirect()->route('hh.search.processing');
    }

    public function processing()
    {
        return view('hh::search.processing');
    }

    public function results(SearchRequest $searchRequest)
    {
        // Add authorization to ensure the user can see these results
        $this->authorize('view', $searchRequest);

        $results = $searchRequest->results()->with('candidate')->orderBy('match_percentage', 'desc')->paginate(20);

        return view('hh::search.results', compact('results', 'searchRequest'));
    }
}
