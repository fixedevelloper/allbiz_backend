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
            // 🔹 1. Création du payout
            $payout = $fedapayService->payout([
                'amount' => $meta['net_amount'],
                'description' => $meta['description'] ?? 'Décaissement',
                'phone_number' => $accountWithdraw->phone,
                'name' => $accountWithdraw->name,
                'country' => strtolower($accountWithdraw->operator->country->iso),
                'reference' => $withdrawal->reference,
            ]);

            // ❗ Vérifier succès API
            if (!isset($payout->success) || $payout->success !== true) {
                return $this->setError(
                    $withdrawal,
                    $meta,
                    $payout->message ?? 'Erreur création payout'
                );
            }

            // 🔹 2. Récupération des données
            $transactionData = $payout->data->{'v1/payout'} ?? null;

            if (!$transactionData) {
                return $this->setError($withdrawal, $meta, 'Structure réponse invalide');
            }

            // ❗ Vérifier status
            if ($transactionData->status === 'failed') {
                return $this->setError(
                    $withdrawal,
                    $meta,
                    $transactionData->last_error_code ?? 'Paiement échoué'
                );
            }

            // 🔹 3. Sauvegarde initiale
            $withdrawal->external_id = $transactionData->id;

            $withdrawal->meta = array_merge($meta, [
                'payout_id' => $transactionData->id,
                'payout_reference' => $transactionData->reference,
                'payout_status' => $transactionData->status,
                'payout_fees' => $transactionData->fees,
            ]);

           // $withdrawal->status = 'processing';
            $withdrawal->save();
/*
            // 🔹 4. Lancer le payout
            $start = $fedapayService->startPayout($transactionData->id);

            logger(json_encode($start));
            // ⚠️ ici réponse = tableau
            $startData = $start->data[0] ?? null;

            if (!$startData) {
                 $this->setError(
                    $withdrawal,
                    (array) $withdrawal->meta,
                    'Réponse start payout invalide'
                );
                return false;
            }

            // ❗ Vérifier status final
            if ($startData['status'] === 'failed') {
                $this->setError(
                    $withdrawal,
                    (array) $withdrawal->meta,
                    $startData['last_error_code'] ?? 'Echec payout'
                );
                return false;
            }

            // 🔹 5. Mise à jour finale
            $withdrawal->meta = array_merge((array)$withdrawal->meta, [
                'payout_status' => $startData['status'],
                'payout_reference' => $startData['reference'],
            ]);

            $withdrawal->status = match ($startData['status']) {
            'sent' => 'success',
            'failed' => 'failed',
            default => 'processing',
        };*/

        $withdrawal->save();

        return true;

    } catch (\Throwable $e) {

            return $this->setError(
                $withdrawal,
                $meta,
                $e->getMessage()
            );
        }
    }

    private function setError($withdrawal, $meta, $message): bool
    {
        $withdrawal->meta = array_merge((array)$meta, [
            'payout_error' => $message,
        ]);

        $withdrawal->status = 'failed';
        $withdrawal->save();

        return false;
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
