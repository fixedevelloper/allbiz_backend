<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Ramsey\Uuid\Uuid;

class SoftpayService
{
    private $base_url;

    public function __construct()
    {
        $this->base_url = (env('APP_ENV') == 'local')
            ? 'https://app.paydunya.com/sandbox-api/v1/'
            : 'https://app.paydunya.com/api/v1/';
    }

    /**
     * Générer un token Softpay pour une facture
     * @param float $amount
     * @param string $description
     * @param string $callbackUrl
     * @param string $orderId
     * @return
     * @throws \Exception
     */
    public function generateSoftpayToken(float $amount, string $description, string $callbackUrl, string $orderId)
    {
        $payload = [
            "invoice" => [
                "total_amount" => intval($amount),
                "description"  => $description
            ],
            "store" => [
                "name"         => config('app.name'),
                "website_url"  => url('/')
            ],
            "actions" => [
                "callback_url" => $callbackUrl,
                "return_url"   => $callbackUrl,
                "cancel_url"   => $callbackUrl
            ],
            "custom_data" => [
                "order_id" => $orderId
            ]
        ];

        $response = $this->httpRequest("checkout-invoice/create", $payload);

        if (isset($response->response_code) && $response->response_code === "00") {
            return $response->token;
        }

        Log::error("Softpay token generation failed", ['response' => $response]);
        throw new \Exception("Impossible de générer le token Softpay");
    }

    /**
     * Faire un paiement Softpay pour un client selon le pays et le mode
     * @param string $countryCode
     * @param string $method
     * @param array $customer
     * @param string $paymentToken
     * @return object
     * @throws \Exception
     */
    public function makePayment(string $countryCode, string $method, array $customer, string $paymentToken)
    {
        $endpoint = $this->getUrlForCountryAndMethod($countryCode, $method);

        logger($endpoint);
        if (!$endpoint) {
            throw new \Exception("Mode de paiement Softpay non supporté pour $countryCode / $method");
        }

        $bodyKeyPrefix = str_replace(['-', ' '], '_', strtolower($method)) . "_" . strtolower($countryCode);

        $payload = [
            "{$bodyKeyPrefix}_fullName"     => $customer['fullName'],
            "{$bodyKeyPrefix}_email"        => $customer['email'],
            "{$bodyKeyPrefix}_phone_number" => $customer['phone'],
            "payment_token"                 => $paymentToken,
        ];

        $response = $this->httpRequest($endpoint, $payload);

        if (isset($response->success) && $response->success === true) {
            return $response;
        }

        Log::error("Softpay payment failed", ['response' => $response]);
        throw new \Exception($response->message ?? "Erreur Softpay");
    }

    /**
     * Vérifier le paiement Softpay avec le token
     * @param string $token
     * @return array|string[]
     */
    public function checkPayment(string $token)
    {
        $response = $this->httpRequest("checkout-invoice/confirm/$token", []);

        if (isset($response->response_code) && $response->response_code === "00") {
            return [
                'status' => $response->status,
                'amount' => $response->invoice->total_amount ?? 0
            ];
        }

        Log::error("Softpay check payment failed", ['response' => $response]);
        return [
            'status' => 'error'
        ];
    }

    /**
     * Retourne l'URL Softpay selon le pays et le mode
     * @param string $countryCode
     * @param string $method
     * @return string|null
     */
    private function getUrlForCountryAndMethod(string $countryCode, string $method): ?string
    {
        $base = 'softpay/';

        $countryCode = strtolower(trim($countryCode));
        $method = strtolower(trim($method));

        // Normalisation des noms d'opérateurs
        $methodAliases = [
            'orange' => 'orange money',
            'orange-money' => 'orange money',
            'orange money' => 'orange money',

            'mtn' => 'mtn',
            'mtn money' => 'mtn',

            'moov' => 'moov',

            'free' => 'free money',
            'free money' => 'free money',

            'wave' => 'wave',

            'tmoney' => 'tmoney',
            't-money' => 'tmoney',
            't money' => 'tmoney',
        ];

        $method = $methodAliases[$method] ?? $method;

        $urls = [
            'sn' => [
                'orange money' => 'new-orange-money-senegal',
                'free money'   => 'free-money-senegal',
            ],
            'ci' => [
                'mtn'          => 'mtn-ci',
                'orange money' => 'orange-money-ci',
                'moov'         => 'moov-ci',
            ],
            'bj' => [
                'mtn'  => 'mtn-benin',
                'moov' => 'moov-benin',
                'wave' => 'wave-benin',
            ],
            'tg' => [
                'tmoney' => 't-money-togo',
            ],
            'ml' => [
                'orange money' => 'orange-money-ml',
            ],
        ];

        if (!isset($urls[$countryCode][$method])) {
            return null; // opérateur ou pays non supporté
        }

        return $base . $urls[$countryCode][$method];
    }


    /**
     * Requête HTTP Laravel vers Paydunya
     * @param string $endpoint
     * @param array $data
     * @return object
     */
    private function httpRequest(string $endpoint, array $data)
    {
        $url = $this->base_url . $endpoint;

        logger($url);
        $response = Http::withHeaders([
            'PAYDUNYA-MASTER-KEY'  => env('PAYDUNYA_PRINCIPAL'),
            'PAYDUNYA-PRIVATE-KEY' => env('PAYDUNYA_SECRET_KEY'),
            'PAYDUNYA-TOKEN'       => env('PAYDUNYA_TOKEN'),
        ])
            ->timeout(30)       // ⬅️ IMPORTANT
            ->connectTimeout(15)
            ->post($url, $data);


        if ($response->failed()) {
            Log::error("Paydunya API request failed", ['body' => $response->body()]);
            return (object)[
                'response_code' => 'error',
                'response_text' => 'Erreur de communication avec PayDunya'
            ];
        }

        return $response->object();
    }
}
