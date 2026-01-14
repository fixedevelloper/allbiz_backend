<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use App\Services\FedaPayService;
use Illuminate\Console\Command;

class CheckFedaPayStatus extends Command
{
    protected $signature = 'fedapay:check-status';
    protected $description = 'Vérifie le statut des paiements FedaPay';

    public function handle()
    {
        $fedaPay = new FedaPayService();

        $transactions = Transaction::where('status', 'pending')
            ->whereNotNull('reference')
            ->get();

        foreach ($transactions as $transaction) {
            try {
                $response = $fedaPay->checkStatus($transaction->reference);
                $transactionData = $response->{'v1/transaction'} ?? null;

                logger(json_encode($transactionData));
                if (!$transactionData || !isset($transactionData->status)) {
                    logger()->warning("Transaction FedaPay introuvable ou réponse invalide pour transaction {$transaction->id}");
                    continue;
                }

                logger()->info("Check FedaPay Status: Transaction {$transaction->id} => {$transactionData->status}");

                switch ($transactionData->status) {
                    case 'approved':
                        $transaction->update(['status' => 'success']);
                        $transaction->investment?->update(['status' => 'active']);
                        break;
                    case 'declined':
                        $transaction->update(['status' => 'failed']);
                        break;
                    case 'canceled':
                        $transaction->update(['status' => 'canceled']);
                        break;
                    case 'refunded':
                        $transaction->update(['status' => 'refunded']);
                        break;
                    default:
                        break; // pending
                }

            } catch (\Throwable $e) {
                logger()->error("Erreur lors de la vérification FedaPay pour transaction {$transaction->id}: ".$e->getMessage());
            }
        }

        return Command::SUCCESS;
    }
}


