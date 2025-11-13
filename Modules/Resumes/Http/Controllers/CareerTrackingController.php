<?php

namespace Modules\Resumes\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class CareerTrackingController extends Controller
{
    public function showResume()
    {
        $user = auth()->user();
        $resume = $user->resumes()->first();

        $careerTrackingInfo = DB::table('career_tracking_pdfs')
            ->where('resume_id', $resume->id)
            ->first();

        return response()->json([
            'success' => true,
            'career_tracking_info' => $careerTrackingInfo
        ]);
    }
}
