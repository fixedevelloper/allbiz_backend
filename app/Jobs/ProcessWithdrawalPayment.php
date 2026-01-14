<?php

namespace App\Jobs;

use App\Models\Transaction;
use App\Models\WithdrawAccount;
use App\Services\FedaPayService;
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

        try {

            DB::transaction(function () {

                $result = $this->simulatePayment();

                if ($result === false) {
                    $this->withdrawal->update([
                        'status' => 'failed',
                    ]);
                    return;
                }

                $this->withdrawal->update([
                    'status' => 'processing',
                ]);
            });

            // 💰 remboursement APRES commit
            if ($this->withdrawal->status === 'failed') {
                $this->refundUserOnce();
            }

        } catch (\Throwable $e) {

            $this->refundUserOnce();

            throw $e;
        }
    }




    /**
     * Simulation paiement Mobile Money
     * @throws \Exception
     */
    private function simulatePayment(): bool
    {
        $fedapayService = new FedaPayService();
        $meta = (array) $this->withdrawal->meta;

        $accountWithdraw = WithdrawAccount::findOrFail(
            $meta['account_withdraw_id']
        );

        $payout = $fedapayService->payout([
            'amount' => $meta['net_amount'],
            'description' => $meta['description'] ?? 'Décaissement',
            'phone_number' => $accountWithdraw->phone,
            'name' => $accountWithdraw->name,
            'country' => strtolower($accountWithdraw->operator->country->iso),
            'reference' => $this->withdrawal->reference,
        ]);

        if (!$payout->success) {

            $this->withdrawal->meta = array_merge($meta, [
                'payout_error' => $payout->message,
            ]);
           // $this->withdrawal->user->increment('balance',$this->withdrawal->amount);
            return false;
        }

        $this->withdrawal->meta = array_merge($meta, [
            'payout_id' => $payout->id,
            'payout_status' => $payout->status ?? 'pending',
        ]);

        $start = $fedapayService->startPayout($payout->id);

        if (!$start->success) {
            $this->withdrawal->meta = array_merge(
                (array) $this->withdrawal->meta,
                ['payout_error' => 'Payout créé mais non lancé']
            );
           // $this->withdrawal->user->increment('balance',$this->withdrawal->amount);
            return false;
        }

        return true;
    }

    private function refundUserOnce(): void
    {
        if (!empty($this->withdrawal->meta['refunded'])) {
            return;
        }

        DB::transaction(function () {
            $this->withdrawal->user()
                ->lockForUpdate()
                ->increment('balance', $this->withdrawal->amount);

            $this->withdrawal->update([
                'meta' => array_merge(
                    (array) $this->withdrawal->meta,
                    ['refunded' => true]
                ),
            ]);
        });
    }



}
