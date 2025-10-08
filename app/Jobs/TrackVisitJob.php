<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class TrackVisitJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    protected array $data;

    
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    
    public function handle(): void
    {
        DB::table('visits')->insert([
            'user_id'    => $this->data['user_id'],
            'session_id' => $this->data['session_id'],
            'ip_address' => $this->data['ip_address'],
            'user_agent' => $this->data['user_agent'],
            'source'     => $this->data['source'] ?? null,
            'visited_at' => $this->data['visited_at'],
            'created_at' => now(),
        ]);
    }
}
