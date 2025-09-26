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
            'miniTotals'
        ));
    }
}
