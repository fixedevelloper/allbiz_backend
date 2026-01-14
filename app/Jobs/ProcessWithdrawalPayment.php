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

        DB::beginTransaction();

        try {
            $started = $this->simulatePayment();

            if (!$started) {
                logger('*******je suis********');
                DB::rollBack();
                $this->withdrawal->update([
                    'status' => 'failed',
                ]);
                return;
            }

            DB::commit();

        } catch (\Throwable $e) {

            DB::rollBack();

            // 💰 Remboursement sécurisé
            $this->withdrawal->user()->increment(
                'balance',
                $this->withdrawal->amount
            );

            $this->withdrawal->update([
                'status' => 'failed',
                'meta' => array_merge($this->withdrawal->meta, [
                    'exception' => $e->getMessage(),
                ]),
            ]);

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
        $meta = $this->withdrawal->meta;

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
            logger('je suis ici');
/*
            if ($payout->status === 403) {
                $this->withdrawal->update([
                    'status' => 'failed',
                    'meta' => array_merge($meta, [
                        'payout_error' => $payout->message
                    ])
                ]);
                return false;
            }

            if ($payout->status === 404) {
                $this->withdrawal->update([
                    'status' => 'pending',
                    'meta' => array_merge($meta, [
                        'payout_error' => 'Balance indisponible',
                        'retry_at' => now()->addMinutes(10),
                    ])
                ]);
                return false;
            }*/

            logger($this->withdrawal);
            $this->withdrawal->forceFill([
                'status' => 'failed',
                'meta' => array_merge(
                    (array) $meta,
                    ['payout_error' => $payout->message]
                )
            ])->save();

            return false;
        }



logger('pppppppppppppppppp');
        // 🔐 Sauvegarde immédiate
        $this->withdrawal->update([
            'status' => 'processing',
            'meta' => array_merge($meta, [
                'payout_id' => $payout->id,
                'payout_status' => $payout->status ?? 'pending',
            ]),
        ]);

        // 🚀 Lancement réel
        $start= $fedapayService->startPayout($payout->id);
        if (!$start->success) {
            $this->withdrawal->update([
                'status' => 'pending',
                'meta' => array_merge($meta, [
                    'payout_error' => 'Payout créé mais non lancé',
                ])
            ]);
            return false;
        }

        return true;
    }



}
