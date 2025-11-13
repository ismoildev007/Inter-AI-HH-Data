<?php

namespace Modules\JobSources\Services;

use App\Models\Vacancy;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Spatie\Browsershot\Browsershot;

class LinkedinService
{
    // public function fetchLinkedinJobs(string $keyword = 'Laravel Developer', ?string $geoId = '91000000')
    // {
    //     try {
    //         $allJobs = [];
    //         $starts = range(0, 2000, 25); 
    //         $proxies = array_filter(array_map('trim', explode(',', env('LINKEDIN_PROXIES', ''))));
    //         if (empty($proxies)) {
    //             Log::warning('No proxies configured, requests will be direct.');
    //         }

    //         $headers = [
    //             'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36',
    //             'Accept-Language' => 'en-US,en;q=0.9',
    //             'Referer' => 'https://www.linkedin.com/jobs/',
    //         ];

    //         // 🔁 Parallel so‘rovlar
    //         $responses = Http::pool(function ($pool) use ($starts, $keyword, $geoId, $headers, $proxies) {
    //             $requests = [];
    //             foreach ($starts as $start) {
    //                 $url = "https://www.linkedin.com/jobs-guest/jobs/api/seeMoreJobPostings/search"
    //                     . "?keywords=" . urlencode($keyword)
    //                     . ($geoId ? "&geoId={$geoId}" : "")
    //                     . "&start={$start}";

    //                 // tanlangan tasodifiy proxy
    //                 $proxy = $proxies ? $proxies[array_rand($proxies)] : null;
    //                 $options = [];
    //                 if ($proxy) $options['proxy'] = $proxy;

    //                 // Retry + small timeout: Http::retry() bilan kombinatsiya qilinadi
    //                 $requests[$start] = $pool
    //                     ->withOptions(array_merge($options, ['timeout' => 30]))
    //                     ->withHeaders($headers)
    //                     ->get($url);
    //             }
    //             return $requests;
    //         });

    //         // 🧠 Har bir sahifani tahlil qilish
    //         foreach ($responses as $index => $response) {
    //             if (!$response || !$response->ok()) {
    //                 Log::warning("Page {$index} returned error");
    //                 continue;
    //             }

    //             $html = $response->body();
    //             if (empty(trim($html))) {
    //                 Log::warning("Page {$index} returned empty HTML");
    //                 continue;
    //             }

    //             $dom = new \DOMDocument();
    //             @$dom->loadHTML($html);
    //             $xpath = new \DOMXPath($dom);

    //             foreach ($xpath->query('//div[contains(@class, "base-card")]') as $jobNode) {
    //                 $title = trim($xpath->query('.//h3[contains(@class, "base-search-card__title")]', $jobNode)->item(0)?->textContent ?? '');
    //                 $company = trim($xpath->query('.//h4[contains(@class, "base-search-card__subtitle")]', $jobNode)->item(0)?->textContent ?? '');
    //                 $location = trim($xpath->query('.//span[contains(@class, "job-search-card__location")]', $jobNode)->item(0)?->textContent ?? '');
    //                 $time = trim($xpath->query('.//time', $jobNode)->item(0)?->textContent ?? '');
    //                 $linkNode = $xpath->query('.//a[contains(@class, "base-card__full-link")]', $jobNode)->item(0);
    //                 $link = $linkNode ? $linkNode->getAttribute('href') : null;

    //                 if ($title && $link) {
    //                     $allJobs[] = compact('title', 'company', 'location', 'time', 'link');
    //                 }
    //             }
    //         }

    //         $uniqueJobs = collect($allJobs)->unique('link')->values();

    //         return response()->json([
    //             'status' => 'success',
    //             'geoId'  => $geoId,
    //             'count'  => $uniqueJobs->count(),
    //             'data'   => $uniqueJobs->take(500)->values(),
    //         ]);
    //     } catch (\Throwable $e) {
    //         Log::error('LinkedIn parse error', ['message' => $e->getMessage()]);
    //         return response()->json([
    //             'error'   => 'LinkedIn parse failed',
    //             'message' => $e->getMessage(),
    //         ], 500);
    //     }
    // }

    // public function fetchLinkedinJobs(string $keyword = 'Laravel Developer')
    // {
    //     try {
    //         $geoId = '91000000'; // Europe only

    //         $headers = [
    //             'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
    //             'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
    //             'Accept-Language' => 'en-US,en;q=0.9',
    //             'Referer' => 'https://www.linkedin.com/jobs/',
    //             'Connection' => 'keep-alive',
    //             'Upgrade-Insecure-Requests' => '1',
    //         ];

    //         $allJobs = [];

    //         // ✅ Paginate through first 10 pages (0 - 250)
    //         for ($start = 0; $start <= 250; $start += 25) {
    //             $url = "https://www.linkedin.com/jobs-guest/jobs/api/seeMoreJobPostings/search"
    //                 . "?keywords=" . urlencode($keyword)
    //                 . "&geoId={$geoId}&start={$start}";

