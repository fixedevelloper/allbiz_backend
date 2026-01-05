<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tontine;
use Carbon\Carbon;

class GenerateTontineTours extends Command
{
    protected $signature = 'tontines:generate-tours';
    protected $description = 'Créer automatiquement les tours selon la fréquence des tontines';

    public function handle()
    {
        $this->info('⏳ Génération des tours de tontine...');

        Tontine::with(['members', 'tours'])->chunk(50, function ($tontines) {
            foreach ($tontines as $tontine) {
                $this->createTourIfNeeded($tontine);
            }
        });

        $this->info('✅ Génération terminée.');
    }

    private function createTourIfNeeded(Tontine $tontine)
    {
        $members = $tontine->members()->orderBy('position')->get();

        if ($members->isEmpty()) {
            return;
        }

        /** Dernier tour */
        $lastTour = $tontine->tours()->latest('cycle_number')->first();

        /** Vérifier la fréquence */
        if ($lastTour && !$this->isNextCycleDue($tontine->frequency, $lastTour->created_at)) {
            return;
        }

        /** Numéro de cycle */
        $nextCycle = ($lastTour->cycle_number ?? 0) + 1;

        /** Round-robin bénéficiaire */
        $beneficiary = $members[($nextCycle - 1) % $members->count()]->user;

        $tontine->tours()->create([
            'cycle_number'   => $nextCycle,
            'beneficiary_id' => $beneficiary->id,
            'amount'         => $tontine->amount,
            'status'         => 'PENDING'
        ]);

        $this->info("✔ Tour {$nextCycle} créé pour la tontine {$tontine->id}");
    }

    private function isNextCycleDue(string $frequency, Carbon $lastDate): bool
    {
        return match ($frequency) {
        'daily'   => $lastDate->addDay()->isPast(),
            'weekly'  => $lastDate->addWeek()->isPast(),
            'monthly' => $lastDate->addMonth()->isPast(),
            default   => false
        };
    }
}
