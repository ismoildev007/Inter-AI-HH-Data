<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    /**
     * Admin dashboard.
     */
    public function index()
    {
        return view('admin::Admin.Dashboard.dashboard');
    }
}
