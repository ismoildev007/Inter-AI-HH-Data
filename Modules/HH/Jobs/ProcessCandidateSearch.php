<?php

namespace Modules\HH\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Modules\HH\Models\Candidate;
use Modules\HH\Models\SearchRequest;
use Modules\HH\Models\SearchResult;

class ProcessCandidateSearch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $searchRequest;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(SearchRequest $searchRequest)
    {
        $this->searchRequest = $searchRequest;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Mark as processing
        $this->searchRequest->update(['status' => 'processing']);

        try {
            // 1. Basic filtering based on 'filters'
            $query = Candidate::query();
            $filters = $this->searchRequest->filters;

            if (isset($filters['experience_min'])) {
                $query->where('experience', '>=', $filters['experience_min']);
            }
            if (isset($filters['experience_max'])) {
                $query->where('experience', '<=', $filters['experience_max']);
            }
            if (isset($filters['specialization'])) {
                $query->where('specialization', 'like', '%' . $filters['specialization'] . '%');
            }
            // Add more filters as needed...

            $candidates = $query->limit(500)->get(); // Limit to a reasonable number for AI processing

            // 2. AI-based analysis on 'custom_requirements'
            // This is a placeholder for a real AI/ML service call.
            // For now, we'll simulate it with a random match percentage.
            foreach ($candidates as $candidate) {
                // In a real application, you would send $candidate->about, $candidate->skills,
                // and $this->searchRequest->custom_requirements to an AI service.
                $matchPercentage = $this->calculateMatch($candidate, $this->searchRequest->custom_requirements);

                if ($matchPercentage > 50) { // Only save if there's a decent match
                    SearchResult::create([
                        'search_request_id' => $this->searchRequest->id,
                        'candidate_id' => $candidate->id,
                        'match_percentage' => $matchPercentage,
                    ]);
                }
            }

            // Mark as completed
            $this->searchRequest->update(['status' => 'completed']);

        } catch (\Exception $e) {
            // Mark as failed
            $this->searchRequest->update(['status' => 'failed']);
            // Log the error
            \Log::error('Candidate search failed for request ID ' . $this->searchRequest->id, ['error' => $e->getMessage()]);
        }
    }

    /**
     * Simulate AI analysis to calculate match percentage.
     */
    private function calculateMatch(Candidate $candidate, string $requirements): int
    {
        // Simple keyword matching simulation
        $score = 0;
        $requirementWords = explode(' ', strtolower($requirements));
        $candidateText = strtolower($candidate->about . ' ' . json_encode($candidate->skills));

        foreach ($requirementWords as $word) {
            if (strlen($word) > 3 && str_contains($candidateText, $word)) {
                $score++;
            }
        }

        // Normalize score to a percentage (this is a very rough simulation)
        $maxPossibleScore = count($requirementWords) / 2; // Heuristic
        $percentage = ($maxPossibleScore > 0) ? ($score / $maxPossibleScore) * 100 : 0;

        return min((int)$percentage, 100); // Cap at 100
    }
}
