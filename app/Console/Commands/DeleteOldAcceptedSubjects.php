<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DeleteOldAcceptedSubjects extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subjects:delete-old-accepted';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete records with status accepted from subject_student table after 3 months from updated_at date';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dateLimit = Carbon::now()->subMonths(3);

        $deleted = DB::table('subject_student')
            ->where('status', 'accepted')
            ->where('updated_at', '<', $dateLimit)
            ->delete();

        $this->info("Deleted {$deleted} records from subject_student table.");
    }
}