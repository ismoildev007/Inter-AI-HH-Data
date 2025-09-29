<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Resume;
use App\Models\User;
use App\Models\TelegramChannel;
use App\Models\Visit;
use Illuminate\Support\Facades\DB;
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
        ];

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
            'topUsers'
        ));
    }

    /**
     * Full listing of users ordered by total visits.
     */
    public function topVisitors()
    {
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
}
