<?php

namespace Modules\JobSources\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Modules\JobSources\Services\LinkedinService;

class JobSourcesController extends Controller
{
    public function fetchIndeed(Request $request)
    {
        $keyword = $request->input('keyword', 'Laravel Developer');
        $location = $request->input('location', 'Remote');

        $rssUrl = "https://www.indeed.com/rss?q=" . urlencode($keyword) . "&l=" . urlencode($location);

        try {
            $response = Http::get($rssUrl);

            if ($response->failed()) {
                return response()->json([
                    'error' => 'Failed to fetch job listings',
                    'status' => $response->status(),
                    'body' => $response->body(),
                ], $response->status());
            }
            

            // ✅ XML ichidagi maxsus belgilarni tozalaymiz
            $cleanXml = str_replace(
                ['&bull;', '&nbsp;', '&ndash;', '&rsquo;'],
                ['•', ' ', '-', "'"],
                $response->body()
            );

            // HTML entity’larni ham decode qilamiz
            $cleanXml = html_entity_decode($cleanXml, ENT_QUOTES | ENT_XML1, 'UTF-8');

            // XML’ni parse qilamiz
            $xml = simplexml_load_string($cleanXml, 'SimpleXMLElement', LIBXML_NOCDATA);

            if (!$xml || !isset($xml->channel->item)) {
                return response()->json(['error' => 'No job items found'], 404);
            }

            $jobs = collect($xml->channel->item)->map(function ($item) {
                return [
                    'title' => (string) $item->title,
                    'link' => (string) $item->link,
                    'description' => trim(strip_tags((string) $item->description)),
                    'pubDate' => (string) $item->pubDate,
                ];
            });

            return response()->json([
                'status' => 'success',
                'count' => $jobs->count(),
                'data' => $jobs->take(10)->values(),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Failed to fetch job listings',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function linkedin(LinkedinService $service, Request $request)
    {
        $keyword = $request->get('keyword', 'Laravel Developer');
        
        return $service->fetchLinkedinJobs($keyword);
    }
}
