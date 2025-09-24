<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Resume;
use App\Models\User;
use App\Models\TelegramChannel;

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

        return view('admin::Admin.Dashboard.dashboard', compact(
            'usersCount',
            'resumesCount',
            'applicationsCount',
            'telegramChannelsCount',
            'tgSourceCount',
            'tgTargetCount',
            'tgTargetPercent'
        ));
    }
}
