<?php


namespace App\Http\Controllers\web;


use App\Http\Controllers\Controller;
use App\Models\Commission;
use App\Models\Investment;
use App\Models\Roulette;
use Illuminate\Http\Request;

class InvestmentController extends Controller
{
    public function index()
    {
        $investments = Investment::with('user')->latest()->get();
        return view('investments.index', compact('investments'));
    }

    public function create()
    {
        return view('investments.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1000'
        ]);

        // 1 seul investissement par user
        if (Investment::where('user_id', auth()->id())->exists()) {
            return back()->withErrors('Vous avez déjà un investissement actif.');
        }

        $investment = Investment::create([
            'user_id' => auth()->id(),
            'amount' => $request->amount
        ]);

        // Exemple : créer une commission (10%)
        $commission = Commission::create([
            'referrer_id' => auth()->id(),
            'investment_id' => $investment->id,
            'amount' => $investment->amount * 0.10,
            'roulette_count' => 1
        ]);

        // Créer une roulette liée
        Roulette::create([
            'commission_id' => $commission->id,
            'type' => '1step'
        ]);

        return redirect()->route('investments.index')
            ->with('success', 'Investissement créé avec succès');
    }
}
