<?php 

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Spatie\Async\Pool;

class TestCommand extends Command
{
    protected $signature = 'test:pool';
    protected $description = 'Test Spatie Async Pool in CLI mode';

    public function handle()
    {
        $start = microtime(true);
        $this->info('🧵 Starting Spatie Pool test...');

        $pool = Pool::create();

        $pool[] = async(function () {
            sleep(3);
            return 'Task 1 done';
        })->then(fn($r) => $this->info('✅ '.$r));

        $pool[] = async(function () {
            sleep(3);
            return 'Task 2 done';
        })->then(fn($r) => $this->info('✅ '.$r));

        $pool[] = async(function () {
            $result = DB::table('vacancies')->count();
            return 'result' . $result;
        });


        await($pool);

        $this->info('🏁 Finished in ' . round(microtime(true) - $start, 2) . 's');
    }
}
