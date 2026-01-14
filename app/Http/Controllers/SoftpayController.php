<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\SoftpayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Investment;
use App\Models\Transaction;
use Illuminate\Support\Facades\Log;

class SoftpayController extends Controller
{
    protected $softpay;

    public function __construct(SoftpayService $softpay)
    {
        $this->softpay = $softpay;
    }

    /**
     * Vérifie le statut d'une commande Softpay
     * @param $token
     * @return JsonResponse
     */
    public function checkStatus(string $token)
    {
        try {
            $transaction = Transaction::where('reference', $token)->first();

            if (!$transaction) {
                return response()->json([
                    'success' => false,
                    'status'  => 'not_found',
                    'message' => 'Transaction introuvable'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'status'  => $transaction->status, // pending | success | failed
            ]);

        } catch (\Throwable $e) {
            logger()->error('CHECK PAYMENT STATUS ERROR', [
                'token' => $token,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'status'  => 'error',
                'message' => 'Erreur serveur'
            ], 500);
        }
    }


    /**
     * Softpay callback
     * @param Request $request
     * @return JsonResponse
     */
    public function callback(Request $request)
    {
        Log::info('fedapay callback reçu:', $request->all());

        // Récupérer les informations envoyées par Paydunya
        $referenceId = $request->input('id'); // token ou reference selon docs Softpay
        $status      = $request->input('status'); // SUCCESS ou FAILED
        $amount      = $request->input('amount');

        if (!$referenceId) {
            Log::warning('fedapay callback sans token');
            return response()->json(['message' => 'Token manquant'], 400);
        }

        // Récupérer la transaction associée
        $transaction = Transaction::where('reference', $referenceId)->first();

        if (!$transaction) {
            Log::error("Transaction introuvable pour le token: $referenceId");
            return redirect()->away(env('FRONTEND_URL') . '/checkout/echec-paiement');
        }

        // Mettre à jour la transaction et l'investissement
        if ($status === 'approved') {
            $transaction->update(['status' => 'success']);
            if ($transaction->investment) {
                $transaction->investment->update(['status' => 'active']);

            }
        } else {
            $transaction->update(['status' => 'failed']);
            if ($transaction->investment) {
                $transaction->investment->update(['status' => 'failed']);
            }
        }

        Log::info("fedapay callback traité: {$referenceId}, status: {$status}");

        // Réponse obligatoire à Paydunya
        return redirect()->away(env('FRONTEND_URL') . '/checkout/success');

    }
    public function callbackOrder(Request $request)
    {
        Log::info('fedapay callback reçu:', $request->all());

        // Récupérer les informations envoyées par Paydunya
        $referenceId = $request->input('id'); // token ou reference selon docs Softpay
        $status      = $request->input('status'); // SUCCESS ou FAILED
        $amount      = $request->input('amount');

        if (!$referenceId) {
            Log::warning('fedapay callback sans token');
            return response()->json(['message' => 'Token manquant'], 400);
        }

        // Récupérer la transaction associée
        $transaction = Order::where('reference_id', $referenceId)->first();

        if (!$transaction) {
            Log::error("Transaction introuvable pour le token: $referenceId");
            return redirect()->away(env('FRONTEND_URL') . '/checkout/echec-paiement');
        }

        // Mettre à jour la transaction et l'investissement
        if ($status === 'approved') {
            $transaction->update(['status' => 'completed']);

        } else {
            $transaction->update(['status' => 'failed']);

        }

        Log::info("fedapay callback traité: {$referenceId}, status: {$status}");

        // Réponse obligatoire à Paydunya
        return redirect()->away(env('FRONTEND_URL') . '/checkout/success');

    }
}
