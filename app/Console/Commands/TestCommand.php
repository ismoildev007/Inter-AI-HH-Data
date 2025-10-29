<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Async\Pool;

class TestCommand extends Command
{
    protected $signature = 'test:pool';
    protected $description = 'Parallel DB queries using Spatie Async + PDO';

    public function handle()
    {
        $this->info('🚀 Parallel PDO test started');
        $start = microtime(true);

        $pool = Pool::create()->concurrency(5);

        $dsn = 'pgsql:host=134.209.240.131;port=5432;dbname=jobapp;';
        $user = 'jobuser';
        $pass = 'JobAppPass123!';

        // 5 parallel so‘rov
        foreach (range(1, 5) as $i) {
            $pool[] = async(function () use ($dsn, $user, $pass, $i) {
                $pdo = new \PDO($dsn, $user, $pass, [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                ]);

                $stmt = $pdo->query('SELECT COUNT(*) AS count FROM vacancies');
                $row = $stmt->fetch();

                return "Task {$i} → Vacancy count: {$row['count']}";
            });
        }

        $results = await($pool);

        foreach ($results as $r) {
            $this->line("✅ {$r}");
        }

        $this->info('🏁 Done in ' . round(microtime(true) - $start, 2) . 's');
    }
}
