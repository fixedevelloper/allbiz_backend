<?php


namespace App\Http\Controllers\web;


use App\Http\Controllers\Controller;
use App\Models\Roulette;
use App\Models\Transaction;

class RouletteController extends Controller
{
    public function index()
    {
        $roulettes = Roulette::with('commission.investment')
            ->where('status', false)
            ->get();

        return view('roulettes.index', compact('roulettes'));
    }

    public function play(Roulette $roulette)
    {
        if ($roulette->status) {
            return back()->withErrors('Cette roulette a déjà été jouée');
        }

        // Gain aléatoire
        $gain = rand(1000, 10000);

        $roulette->update([
            'amount' => $gain,
            'status' => true,
            'executed_at' => now()
        ]);

        // Créer transaction
        Transaction::create([
            'user_id' => $roulette->commission->referrer_id,
            'amount' => $gain,
            'type' => 'commission',
            'status' => 'success',
            'meta' => [
                'roulette_id' => $roulette->id
            ]
        ]);

        return back()->with('success', "Vous avez gagné {$gain} FCFA 🎉");
    }
}
