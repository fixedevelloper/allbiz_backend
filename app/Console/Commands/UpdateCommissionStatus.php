<?php

namespace App\Console\Commands;

use App\Models\Investment;
use App\Models\Transaction;
use App\Services\ReferralService;
use Illuminate\Console\Command;

class UpdateCommissionStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-commission-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $investments = Investment::query()
            ->where('status', 'pending')
            ->with(['user.transactions' => function ($query) {
                $query->where('type', 'investment');
            }])
            ->get();

        foreach ($investments as $investment) {
            $transaction = $investment->user->transactions->first();

            if ($transaction && $transaction->status === 'success') {
                $investment->update(['status' => 'active']);
                (new ReferralService())->handleReferral($investment);

                $this->info("Commission traitée pour Investment ID: {$investment->id}");
            }
        }

        $this->info("Traitement terminé.");
    }

}
