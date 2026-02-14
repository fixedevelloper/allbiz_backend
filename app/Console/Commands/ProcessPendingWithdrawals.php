<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Transaction;
use App\Mail\WithdrawalNotification;
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
        $transactions = Transaction::where('status', 'processing')
            ->where('type', 'withdrawal')
            ->get();

        $count = $transactions->count();

        if ($count === 0) {
            $this->info("Aucune transaction en processing de type withdrawal.");
            return 0;
        }

        $this->info("Transactions en processing (type withdrawal) : $count");

        $adminEmail = config('mail.from.admin_email');

        if (!$adminEmail) {
            $this->error("Admin email non défini dans config/mail.php");
            return 1;
        }

        foreach ($transactions as $tx) {
            // Envoi du mail via queue
            Mail::to($adminEmail)->queue(new WithdrawalNotification($tx));
            $tx->update([
                'status' => 'success',
            ]);
            $this->line("Mail mis en queue pour Transaction ID: {$tx->id} | Réf: {$tx->reference}");
        }

        $this->info("Tous les mails ont été mis en queue avec succès.");

        return 0;
    }
}
