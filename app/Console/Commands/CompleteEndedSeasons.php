<?php

namespace App\Console\Commands;

use App\Models\LedgerSeason;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CompleteEndedSeasons extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:complete-ended-seasons';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark all ended active seasons as completed if end_date has passed';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $count = LedgerSeason::where('status', 'active')
            ->whereDate('end_date', '<', Carbon::now())
            ->update(['status' => 'completed']);

        if ($count > 0) {
            $this->info("{$count} season(s) marked as completed.");
        } else {
            $this->info("No ended active seasons found.");
        }

        return Command::SUCCESS;


    }
 }

