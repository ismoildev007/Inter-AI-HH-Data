<?php

// namespace App\Http\Controllers;

// use App\Models\Visit;
// use Carbon\Carbon;
// use Illuminate\Http\Request;
// use Illuminate\Support\Facades\DB;

// class VisitorsApiController extends Controller
// {
//     /**
//      * GET /api/v1/visitors
//      * Query params:
//      *  - period: monthly|daily (default: monthly)
//      *  - months: int (default: 12)
//      *  - days: int (default: 7)
//      *  - from, to: ISO date strings (optional)
//      */
//     public function index(Request $request)
//     {
//         $period = $request->query('period', 'monthly');

//         if ($period === 'daily') {
//             $days = max(1, (int) $request->query('days', 7));
//             $to = $request->query('to') ? Carbon::parse($request->query('to'))->endOfDay() : Carbon::now()->endOfDay();
//             $from = $request->query('from') ? Carbon::parse($request->query('from'))->startOfDay() : (clone $to)->startOfDay()->subDays($days - 1);

//             $driver = DB::getDriverName();
//             $expr = match ($driver) {
//                 'pgsql' => "to_char(visited_at, 'YYYY-MM-DD')",
//                 'sqlite' => "strftime('%Y-%m-%d', visited_at)",
//                 default => "DATE(visited_at)",
//             };

//             $rows = Visit::query()
//                 ->whereBetween('visited_at', [$from, $to])
//                 ->selectRaw($expr . ' as d, COUNT(*) as c')
//                 ->groupBy('d')
//                 ->orderBy('d')
//                 ->get();

//             // Build continuous range
//             $labels = [];
//             $series = [];
//             $cursor = (clone $from)->startOfDay();
//             $map = $rows->keyBy('d');
//             while ($cursor <= $to) {
//                 $key = $cursor->format('Y-m-d');
//                 $labels[] = $cursor->format('Y-m-d');
//                 $series[] = (int) ($map[$key]->c ?? 0);
//                 $cursor->addDay();
//             }

//             return response()->json([
//                 'status' => 'ok',
//                 'period' => 'daily',
//                 'from' => $from->toIso8601String(),
//                 'to' => $to->toIso8601String(),
//                 'labels' => $labels,
//                 'series' => $series,
//                 'total' => array_sum($series),
//             ]);
//         }

//         // monthly (default)
//         $months = max(1, (int) $request->query('months', 12));
//         $now = Carbon::now()->startOfMonth();
//         $from = $request->query('from') ? Carbon::parse($request->query('from'))->startOfMonth() : (clone $now)->subMonths($months - 1);
//         $to = $request->query('to') ? Carbon::parse($request->query('to'))->endOfMonth() : (clone $now)->endOfMonth();

//         $driver = DB::getDriverName();
//         $expr = match ($driver) {
//             'pgsql' => "to_char(visited_at, 'YYYY-MM')",
//             'sqlite' => "strftime('%Y-%m', visited_at)",
//             default => "DATE_FORMAT(visited_at, '%Y-%m')",
//         };

//         $rows = Visit::query()
//             ->whereBetween('visited_at', [$from, $to])
//             ->selectRaw($expr . ' as ym, COUNT(*) as c')
//             ->groupBy('ym')
//             ->orderBy('ym')
//             ->get()
//             ->keyBy('ym');

//         $labels = [];
//         $series = [];
//         $cursor = (clone $from)->startOfMonth();
//         while ($cursor <= $to) {
//             $key = $cursor->format('Y-m');
//             $labels[] = strtoupper($cursor->format('M')) . '/' . $cursor->format('y');
//             $series[] = (int) ($rows[$key]->c ?? 0);
//             $cursor->addMonth();
//         }

//         return response()->json([
//             'status' => 'ok',
//             'period' => 'monthly',
//             'from' => $from->toIso8601String(),
//             'to' => $to->toIso8601String(),
//             'labels' => $labels,
//             'series' => $series,
//             'total' => array_sum($series),
//         ]);
//     }
// }

