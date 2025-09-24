<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;

class ProfileController extends Controller
{
    public function index()
    {
        return view('admin::Admin.Profile.index');
    }
}

