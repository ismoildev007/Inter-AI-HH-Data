<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PlanController extends Controller
{
    /**
     * Display a listing of the plans.
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));

        $baseQuery = Plan::query();

        $plans = (clone $baseQuery)
            ->when($search !== '', function ($query) use ($search) {
                $normalized = mb_strtolower($search, 'UTF-8');
                $like = '%'.$normalized.'%';

                $query->where(function ($inner) use ($like, $search) {
                    $inner->whereRaw('LOWER(name) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(description) LIKE ?', [$like]);

                    if (is_numeric($search)) {
                        $inner->orWhere('price', $search)
                            ->orWhere('fake_price', $search)
                            ->orWhere('auto_response_limit', (int) $search);
                    }
                });
            })
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $totalPlans = (clone $baseQuery)->count();
        $averagePrice = (clone $baseQuery)->whereNotNull('price')->avg('price');
        $maxAutoResponses = (clone $baseQuery)->max('auto_response_limit');

        return view('admin::Plan.index', [
            'plans' => $plans,
            'search' => $search,
            'totalPlans' => $totalPlans,
            'averagePrice' => $averagePrice,
            'maxAutoResponses' => $maxAutoResponses,
        ]);
    }

    /**
     * Show the form for creating a new plan.
     */
    public function create()
    {
        return view('admin::Plan.create', [
            'plan' => new Plan(),
        ]);
    }

    /**
     * Store a newly created plan.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatePayload($request);

        $plan = Plan::create($data);

        return redirect()
            ->route('admin.plans.show', $plan)
            ->with('status', 'Plan created successfully.');
    }

    /**
     * Display the specified plan.
     */
    public function show(Plan $plan)
    {
        $subscriptionCount = $plan->subscriptions()->count();
        $revenuePotential = $plan->price ? (float) $plan->price * $subscriptionCount : null;
        $autoResponsePerPrice = ($plan->price && $plan->auto_response_limit > 0)
            ? $plan->auto_response_limit / (float) $plan->price
            : null;

        return view('admin::Plan.show', [
            'plan' => $plan,
            'subscriptionCount' => $subscriptionCount,
            'revenuePotential' => $revenuePotential,
            'autoResponsePerPrice' => $autoResponsePerPrice,
        ]);
    }

    /**
     * Show the form for editing the specified plan.
     */
    public function edit(Plan $plan)
    {
        return view('admin::Plan.edit', [
            'plan' => $plan,
        ]);
    }

    /**
     * Update the specified plan.
     */
    public function update(Request $request, Plan $plan): RedirectResponse
    {
        $data = $this->validatePayload($request, $plan);

        $plan->update($data);

        return redirect()
            ->route('admin.plans.show', $plan)
            ->with('status', 'Plan updated successfully.');
    }

    /**
     * Remove the specified plan from storage.
     */
    public function destroy(Request $request, Plan $plan): RedirectResponse
    {
        $plan->delete();

        return redirect()
            ->route('admin.plans.index')
            ->with('status', 'Plan deleted successfully.');
    }

    /**
     * Validate incoming data for create/update.
     */
    protected function validatePayload(Request $request, ?Plan $plan = null): array
    {
        $planId = $plan?->getKey();

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('plans', 'name')->ignore($planId),
            ],
            'description' => ['nullable', 'string'],
            'fake_price' => ['nullable', 'numeric', 'min:0'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'auto_response_limit' => ['nullable', 'integer', 'min:0'],
            'duration' => ['nullable', 'date'],
        ]);

        $validated['auto_response_limit'] = $validated['auto_response_limit'] ?? 0;

        return $validated;
    }
}
