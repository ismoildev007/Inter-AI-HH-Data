<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Resume;
use App\Models\User;
use App\Models\TelegramChannel;
use App\Models\Visit;
use App\Models\Vacancy;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Admin dashboard.
     */
    public function index()
    {
        $sourceFilter = config('analytics.visits_source', 'api'); // 'api' | 'web' | 'all'
        $usersCount = User::count();
        $resumesCount = Resume::count();
        $applicationsCount = Application::count();
        $telegramChannelsCount = TelegramChannel::count();
        $tgSourceCount = TelegramChannel::where('is_source', true)->count();
        $tgTargetCount = TelegramChannel::where('is_target', true)->count();

        $tgTargetPercent = $telegramChannelsCount > 0
            ? round(($tgTargetCount / $telegramChannelsCount) * 100)
            : 0;

        // Visitors trend (last 12 months, monthly totals) starting from current month backwards
        $now = Carbon::now()->startOfMonth();
        $start = (clone $now)->subMonths(11);
        $end = (clone $now)->endOfMonth();
        $buckets = [];
        $orderMonths = [];
        for ($i = 0; $i < 12; $i++) {
            $dt = (clone $now)->subMonths($i);
            $key = $dt->format('Y-m');
            $orderMonths[] = $key;
            $buckets[$key] = [
                'label' => strtoupper($dt->format('M')).'/'.$dt->format('y'),
                'count' => 0,
            ];
        }

        // Cross‑DB monthly bucketing
        $driver = DB::getDriverName();
        $expr = match ($driver) {
            'pgsql' => "to_char(visited_at, 'YYYY-MM')",
            'sqlite' => "strftime('%Y-%m', visited_at)",
            default => "DATE_FORMAT(visited_at, '%Y-%m')",
        };

        $rows = Visit::query()
            ->when($sourceFilter !== 'all', function ($q) use ($sourceFilter) { $q->where('source', $sourceFilter); })
            ->whereBetween('visited_at', [$start, $end])
            ->selectRaw($expr." as ym, COUNT(*) as c")
            ->groupBy('ym')
            ->orderBy('ym')
            ->get();

        foreach ($rows as $row) {
            if (isset($buckets[$row->ym])) {
                $buckets[$row->ym]['count'] = (int) $row->c;
            }
        }

        // Order left-to-right: oldest -> newest
        $visitorsLabels = [];
        $visitorsSeries = [];
        foreach (array_reverse($orderMonths) as $k) {
            $visitorsLabels[] = $buckets[$k]['label'];
            $visitorsSeries[] = $buckets[$k]['count'];
        }

        // Mini charts: last 7 days trend for Users, Applications, Resumes
        $dayNow = Carbon::now()->startOfDay();
        $dayStart = (clone $dayNow)->subDays(6);
        $dayKeys = [];
        $dayLabels = [];
        for ($i = 0; $i < 7; $i++) {
            $d = (clone $dayStart)->copy()->addDays($i);
            $dayKeys[] = $d->format('Y-m-d');
            $dayLabels[] = strtoupper($d->format('D'));
        }

        $dayExpr = match ($driver) {
            'pgsql' => "to_char(created_at, 'YYYY-MM-DD')",
            'sqlite' => "strftime('%Y-%m-%d', created_at)",
            default => "DATE(created_at)",
        };

        $agg = function (string $modelClass) use ($dayStart, $dayNow, $dayExpr, $dayKeys) {
            $rows = $modelClass::query()
                ->whereBetween('created_at', [$dayStart, (clone $dayNow)->endOfDay()])
                ->selectRaw($dayExpr." as d, COUNT(*) as c")
                ->groupBy('d')
                ->orderBy('d')
                ->get();
            $map = array_fill_keys($dayKeys, 0);
            foreach ($rows as $r) { if (isset($map[$r->d])) { $map[$r->d] = (int) $r->c; } }
            return array_values($map);
        };

        $miniUsers = $agg(User::class);
        $miniApplications = $agg(Application::class);
        $miniResumes = $agg(Resume::class);
        $miniTotals = [
            'users' => array_sum($miniUsers),
            'applications' => array_sum($miniApplications),
            'resumes' => array_sum($miniResumes),
        ];

        // Analytics (Bounce/Page Views/Impressions/Conversions) based on actual visits
        // 1) Bounce rate chart -> last 24 hours, hourly buckets
        $hourNow = Carbon::now()->startOfHour();
        $hourStart = (clone $hourNow)->subHours(23); // inclusive range of 24 hours
        $hourKeys = [];
        $hourLabels = [];
        for ($i = 0; $i < 24; $i++) {
            $dt = (clone $hourStart)->copy()->addHours($i);
            $hourKeys[] = $dt->format('Y-m-d H:00');
            $hourLabels[] = $dt->format('H').':00';
        }
        $hourExpr = match ($driver) {
            'pgsql' => "to_char(date_trunc('hour', visited_at), 'YYYY-MM-DD HH24:00')",
            'sqlite' => "strftime('%Y-%m-%d %H:00', visited_at)",
            default => "DATE_FORMAT(visited_at, '%Y-%m-%d %H:00')",
        };
        $hourRows = Visit::query()
            ->when($sourceFilter !== 'all', function ($q) use ($sourceFilter) { $q->where('source', $sourceFilter); })
            ->whereBetween('visited_at', [$hourStart, (clone $hourNow)->endOfHour()])
            ->selectRaw($hourExpr." as h, COUNT(*) as c")
            ->groupBy('h')
            ->orderBy('h')
            ->get();
        $hourMap = array_fill_keys($hourKeys, 0);
        foreach ($hourRows as $r) { if (isset($hourMap[$r->h])) { $hourMap[$r->h] = (int) $r->c; } }
        $bounceSeries = array_values($hourMap);

        // 2) Page views -> last 30 days, daily buckets
        $pvNow = Carbon::now()->startOfDay();
        $pvStart = (clone $pvNow)->subDays(29);
        $pvKeys = [];
        $pvLabels = [];
        for ($i = 0; $i < 30; $i++) {
            $d = (clone $pvStart)->copy()->addDays($i);
            $pvKeys[] = $d->format('Y-m-d');
            $pvLabels[] = $d->format('M d');
        }
        $pvExpr = match ($driver) {
            'pgsql' => "to_char(visited_at, 'YYYY-MM-DD')",
            'sqlite' => "strftime('%Y-%m-%d', visited_at)",
            default => "DATE(visited_at)",
        };
        $pvRows = Visit::query()
            ->when($sourceFilter !== 'all', function ($q) use ($sourceFilter) { $q->where('source', $sourceFilter); })
            ->whereBetween('visited_at', [$pvStart, (clone $pvNow)->endOfDay()])
            ->selectRaw($pvExpr." as d, COUNT(*) as c")
            ->groupBy('d')
            ->orderBy('d')
            ->get();
        $pvMap = array_fill_keys($pvKeys, 0);
        foreach ($pvRows as $r) { if (isset($pvMap[$r->d])) { $pvMap[$r->d] = (int) $r->c; } }
        $pageViewsSeries = array_values($pvMap);

        // 3) Site impressions -> last 12 months monthly buckets (reuse visitors monthly buckets)
        $impressionsLabels = $visitorsLabels;
        $impressionsSeries = $visitorsSeries;

        // 4) Conversion Rate -> last 12 months monthly buckets as well (could be same underlying visits metric)
        $conversionsLabels = $visitorsLabels;
        $conversionsSeries = $visitorsSeries;

        // Vacancies analytics
        $sourceAggregations = [
            'total' => 'COUNT(*) as total',
            'telegram' => "SUM(CASE WHEN LOWER(COALESCE(source,'')) LIKE 'telegram%' THEN 1 ELSE 0 END) as telegram",
            'hh' => "SUM(CASE WHEN LOWER(COALESCE(source,'')) LIKE 'hh%' THEN 1 ELSE 0 END) as hh",
        ];

        $extractSourceValues = static function (array $maps, $row, string $bucket) {
            if (!isset($maps['all'][$bucket])) {
                return $maps;
            }
            $maps['all'][$bucket] = (int)($row->total ?? 0);
            $maps['telegram'][$bucket] = (int)($row->telegram ?? 0);
            $maps['hh'][$bucket] = (int)($row->hh ?? 0);
            return $maps;
        };

        // Hourly (last 24 hours)
        $vHourNow = Carbon::now()->startOfHour();
        $vHourStart = (clone $vHourNow)->subHours(23);
        $vhExpr = match ($driver) {
            'pgsql' => "to_char(date_trunc('hour', created_at), 'YYYY-MM-DD HH24:00')",
            'sqlite' => "strftime('%Y-%m-%d %H:00', created_at)",
            default => "DATE_FORMAT(created_at, '%Y-%m-%d %H:00')",
        };
        $vHourRows = DB::table('vacancies')
            ->whereBetween('created_at', [$vHourStart, (clone $vHourNow)->endOfHour()])
            ->selectRaw($vhExpr." as h")
            ->selectRaw($sourceAggregations['total'])
            ->selectRaw($sourceAggregations['telegram'])
            ->selectRaw($sourceAggregations['hh'])
            ->groupBy('h')
            ->orderBy('h')
            ->get();
        $vacHourlyMap = [
            'all' => array_fill_keys($hourKeys, 0),
            'telegram' => array_fill_keys($hourKeys, 0),
            'hh' => array_fill_keys($hourKeys, 0),
        ];
        foreach ($vHourRows as $r) {
            $vacHourlyMap = $extractSourceValues($vacHourlyMap, $r, $r->h);
        }
        $vacHourlySeries = array_values($vacHourlyMap['all']);
        $vacHourlySeriesTelegram = array_values($vacHourlyMap['telegram']);
        $vacHourlySeriesHh = array_values($vacHourlyMap['hh']);

        // Daily (last 30 days)
        $vdNow = Carbon::now()->startOfDay();
        $vdStart = (clone $vdNow)->subDays(29);
        $vdKeys = [];
        $vdLabels = [];
        for ($i = 0; $i < 30; $i++) {
            $d = (clone $vdStart)->copy()->addDays($i);
            $vdKeys[] = $d->format('Y-m-d');
            $vdLabels[] = $d->format('M d');
        }
        $vdExpr = match ($driver) {
            'pgsql' => "to_char(created_at, 'YYYY-MM-DD')",
            'sqlite' => "strftime('%Y-%m-%d', created_at)",
            default => "DATE(created_at)",
        };
        $vdRows = DB::table('vacancies')
            ->whereBetween('created_at', [$vdStart, (clone $vdNow)->endOfDay()])
            ->selectRaw($vdExpr." as d")
            ->selectRaw($sourceAggregations['total'])
            ->selectRaw($sourceAggregations['telegram'])
            ->selectRaw($sourceAggregations['hh'])
            ->groupBy('d')
            ->orderBy('d')
            ->get();
        $vacDailyMap = [
            'all' => array_fill_keys($vdKeys, 0),
            'telegram' => array_fill_keys($vdKeys, 0),
            'hh' => array_fill_keys($vdKeys, 0),
        ];
        foreach ($vdRows as $r) {
            $vacDailyMap = $extractSourceValues($vacDailyMap, $r, $r->d);
        }
        $vacDailySeries = array_values($vacDailyMap['all']);
        $vacDailySeriesTelegram = array_values($vacDailyMap['telegram']);
        $vacDailySeriesHh = array_values($vacDailyMap['hh']);

        // Weekly (last 12 weeks)
        $wNow = Carbon::now()->startOfWeek();
        $wStart = (clone $wNow)->subWeeks(11);
        $wKeys = [];
        $wLabels = [];
        for ($i = 0; $i < 12; $i++) {
            $w = (clone $wStart)->copy()->addWeeks($i);
            $wKeys[] = $w->format('o-W'); // ISO year-week
            $wLabels[] = 'W' . $w->format('W');
        }
        $wExpr = match ($driver) {
            'pgsql' => "to_char(date_trunc('week', created_at), 'IYYY-IW')",
            'sqlite' => "strftime('%Y-%W', created_at)",
            default => "DATE_FORMAT(created_at, '%x-%v')",
        };
        $wRows = DB::table('vacancies')
            ->whereBetween('created_at', [$wStart, (clone $wNow)->endOfWeek()])
            ->selectRaw($wExpr." as w")
            ->selectRaw($sourceAggregations['total'])
            ->selectRaw($sourceAggregations['telegram'])
            ->selectRaw($sourceAggregations['hh'])
            ->groupBy('w')
            ->orderBy('w')
            ->get();
        $vacWeeklyMap = [
            'all' => array_fill_keys($wKeys, 0),
            'telegram' => array_fill_keys($wKeys, 0),
            'hh' => array_fill_keys($wKeys, 0),
        ];
        foreach ($wRows as $r) {
            $vacWeeklyMap = $extractSourceValues($vacWeeklyMap, $r, $r->w);
        }
        $vacWeeklySeries = array_values($vacWeeklyMap['all']);
        $vacWeeklySeriesTelegram = array_values($vacWeeklyMap['telegram']);
        $vacWeeklySeriesHh = array_values($vacWeeklyMap['hh']);

        // Monthly (last 12 months)
        $vmNow = Carbon::now()->startOfMonth();
        $vmStart = (clone $vmNow)->subMonths(11);
        $vmKeys = [];
        $vmLabels = [];
        for ($i = 0; $i < 12; $i++) {
            $dt = (clone $vmNow)->subMonths($i);
            $key = $dt->format('Y-m');
            $vmKeys[] = $key;
            $vmLabels[] = strtoupper($dt->format('M')).'/'.$dt->format('y');
        }
        $vmExpr = match ($driver) {
            'pgsql' => "to_char(created_at, 'YYYY-MM')",
            'sqlite' => "strftime('%Y-%m', created_at)",
            default => "DATE_FORMAT(created_at, '%Y-%m')",
        };
        $vmRows = DB::table('vacancies')
            ->whereBetween('created_at', [$vmStart, (clone $vmNow)->endOfMonth()])
            ->selectRaw($vmExpr." as ym")
            ->selectRaw($sourceAggregations['total'])
            ->selectRaw($sourceAggregations['telegram'])
            ->selectRaw($sourceAggregations['hh'])
            ->groupBy('ym')
            ->orderBy('ym')
            ->get();
        $vacMonthlyMap = [
            'all' => array_fill_keys($vmKeys, 0),
            'telegram' => array_fill_keys($vmKeys, 0),
            'hh' => array_fill_keys($vmKeys, 0),
        ];
        foreach ($vmRows as $r) {
            $vacMonthlyMap = $extractSourceValues($vacMonthlyMap, $r, $r->ym);
        }
        // Reverse labels to oldest -> newest
        $vacMonthlyLabels = array_reverse($vmLabels);
        $vacMonthlySeries = array_values(array_reverse($vacMonthlyMap['all']));
        $vacMonthlySeriesTelegram = array_values(array_reverse($vacMonthlyMap['telegram']));
        $vacMonthlySeriesHh = array_values(array_reverse($vacMonthlyMap['hh']));

        $analyticsData = [
            'bounce' => [
                'labels' => $hourLabels,
                'series' => $bounceSeries,
            ],
            'pageViews' => [
                'labels' => $pvLabels,
                'series' => $pageViewsSeries,
            ],
            'impressions' => [
                'labels' => $impressionsLabels,
                'series' => $impressionsSeries,
            ],
            'conversions' => [
                'labels' => $conversionsLabels,
                'series' => $conversionsSeries,
            ],
            // Vacancies
            'vac_hourly' => [
                'labels' => $hourLabels,
                'series' => [
                    'Total' => $vacHourlySeries,
                    'Telegram' => $vacHourlySeriesTelegram,
                    'HH' => $vacHourlySeriesHh,
                ],
            ],
            'vac_daily'  => [
                'labels' => $vdLabels,
                'series' => [
                    'Total' => $vacDailySeries,
                    'Telegram' => $vacDailySeriesTelegram,
                    'HH' => $vacDailySeriesHh,
                ],
            ],
            'vac_weekly' => [
                'labels' => $wLabels,
                'series' => [
                    'Total' => $vacWeeklySeries,
                    'Telegram' => $vacWeeklySeriesTelegram,
                    'HH' => $vacWeeklySeriesHh,
                ],
            ],
            'vac_monthly'=> [
                'labels' => $vacMonthlyLabels,
                'series' => [
                    'Total' => $vacMonthlySeries,
                    'Telegram' => $vacMonthlySeriesTelegram,
                    'HH' => $vacMonthlySeriesHh,
                ],
            ],
        ];

        // Vacancies by category (top N) — Postgres-safe quoting and grouping by expression
        $categorizer = app(\Modules\TelegramChannel\Services\VacancyCategoryService::class);
        $catExpr = "COALESCE(NULLIF(category, ''), 'Other')";
        $vacancyCategoriesRaw = DB::table('vacancies')
            ->selectRaw($catExpr . ' as category, COUNT(*) as c')
            ->groupBy(DB::raw($catExpr))
            ->orderByDesc('c')
            ->limit(8)
            ->get();
        $vacancyCategories = $vacancyCategoriesRaw
            ->map(function ($row) use ($categorizer) {
                $canonical = $categorizer->categorize($row->category, null, '', $row->category);
                $slug = $categorizer->slugify($canonical);
                return (object) [
                    'category' => $canonical,
                    'slug' => $slug,
                    'count' => (int) $row->c,
                ];
            })
            ->groupBy('category')
            ->map(function ($group, $category) use ($categorizer) {
                $total = $group->sum('count');
                $slug = $categorizer->slugify($category);
                return (object) [
                    'category' => $category,
                    'slug' => $slug,
                    'c' => $total,
                ];
            })
            ->sortByDesc('c')
            ->values();
        $vacanciesTotal = DB::table('vacancies')->count();

        // Top visitors (all-time), only authenticated users (user_id not null)
        // Use LEFT JOIN + COALESCE id fallback so rows remain even if user record missing
        $topUsers = DB::table('visits')
            ->leftJoin('users', 'users.id', '=', 'visits.user_id')
            ->whereNotNull('visits.user_id')
            ->when($sourceFilter !== 'all', function ($q) use ($sourceFilter) { $q->where('visits.source', $sourceFilter); })
            ->selectRaw('COALESCE(users.id, visits.user_id) as id')
            ->selectRaw('users.first_name, users.last_name, users.email')
            ->selectRaw('COUNT(*) as visits_count')
            ->groupBy(DB::raw('COALESCE(users.id, visits.user_id)'), 'users.first_name', 'users.last_name', 'users.email')
            ->orderByDesc('visits_count')
            ->limit(10)
            ->get();

        return view('admin::Admin.Dashboard.dashboard', compact(
            'usersCount',
            'resumesCount',
            'applicationsCount',
            'telegramChannelsCount',
            'tgSourceCount',
            'tgTargetCount',
            'tgTargetPercent',
            'visitorsLabels',
            'visitorsSeries',
            'dayLabels',
            'miniUsers',
            'miniApplications',
            'miniResumes',
            'miniTotals',
            'analyticsData',
            'topUsers',
            'vacancyCategories',
            'vacanciesTotal'
        ));
    }

    /**
     * Full listing of users ordered by total visits.
     */
    public function topVisitors()
    {
        $sourceFilter = config('analytics.visits_source', 'api'); // 'api' | 'web' | 'all'
        $rows = DB::table('visits')
            ->leftJoin('users', 'users.id', '=', 'visits.user_id')
            ->whereNotNull('visits.user_id')
            ->when($sourceFilter !== 'all', function ($q) use ($sourceFilter) { $q->where('visits.source', $sourceFilter); })
            ->selectRaw('COALESCE(users.id, visits.user_id) as id')
            ->selectRaw('users.first_name, users.last_name, users.email')
            ->selectRaw('COUNT(*) as visits_count')
            ->groupBy(DB::raw('COALESCE(users.id, visits.user_id)'), 'users.first_name', 'users.last_name', 'users.email')
            ->orderByDesc('visits_count')
            ->paginate(50);

        return view('admin::Admin.Dashboard.top-visitors', [
            'rows' => $rows,
        ]);
    }

    /**
     * Full listing of vacancies grouped by category (all categories).
     */
    public function vacancyCategories(Request $request)
    {
        $filter = strtolower($request->query('filter', 'all'));
        if (!in_array($filter, ['all', 'telegram', 'hh'], true)) {
            $filter = 'all';
        }

        $categorizer = app(\Modules\TelegramChannel\Services\VacancyCategoryService::class);
        $catExpr = "COALESCE(NULLIF(category, ''), 'Other')";
        $rowsRaw = DB::table('vacancies')
            ->selectRaw($catExpr . ' as category, COUNT(*) as c')
            ->when($filter === 'telegram', function ($query) {
                $query->whereRaw('LOWER(source) LIKE ?', ['telegram%']);
            })
            ->when($filter === 'hh', function ($query) {
                $query->whereRaw('LOWER(source) LIKE ?', ['hh%']);
            })
            ->groupBy(DB::raw($catExpr))
            ->orderByDesc('c')
            ->get();
        $rows = $rowsRaw
            ->map(function ($row) use ($categorizer) {
                $canonical = $categorizer->categorize($row->category, null, '', $row->category);
                $slug = $categorizer->slugify($canonical);
                return (object) [
                    'category' => $canonical,
                    'slug' => $slug,
                    'count' => (int) $row->c,
                ];
            })
            ->groupBy('category')
            ->map(function ($group, $category) use ($categorizer) {
                $total = $group->sum('count');
                $slug = $categorizer->slugify($category);
                return (object) [
                    'category' => $category,
                    'slug' => $slug,
                    'c' => $total,
                ];
            })
            ->sortByDesc('c')
            ->values();

        $totalCount = DB::table('vacancies')
            ->when($filter === 'telegram', function ($query) {
                $query->whereRaw('LOWER(source) LIKE ?', ['telegram%']);
            })
            ->when($filter === 'hh', function ($query) {
                $query->whereRaw('LOWER(source) LIKE ?', ['hh%']);
            })
            ->count();

        return view('admin::Admin.Dashboard.categories', [
            'rows' => $rows,
            'totalCount' => $totalCount,
            'filter' => $filter,
        ]);
    }

    /**
     * List all vacancies (titles) for a given category.
     */
    public function vacanciesByCategory(Request $request, string $category)
    {
        $filter = strtolower($request->query('filter', 'all'));
        if (!in_array($filter, ['all', 'telegram', 'hh'], true)) {
            $filter = 'all';
        }

        $categorizer = app(\Modules\TelegramChannel\Services\VacancyCategoryService::class);
        $canonical = $categorizer->fromSlug($category) ?? $categorizer->categorize($category, null, '', $category);
        $slug = $categorizer->slugify($canonical);

        $query = Vacancy::query()->select(['id','title','category','created_at'])->orderByDesc('id');

        if (mb_strtolower($canonical) === 'other') {
            $query->where(function($q){
                $q->whereNull('category')->orWhere('category','')
                ->orWhere('category','Other');
            });
        } else {
            $query->where('category', $canonical);
        }

        $query->when($filter === 'telegram', function ($q) {
            $q->whereRaw('LOWER(source) LIKE ?', ['telegram%']);
        })->when($filter === 'hh', function ($q) {
            $q->whereRaw('LOWER(source) LIKE ?', ['hh%']);
        });

        // Paginate to avoid huge responses; can be adjusted as needed
        $vacancies = $query->paginate(50)->withQueryString();

        $titleCategory = $canonical;

        $count = $vacancies->total();

        return view('admin::Admin.Dashboard.category-vacancies', [
            'category' => $titleCategory,
            'categorySlug' => $slug,
            'vacancies' => $vacancies,
            'count' => $count,
            'filter' => $filter,
        ]);
    }

    /**
     * Toggle a vacancy between published and archived states.
     */
    public function vacancyUpdateStatus(Request $request, Vacancy $vacancy)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in([Vacancy::STATUS_PUBLISH, Vacancy::STATUS_ARCHIVE])],
        ]);

        $vacancy->update([
            'status' => $validated['status'],
        ]);

        $filter = strtolower($request->input('return_filter', $request->query('filter', 'all')));
        if (!in_array($filter, ['all', 'telegram', 'hh'], true)) {
            $filter = 'all';
        }
        $routeParams = ['id' => $vacancy->id];
        if ($filter !== 'all') {
            $routeParams['filter'] = $filter;
        }

        return redirect()
            ->route('admin.vacancies.show', $routeParams)
            ->with('status', 'Vacancy status updated.');
    }

    /**
     * Permanently delete a vacancy.
     */
    public function vacancyDestroy(Request $request, Vacancy $vacancy)
    {
        $vacancy->delete();

        $filter = strtolower($request->input('return_filter', $request->query('filter', 'all')));
        if (!in_array($filter, ['all', 'telegram', 'hh'], true)) {
            $filter = 'all';
        }
        $routeParams = $filter === 'all' ? [] : ['filter' => $filter];

        return redirect()
            ->route('admin.vacancies.categories', $routeParams)
            ->with('status', 'Vacancy deleted.');
    }

    /**
     * Show single vacancy details.
     */
    public function vacancyShow(Request $request, int $id)
    {
        $vacancy = Vacancy::query()->findOrFail($id);
        $categorizer = app(\Modules\TelegramChannel\Services\VacancyCategoryService::class);
        $categorySlug = $vacancy->category ? $categorizer->slugify($vacancy->category) : null;
        $filter = strtolower($request->query('filter', $request->input('return_filter', 'all')));
        if (!in_array($filter, ['all', 'telegram', 'hh'], true)) {
            $filter = 'all';
        }
        return view('admin::Admin.Dashboard.vacancy-show', compact('vacancy', 'categorySlug', 'filter'));
    }
}