    //             $response = Http::withHeaders($headers)->get($url);
    //             if (!$response->ok()) {
    //                 Log::warning("LinkedIn Europe page {$start} returned status " . $response->status());
    //                 continue;
    //             }

    //             $html = $response->body();
    //             if (trim($html) === '') {
    //                 Log::warning("Empty HTML for Europe page {$start}");
    //                 continue;
    //             }

    //             $dom = new \DOMDocument();
    //             @$dom->loadHTML($html);
    //             $xpath = new \DOMXPath($dom);

    //             foreach ($xpath->query('//li[contains(@class, "base-card")]') as $jobNode) {
    //                 $title = trim($xpath->query('.//h3[contains(@class, "base-search-card__title")]', $jobNode)->item(0)?->textContent ?? '');
    //                 $company = trim($xpath->query('.//h4[contains(@class, "base-search-card__subtitle")]', $jobNode)->item(0)?->textContent ?? '');
    //                 $location = trim($xpath->query('.//span[contains(@class, "job-search-card__location")]', $jobNode)->item(0)?->textContent ?? '');
    //                 $time = trim($xpath->query('.//time', $jobNode)->item(0)?->textContent ?? '');
    //                 $linkNode = $xpath->query('.//a[contains(@class, "base-card__full-link")]', $jobNode)->item(0);
    //                 $link = $linkNode ? $linkNode->getAttribute('href') : null;

    //                 if ($title && $link) {
    //                     $allJobs[] = compact('title', 'company', 'location', 'time', 'link') + ['region' => 'Europe'];
    //                 }
    //             }

    //             // small delay to avoid bot detection
    //             usleep(random_int(400000, 800000)); // 0.4–0.8s
    //         }

    //         $uniqueJobs = collect($allJobs)->unique('link')->values();

    //         return response()->json([
    //             'status' => 'success',
    //             'region' => 'Europe',
    //             'count' => $uniqueJobs->count(),
    //             'data' => $uniqueJobs->take(500)->values(),
    //         ]);
    //     } catch (\Throwable $e) {
    //         Log::error('LinkedIn Europe fetch error', ['message' => $e->getMessage()]);
    //         return response()->json([
    //             'error' => 'LinkedIn Europe fetch failed',
    //             'message' => $e->getMessage(),
    //         ], 500);
    //     }
    // }

    public function fetchLinkedinJobs(string $keyword = 'Laravel Developer', ?string $geoId = '91000000')
    {
        try {
            Log::info("Fetching LinkedIn jobs for keyword='{$keyword}', geoId='{$geoId}'");
            $allJobs = [];

            // Paginate quickly across 3 pages (0–75 results)
            for ($start = 0; $start <= 500; $start += 25) {

                $url = "https://www.linkedin.com/jobs-guest/jobs/api/seeMoreJobPostings/search" .
                    "?keywords=" . urlencode($keyword) .
                    ($geoId ? "&geoId={$geoId}" : "") .
                    "&start={$start}";

                $response = Http::withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                    'Accept-Language' => 'en-US,en;q=0.9',
                    'Cookie' => 'li_at=' . env('LINKEDIN_LI_AT') . ';',
                ])->get($url);


                if (!$response->ok()) continue;

                $html = $response->body();


                if (empty($html)) continue;

                $dom = new \DOMDocument();
                @$dom->loadHTML($html);
                $xpath = new \DOMXPath($dom);

                foreach ($xpath->query('//div[contains(@class, "base-card")]') as $jobNode) {
                    $title = trim($xpath->query('.//h3[contains(@class, "base-search-card__title")]', $jobNode)->item(0)?->textContent ?? '');
                    $company = trim($xpath->query('.//h4[contains(@class, "base-search-card__subtitle")]', $jobNode)->item(0)?->textContent ?? '');
                    $location = trim($xpath->query('.//span[contains(@class, "job-search-card__location")]', $jobNode)->item(0)?->textContent ?? '');
                    $time = trim($xpath->query('.//time', $jobNode)->item(0)?->textContent ?? '');
                    $linkNode = $xpath->query('.//a[contains(@class, "base-card__full-link")]', $jobNode)->item(0);
                    $link = $linkNode ? $linkNode->getAttribute('href') : null;

                    if ($title && $link) {
                        $allJobs[] = compact('title', 'company', 'location', 'time', 'link');
                    }
                }

                usleep(random_int(200000, 500000));
            }

            $uniqueJobs = collect($allJobs)->unique('link')->values();
            Log::info("Fetched " . $uniqueJobs->count() . " unique jobs from LinkedIn.");

