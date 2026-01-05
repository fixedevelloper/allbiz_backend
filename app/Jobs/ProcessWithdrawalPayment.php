<?php

namespace App\Jobs;

use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ProcessWithdrawalPayment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 60;

    protected Transaction $withdrawal;

    public function __construct(Transaction $withdrawal)
    {
        $this->withdrawal = $withdrawal;
    }

    public function handle(): void
    {
        if ($this->withdrawal->status !== 'pending') {
            return;
        }

        DB::beginTransaction();

        try {
            $meta = $this->withdrawal->meta;

            /**
             * 🔁 ICI : intégrer API MTN / Orange
             * (Simulation pour l’instant)
             */
            $paymentSuccess = $this->simulatePayment($meta);

            if ($paymentSuccess) {
                $this->withdrawal->update([
                    'status' => 'success',
                ]);
            } else {
                $this->withdrawal->update([
                    'status' => 'failed',
                ]);
            }

            DB::commit();

        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Simulation paiement Mobile Money
     */
    private function simulatePayment(array $meta): bool
    {
        // 90% succès
        return rand(1, 100) <= 90;
    }
}
