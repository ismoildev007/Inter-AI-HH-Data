<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\JobSources\Services\LinkedinService;

class FetchLinkedinJobs extends Command
{
    protected $signature = 'linkedin:fetch {keyword=Laravel Developer} {geoId=91000000}';
    protected $description = 'Fetch LinkedIn jobs every minute';

    public function handle(LinkedinService $linkedinService)
    {
        $keyword = $this->argument('keyword');
        $geoId   = $this->argument('geoId');

        $this->info("Fetching LinkedIn jobs: {$keyword}");
        Log::info("Starting LinkedIn job fetch for keyword='{$keyword}', geoId='{$geoId}'");

        $result = $linkedinService->fetchLinkedinJobs($keyword, $geoId);

        $this->info("Done");
        Log::info('Done');
        return 0;
    }
}
