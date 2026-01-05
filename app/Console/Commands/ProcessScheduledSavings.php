<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Models\ScheduledSaving;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProcessScheduledSavings extends Command
{
    protected $signature = 'savings:process';
    protected $description = 'Process scheduled savings';

    public function handle()
    {
        $today = now()->toDateString();

        $scheduledSavings = ScheduledSaving::where('active',true)
            ->whereDate('next_run_date','<=',$today)
            ->get();

        foreach ($scheduledSavings as $schedule) {

            DB::transaction(function () use ($schedule) {

                $saving = $schedule->saving();

                // Dépôt
                $saving->increment('balance', $schedule->amount);

                Payment::create([
                    'saving_id' => $saving->id,
                    'user_id' => $schedule->user_id,
                    'amount' => $schedule->amount,
                    'method' => 'AUTO',
                    'type' => 'EPARGNE',
                    'status' => 'SUCCESS'
                ]);

                // Prochaine date
                $schedule->update([
                    'next_run_date' => match ($schedule->frequency) {
                    'daily' => now()->addDay(),
                        'weekly' => now()->addWeek(),
                        'monthly' => now()->addMonth(),
                    }
                ]);
            });
        }
    }
}

