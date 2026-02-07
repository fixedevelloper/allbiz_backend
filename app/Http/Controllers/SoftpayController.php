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
        Log::info('Fedapay callback reçu', $request->all());

        $referenceId = $request->input('id');
        $status      = $request->input('status');
        $amount      = $request->input('amount');

        if (!$referenceId) {
            Log::warning('Fedapay callback sans référence');
            return response()->json(['message' => 'missing reference'], 200);
        }

        // Réponse immédiate au provider (IMPORTANT)
        response()->json(['status' => 'received'])->send();

        // Traitement en arrière-plan
        dispatch(function () use ($referenceId, $status, $amount) {

            DB::transaction(function () use ($referenceId, $status, $amount) {

                $transaction = Transaction::where('reference', $referenceId)
                    ->lockForUpdate()
                    ->first();

                if (!$transaction) {
                    Log::error("Transaction introuvable: {$referenceId}");
                    return;
                }

                // 🔐 Idempotence
                if ($transaction->status === 'success') {
                    Log::info("Transaction déjà traitée: {$referenceId}");
                    return;
                }

                if ($status === 'approved') {
                    $transaction->update(['status' => 'success']);

                    $transaction->user?->update([
                        'membership_level' => $transaction->amount
                    ]);

                if ($transaction->investment) {
                    $transaction->investment->update(['status' => 'active']);
                }
            } else {
                    $transaction->update(['status' => 'failed']);

                    if ($transaction->investment) {
                        $transaction->investment->update(['status' => 'failed']);
                    }
                }

                Log::info("Fedapay callback traité OK: {$referenceId}");
            });

        });

        // Fin du callback (rien après)
        return;
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