            // return response()->json([
            //     'status' => 'success',
            //     'geoId'  => $geoId,
            //     'count'  => $uniqueJobs->count(),
            //     'data'   => $uniqueJobs->take(300)->values(),
            // ]);
            return [
                'status' => 'success',
                'count'  => $uniqueJobs->count(),
                'geoId'  => $geoId,
                'data'   => $uniqueJobs->take(300)->values()->all(),
            ];
        } catch (\Throwable $e) {
            Log::error('LinkedIn parse error', ['message' => $e->getMessage()]);
            // return response()->json([
            //     'error'   => 'LinkedIn parse failed',
            //     'message' => $e->getMessage(),
            // ], 500);
            return [
                'status'  => 'error',
                'message' => $e->getMessage(),
                'data'    => [],
            ];
        }
    }

    public function extractExternalId(string $url): ?string
    {
        if (preg_match('/\/view\/(\d+)/', $url, $m)) return $m[1];
        if (preg_match('/-(\d+)(?:\?|$)/', $url, $m)) return $m[1];
        return null;
    }


    public function saveToDatabase(array $jobs): array
    {
        $saved = 0;
        $updated = 0;
        Log::info("Saving " . count($jobs) . " LinkedIn jobs to database.");
        Log::info("Sample job: " . json_encode($jobs[0] ?? []));
        foreach ($jobs as $job) {

            $externalId = $this->extractExternalId($job['link'] ?? '');
            if (!$externalId) continue;

            $vac = Vacancy::where('source', 'linkedin')
                ->where('external_id', $externalId)
                ->first();

            if (!$vac) {
                Vacancy::create([
                    'title'       => $job['title'] ?? '',
                    'description' => implode("\n", array_filter([
                        $job['company'] ?? '',
                        $job['location'] ?? '',
                        $job['time'] ?? ''
                    ])),
                    'source'      => 'linkedin',
                    'company'     => $job['company'] ?? '',
                    'external_id' => $externalId,
                    'apply_url'        => $job['link'],
                    'status'      => 'publish',
                    'raw_data'    => json_encode($job),
                ]);
                $saved++;
            } else {
                $vac->update([
                    'title'       => $job['title'] ?? $vac->title,
                    'raw_data'    => json_encode($job),
                    'description' => implode("\n", array_filter([
                        $job['company'] ?? '',
                        $job['location'] ?? '',
                    ])),
                ]);
                $updated++;
            }
        }

        return compact('saved', 'updated');
    }





    // public function fetchLinkedinJobs(string $keyword = 'Laravel Developer')
    // {
    //     try {
    //         $csrf = env('LINKEDIN_CSRF_TOKEN');
    //         $cookie = 'li_at=' . env('LINKEDIN_LI_AT') . ';';

    //         $headers = [
    //             'authority' => 'www.linkedin.com',
    //             'accept' => 'application/json',
    //             'accept-language' => 'en-US,en;q=0.9',
    //             'csrf-token' => $csrf,
    //             'cookie' => $cookie,
    //             'content-type' => 'application/json',
    //             'x-restli-protocol-version' => '2.0.0',
    //             'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
    //             'referer' => 'https://www.linkedin.com/jobs/search/',
    //         ];

    //         $jobs = collect();
    //         $count = 25;

    //         for ($start = 0; $start < 200; $start += $count) {
    //             $payload = [
    //                 'variables' => [
    //                     'query' => $keyword,
    //                     'count' => $count,
    //                     'start' => $start,
    //                     'origin' => 'JOB_SEARCH_PAGE_SEARCH_BUTTON',
    //                 ],
    //                 'queryId' => 'voyagerJobsDashJobSearchJobs',
    //             ];

    //             $response = Http::withHeaders($headers)
    //                 ->post('https://www.linkedin.com/voyager/api/graphql?includeWebMetadata=true', $payload);

    //             if (!$response->ok()) {
    //                 Log::warning('LinkedIn voyager API request failed', [
    //                     'status' => $response->status(),
    //                     'body' => $response->body()
    //                 ]);
    //                 return response()->json([
    //                     'error' => 'LinkedIn voyager API request failed',
    //                     'status' => $response->status(),
    //                     'body' => $response->body()
    //                 ], 500);
    //             }

    //             $data = $response->json();
    //             $elements = data_get($data, 'data.jobsDashJobSearchByQueryResult.elements', []);
    //             if (empty($elements)) break;

    //             foreach ($elements as $job) {
    //                 $jobs->push([
    //                     'title' => data_get($job, 'title.text', 'N/A'),
    //                     'company' => data_get($job, 'companyDetails.company.name', 'N/A'),
    //                     'location' => data_get($job, 'formattedLocation', 'N/A'),
    //                     'listedAt' => data_get($job, 'listedAt', ''),
    //                     'link' => "https://www.linkedin.com/jobs/view/" . data_get($job, 'trackingUrn', ''),
    //                 ]);
    //             }

    //             usleep(random_int(500000, 1000000)); // anti-throttle
    //         }

    //         return response()->json([
    //             'status' => 'success',
    //             'count' => $jobs->count(),
    //             'data' => $jobs->values(),
    //         ]);
    //     } catch (\Throwable $e) {
    //         Log::error('LinkedIn voyager GraphQL fetch error', [
    //             'message' => $e->getMessage(),
    //         ]);
    //         return response()->json([
    //             'error' => 'LinkedIn voyager GraphQL fetch failed',
    //             'message' => $e->getMessage(),
    //         ], 500);
    //     }
    // }
}
