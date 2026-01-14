<?php


namespace App\Services;


use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;


class FedaPayService
{
    private string $base_url;

    public function __construct()
    {
        $this->base_url = (env('APP_ENV') === 'local')
            ? 'https://sandbox-api.fedapay.com/v1/'
            : 'https://api.fedapay.com/v1/';
    }

    /**
     * Création d'un client FedaPay
     * @param array $data
     * @return object
     * @throws \Exception
     */
    public function createCustomer(array $data)
    {
        $response = $this->httpRequest('customers', [
            'customer' => [
                'firstname' => $data['firstname'] ?? '',
                'lastname'  => $data['lastname'] ?? '',
                'email'     => $data['email'] ?? null,
                'phone'     => [
                    'number'  => $data['phone'],
                    'country' => $data['country'] ?? 'BJ'
                ]
            ]
        ]);

        if (!$response->success) {
            throw new \Exception(
                'FedaPay - Création client échouée : ' . $response->message
            );
        }

        // ✅ CORRECTION ICI
        $customer = $response->data->{'v1/customer'} ?? null;

        if (!$customer || empty($customer->id)) {
            Log::error('FEDAPAY CUSTOMER INVALID RESPONSE', [
                'response' => $response
            ]);

            throw new \Exception('FedaPay - Réponse client invalide');
        }

        return $customer;
    }



    /**
     * 🔵 COLLECT : Paiement client (Mobile Money / Carte)
     * @param array $data
     * @return object
     */
    public function collect(array $data)
    {

        return $this->httpRequest('transactions', [

                'amount'        => $data['amount'],
                'description'   => $data['description'] ?? 'Paiement',
                'callback_url'  => $data['callback_url'],
               // 'currency'     => 'XOF', // ✅ STRING (API REST)
            'currency' => [
                'iso' => 'XOF'
            ],
                'customer'      => [
                    'id' => $data['customer_id']
                ],
                'mode'=>'momo_test'

        ]);
    }

    /**
     * 🟢 PAYOUT : Transfert / décaissement
     * @param array $data
     * @return object
     * @throws \Exception
     */
    public function payout(array $data)
    {
        logger($data);
        $res=$this->httpRequest('payouts', [
            'amount' => (int) $data['amount'],
            'currency' => [
                'iso' => 'XOF',
            ],
            'description' => $data['description'] ?? 'Décaissement',
            'customer' => [
                'firstname' => $data['name'],
                'lastname' => $data['name'],
                'phone_number' => [
                    'number'  => $data['phone_number'],
                    'country' =>strtolower($data['country'] ?? 'bj'),
                ],
            ],
            "mode" => "mobile_money",
            'merchant_reference' => $data['reference'],
        ]);
        return $res;
    }

    /**
     * 🚀 Démarrer un payout FedaPay
     *
     * @param int|string $payoutId
     * @return object
     * @throws \Exception
     */
    public function startPayout($payoutId)
    {
        $url = $this->base_url . 'payouts/start';

        $response = Http::withHeaders([
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . env('FEDAPAY_SECRET'),
        ])
            ->timeout(30)
            ->connectTimeout(15)
            ->put($url, [
                'payout_id' => (int) $payoutId,
            ]);

        if ($response->failed()) {
            Log::error('FEDAPAY PAYOUT START ERROR', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            throw new \Exception('Impossible de démarrer le payout FedaPay');
        }

        return $response->object();
    }

    /**
     * 🔁 Vérifier le statut d'une transaction
     * @param string $transactionId
     * @return
     */
    public function checkStatus(string $transactionId)
    {
        return $this->httpGet("transactions/{$transactionId}");
    }

    /**
     * =========================
     * HTTP HELPERS
     * =========================
     * @param string $endpoint
     * @param array $data
     * @return object
     */
    private function httpRequest(string $endpoint, array $data)
    {
        $url = $this->base_url . $endpoint;

        logger($data);
        try {
            $response = Http::withHeaders([
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . env('FEDAPAY_SECRET'),
            ])
                ->timeout(30)
                ->connectTimeout(15)
                ->post($url, $data);


        } catch (ConnectionException $e) {
            Log::error('FEDAPAY CONNECTION ERROR', [
                'message' => $e->getMessage(),
                'url'     => $url
            ]);

            throw new \Exception("Impossible de contacter FedaPay");
        }

        if ($response->failed()) {
            Log::error('FEDAPAY API ERROR', [
                'status' => $response->status(),
                'body'   => $response->json()
            ]);

            return (object)[
                'success' => false,
                'message' => $response->json()['message']
                    ?? 'Erreur lors de la requête FedaPay',
                'errors'  => $response->json()['errors'] ?? null,
            ];
        }

        return (object)[
            'success' => true,
            'data'    => $response->object(),
        ];
    }


    private function httpGet(string $endpoint)
    {
        $url = $this->base_url . $endpoint;

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('FEDAPAY_SECRET'),
        ])->get($url);

        return $response->object();
    }
}
