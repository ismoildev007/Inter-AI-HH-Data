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

        // Visitors trend (last 12 months, monthly totals)
        $start = Carbon::now()->startOfMonth()->subMonths(11);
        $buckets = [];
        for ($i = 0; $i < 12; $i++) {
            $dt = (clone $start)->copy()->addMonths($i);
            $buckets[$dt->format('Y-m')] = [
                'label' => strtoupper($dt->format('M')).'/'.$dt->format('y'),
                'count' => 0,
            ];
        }

        // Cross‑DB monthly bucketing
        $driver = DB::getDriverName();
        $expr = match ($driver) {
            'pgsql' => "to_char(visited_at, 'YYYY-MM')",
            'sqlite' => "strftime('%Y-%m', visited_at)",
            default => "DATE_FORMAT(visited_at, '%Y-%m')", // MySQL/MariaDB
        };

        $rows = Visit::query()
            ->where('visited_at', '>=', $start)
            ->selectRaw($expr." as ym, COUNT(*) as c")
            ->groupBy('ym')
            ->orderBy('ym')
            ->get();

        foreach ($rows as $row) {
            if (isset($buckets[$row->ym])) {
                $buckets[$row->ym]['count'] = (int) $row->c;
            }
        }

        $visitorsLabels = array_column($buckets, 'label');
        $visitorsSeries = array_column($buckets, 'count');

        return view('admin::Admin.Dashboard.dashboard', compact(
            'usersCount',
            'resumesCount',
            'applicationsCount',
            'telegramChannelsCount',
            'tgSourceCount',
            'tgTargetCount',
            'tgTargetPercent',
            'visitorsLabels',
            'visitorsSeries'
        ));
    }
}
