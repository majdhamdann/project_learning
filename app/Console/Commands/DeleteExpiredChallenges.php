<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Challenge;
use Carbon\Carbon;

class DeleteExpiredChallenges extends Command
{
    protected $signature = 'challenges:delete-expired';
    protected $description = 'Delete challenges that have expired based on start_time and duration';

    public function handle()
{
    $now = now();

    
    $challenges = Challenge::whereRaw('DATE_ADD(start_time, INTERVAL duration_minutes MINUTE) <= ?', [$now])
        ->get();

    foreach ($challenges as $challenge) {
        $challenge->delete();
    }

    $this->info('Expired challenges deleted successfully.');
}

}
