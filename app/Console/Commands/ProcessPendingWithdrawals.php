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

        try {
            $payout = $fedapayService->payout([
                'amount' => $meta['net_amount'],
                'description' => $meta['description'] ?? 'Décaissement',
                'phone_number' => $accountWithdraw->phone,
                'name' => $accountWithdraw->name,
                'country' => strtolower($accountWithdraw->operator->country->iso),
                'reference' => $withdrawal->reference,
            ]);

            $transactionData = $payout->{'v1/payout'} ?? null;

            logger($payout);
            // ❗ Vérification réponse
            if (!$transactionData) {
                $this->setError($withdrawal, $meta, 'Réponse FedaPay invalide');
                return false;
            }

            // ❗ Vérification status
            if ($transactionData->status === 'failed') {
                $this->setError(
                    $withdrawal,
                    $meta,
                    $transactionData->last_error_code ?? 'Paiement échoué'
                );
                return false;
            }

            // Sauvegarde
            $withdrawal->meta = array_merge($meta, [
                'payout_id' => $transactionData->id,
                'payout_status' => $transactionData->status,
            ]);

            // 🚀 Lancer le payout
            $start = $fedapayService->startPayout($transactionData->id);

// ⚠️ ici c’est un tableau
            $startData = $start[0] ?? null;

            if (!$startData) {
                $this->setError($withdrawal, $withdrawal->meta, 'Payout non lancé');
                return false;
            }

// Vérifier le status réel
            if ($startData['status'] === 'failed') {
                $this->setError(
                    $withdrawal,
                    $withdrawal->meta,
                    $startData['last_error_code'] ?? 'Echec du payout'
                );
                return false;
            }

            // Mise à jour status après start
            $withdrawal->meta = array_merge((array)$withdrawal->meta, [
                'payout_status' => $startData->status,
            ]);

            $withdrawal->save();

            return true;

        } catch (\Throwable $e) {

            $this->setError($withdrawal, $meta, $e->getMessage());

            return false;
        }
    }

    private function setError($withdrawal, $meta, $message)
    {
        $withdrawal->meta = array_merge($meta, [
            'payout_error' => $message,
        ]);

        $withdrawal->status = 'failed';
        $withdrawal->save();
    }
/*    private function simulatePayment($withdrawal): bool
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
        $transactionData = $payout->{'v1/payout'} ?? null;
        if (!$transactionData->status) {

            $withdrawal->meta = array_merge($meta, [
                'payout_error' => $payout->message,
            ]);
            // $this->withdrawal->user->increment('balance',$this->withdrawal->amount);
            return false;
        }

        $withdrawal->meta = array_merge($meta, [
            'payout_id' => $transactionData->id,
            'payout_status' => $transactionData->status ?? 'pending',
        ]);

        $start = $fedapayService->startPayout($transactionData->id);

        if (!$start->success) {
            $withdrawal->meta = array_merge(
                (array) $withdrawal->meta,
                ['payout_error' => 'Payout créé mais non lancé']
            );
            // $this->withdrawal->user->increment('balance',$this->withdrawal->amount);
            return false;
        }

        return true;
    }*/

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
