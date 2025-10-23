<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;

class BillingDashboardController extends Controller
{
    public function index()
    {
        return view('admin::Admin.Dashboard.billing');
    }
}
