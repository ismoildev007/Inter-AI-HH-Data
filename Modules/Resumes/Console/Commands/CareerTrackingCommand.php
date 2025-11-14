<?php

namespace Modules\Resumes\Console\Commands;

use App\Models\Resume;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Resumes\Services\ResumePdfService;

class CareerTrackingCommand extends Command
{
    protected $signature = 'career-tracking';
    protected $description = 'Generate and save AI-based career tracking PDFs for all users';

    public function handle(ResumePdfService $pdfService)
    {
        Log::info("📄 Career Tracking Generation Command Started");
        $this->info('🚀 Career tracking generation started...');

        $resumes = Resume::whereNotNull('user_id')
            // ->whereHas('user', function ($query) {
            //     $query->whereIn('id', ['769', '868', '778']);
            // })
            ->get();

        $this->info('Total resumes found: ' . $resumes->count());

        foreach ($resumes as $resume) {
            $this->info("Processing resume ID: {$resume->id} (user_id: {$resume->user_id})");

            try {
                $pdfService->pdf($resume);
                $this->info("✅ Successfully generated PDF for Resume ID: {$resume->id}");
            } catch (\Throwable $e) {
                $this->error("❌ Failed for Resume ID: {$resume->id}");
                Log::error('CareerTrackingCommand error', [
                    'resume_id' => $resume->id,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        $this->info('✅ Career tracking process completed successfully!');
    }
}
