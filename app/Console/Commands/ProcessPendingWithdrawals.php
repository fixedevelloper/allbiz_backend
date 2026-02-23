<?php

namespace App\Console\Commands;

use App\Models\WithdrawAccount;
use App\Services\FedaPayService;
use Illuminate\Console\Command;
use App\Models\Transaction;
use App\Mail\WithdrawalNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class ProcessPendingWithdrawals extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'withdrawals:processing';

    /**
     * The console command description.
     */
    protected $description = 'Envoyer un mail pour toutes les transactions en processing et de type withdrawal';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Transaction::where('status', 'processing')
            ->where('type', 'withdrawal')
            ->chunk(50, function ($transactions) {

                foreach ($transactions as $tx) {

                    try {

                        DB::transaction(function () use ($tx) {

                            $result = $this->simulatePayment($tx);

                            if (!$result) {
                                $tx->update(['status' => 'failed']);
                                return;
                            }

                            $tx->update(['status' => 'success']);
                        });

                        $tx->refresh();

                        if ($tx->status === 'failed') {
                            $this->refundUserOnce($tx);
                        }

                        if ($tx->status === 'success') {
                            Mail::to(config('mail.from.admin_email'))
                                ->queue(new WithdrawalNotification($tx));
                        }

                    } catch (\Throwable $e) {

                        $this->refundUserOnce($tx);

                        $this->error("Erreur TX {$tx->id}: " . $e->getMessage());
                    }
                }
            });

        return 0;
    }
    /**
     * Simulation paiement Mobile Money
     * @throws \Exception
     */
    private function simulatePayment($withdrawal): bool
    {
        $fedapayService = new FedaPayService();
        $meta = (array) $withdrawal->meta;

        $accountWithdraw = WithdrawAccount::findOrFail(
            $meta['account_withdraw_id']
        );

        $payout = $fedapayService->payout([
            'amount' => $meta['net_amount'],
            'description' => $meta['description'] ?? 'Décaissement',
            'phone_number' => $accountWithdraw->phone,
            'name' => $accountWithdraw->name,
            'country' => strtolower($accountWithdraw->operator->country->iso),
            'reference' => $withdrawal->reference,
        ]);

        if (!$payout->success) {

            $withdrawal->meta = array_merge($meta, [
                'payout_error' => $payout->message,
            ]);
            // $this->withdrawal->user->increment('balance',$this->withdrawal->amount);
            return false;
        }

        $withdrawal->meta = array_merge($meta, [
            'payout_id' => $payout->id,
            'payout_status' => $payout->status ?? 'pending',
        ]);

        $start = $fedapayService->startPayout($payout->id);

        if (!$start->success) {
            $withdrawal->meta = array_merge(
                (array) $withdrawal->meta,
                ['payout_error' => 'Payout créé mais non lancé']
            );
            // $this->withdrawal->user->increment('balance',$this->withdrawal->amount);
            return false;
        }

        return true;
    }

    private function refundUserOnce($withdrawal): void
    {
        if (!empty($withdrawal->meta['refunded'])) {
            return;
        }

        DB::transaction(function () use ($withdrawal) {
            $withdrawal->user()
                ->lockForUpdate()
                ->increment('balance', $withdrawal->amount);

            $withdrawal->update([
                'meta' => array_merge(
                    (array) $withdrawal->meta,
                    ['refunded' => true]
                ),
            ]);
        });
    }

}
