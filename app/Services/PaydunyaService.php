<?php


namespace App\Services;

use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaydunyaService
{
    private $base_url;

    public function __construct()
    {
        $this->base_url = (env('APP_ENV') == 'local')
            ? 'https://app.paydunya.com/api/v1/'
            : 'https://app.paydunya.com/sandbox-api/v1/';
    }

    /** TRANSFERT */
    public function make_transfert($item)
    {
        $data = [
            'account_alias' => $item['phone'],
            'amount' => $item['amount'],
            'withdraw_mode' => $item['draw'],
            'callback_url' => $item['callback_url']
        ];

        $response = $this->httpRequest('disburse/get-invoice', $data);
        Log::info(json_encode($response));

        if ($response->response_code == '00') {
            $txnid = Uuid::uuid4()->toString();
            $submitData = [
                'disburse_invoice' => $response->disburse_token,
                'disburse_id' => $txnid
            ];

            return $this->httpRequest('disburse/submit-invoice', $submitData);
        }

        return $response;
    }

    /** REQUÊTE HTTP LARAVEL
     * @param $endpoint
     * @param $data
     * @return object
     */
    protected function httpRequest($endpoint, $data)
    {
        $url = $this->base_url . $endpoint;

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'PAYDUNYA-MASTER-KEY' => env('PAYDUNYA_PRINCIPAL'),
            'PAYDUNYA-PRIVATE-KEY' => env('PAYDUNYA_SECRET_KEY'),
            'PAYDUNYA-TOKEN' => env('PAYDUNYA_TOKEN'),
        ])->post($url, $data);

        if ($response->failed()) {
            Log::error("Erreur Paydunya API: " . $response->body());
            return (object)[
                'response_code' => 'error',
                'response_text' => 'Erreur de communication avec Paydunya'
            ];
        }

        return $response->object();
    }

    /** COLLECTE / PAYMENT
     * @param $value
     * @return array|null
     */
    public function make_collete($value)
    {
        try {
            $order = $this->createOrder($value);
            $response = $this->httpRequest('checkout-invoice/create', $order);

            if (isset($response->response_code) && $response->response_code == "00") {

                $data = [
                    "customer_name" => $value['customer_name'],
                    "customer_email" =>  $value['customer_email'],
                    "phone_number" =>  $value['phone_number'],
                    "invoice_token" => $response->token,
                ];
                $resp = $this->httpRequest($this->getUrlForCountryAndMethod($value['country_code'],$value['operatror']), $data);
            } else {
                Log::error($response->response_text ?? 'Erreur inconnue Paydunya');
                return null;
            }
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            return null;
        }
    }

    /** CREATION DE COMMANDE
     * @param $values
     * @return array
     */
    public function createOrder($values)
    {
        $value = $values['amount'];
        $txnid = $values['referenceId'];
        $hash = hash('sha512', "$value|||||||||||$txnid");

        $paydunya_items = [
            [
                "name" => $values['product_name'],
                "quantity" => $values['quantity'],
                "unit_price" => intval($value),
                "total_price" => intval($value),
                "description" => $values['description']
            ]
        ];

        return [
            "invoice" => [
                "items" => $paydunya_items,
                "total_amount" => intval($value),
                "description" => $values['description']
            ],
            "store" => [
                "name" => "wetransfercash",
                "website_url" => "https://allbizgroup.com"
            ],
            "actions" => [
                "cancel_url" => $values['callback_url'],
                "callback_url" => $values['callback_url'],
                "return_url" => "https://app.allbizgroup.com"
            ],
            "custom_data" => [
                "order_id" => 1,
                "trans_id" => $txnid,
                "to_user_id" => 2,
                "hash" => $hash
            ]
        ];
    }

    public function check_collete($token)
    {
        try {
            $response = $this->httpRequest("checkout-invoice/confirm/$token", []);
            Log::info(json_encode($response));

            if (isset($response->response_code) && $response->response_code == "00") {
                return [
                    'status' => $response->status
                ];
            } else {
                Log::error($response->response_text ?? 'Erreur inconnue Paydunya');
                return [
                    'status' => $response->response_code ?? 'error'
                ];
            }
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            return [
                'status' => 'error'
            ];
        }
    }
    private function getUrlForCountryAndMethod($countryCode, $method)
    {
        $base = 'https://app.paydunya.com/api/v1/softpay/';

        $countryCode = strtolower($countryCode);
        $method      = strtolower($method);

        switch ($countryCode) {
            case 'sn': // Sénégal
                return match($method) {
                'orange money' => $base . 'orange-money-senegal',
                'free money'   => $base . 'free-money-senegal',
            default        => null,
        };

    case 'ci': // Côte d'Ivoire
            return match($method) {
            'mtn'           => $base . 'mtn-ci',
                'orange money'  => $base . 'orange-money-ci',
                'moov'          => $base . 'moov-ci',
                default         => null,
            };

        case 'bj': // Bénin
            return match($method) {
            'mtn'           => $base . 'mtn-benin',
                'moov'          => $base . 'moov-benin',
                'wave'          => $base . 'wave-benin',
                default         => null,
            };

        case 'tg': // Togo
            return match($method) {
            'tmoney'        => $base . 't-money-togo',
                default         => null,
            };

        case 'ml': // Mali
            return match($method) {
            'orange money'  => $base . 'orange-money-mali',
                default         => null,
            };

        default:
            return null; // Pays non supporté
    }
}
}


