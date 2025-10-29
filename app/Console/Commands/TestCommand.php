<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Async\Pool;

class TestCommand extends Command
{
    protected $signature = 'test:pool';
    protected $description = 'Test Spatie Async Pool in CLI mode with DB connection';

    public function handle()
    {
        $start = microtime(true);
        $this->info('🧵 Starting Spatie Pool test...');

        $pool = Pool::create();

        // Task 1
        $pool[] = async(function () {
            sleep(3);
            return 'Task 1 done';
        })->then(fn($r) => print("✅ {$r}\n"));

        // Task 2
        $pool[] = async(function () {
            sleep(3);
            return 'Task 2 done';
        })->then(fn($r) => print("✅ {$r}\n"));

        // Task 3: direct PostgreSQL query via PDO (no Laravel facades)
        $pool[] = async(function () {
            $dsn = 'pgsql:host=134.209.240.131;port=5432;dbname=jobapp;';
            $user = 'jobuser';
            $pass = 'JobAppPass123!';

            $pdo = new \PDO($dsn, $user, $pass, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            ]);

            $stmt = $pdo->query('SELECT COUNT(*) AS count FROM vacancies');
            $row = $stmt->fetch();
            return 'Vacancies in DB: ' . $row['count'];
        })->then(fn($r) => print("✅ {$r}\n"));

        await($pool);

        $this->info('🏁 Finished in ' . round(microtime(true) - $start, 2) . 's');
    }
}
