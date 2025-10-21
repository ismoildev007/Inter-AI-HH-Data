<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class DeactivateExpiredTrialsCommand extends Command
{
    protected $signature = 'users:trials:deactivate';

    protected $description = 'Disable trial holati muddati tugagan foydalanuvchilar uchun';

    public function handle(): int
    {
        $now = now();

        $affected = User::query()
            ->where('is_trial_active', true)
            ->whereNotNull('trial_end_date')
            ->where('trial_end_date', '<=', $now)
            ->update([
                'is_trial_active' => false,
            ]);

        $this->info("Trial muddati tugagan foydalanuvchilar soni: {$affected}");

        return Command::SUCCESS;
    }
}

