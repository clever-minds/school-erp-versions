<?php

namespace App\Services\Payment;

use JetBrains\PhpStorm\Pure;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentIntent;
use Stripe\StripeClient;

class StripePayment implements PaymentInterface {
    private StripeClient $stripe;
    private string $currencyCode;

    #[Pure] public function __construct($secretKey, $currencyCode) {
        // Call Stripe Class and Create Payment Intent
        $this->stripe = new StripeClient($secretKey);
        $this->currencyCode = $currencyCode;
    }

    /**
     * @param $amount
     * @param $customMetaData
     * @return PaymentIntent
     * @throws ApiErrorException
     */
    public function createPaymentIntent($amount, $customMetaData) {
        try {
            return $this->stripe->paymentIntents->create(
                [
                    'amount'      => $amount * 100,
                    'currency'    => $this->currencyCode,
                    'metadata'    => $customMetaData,
//                    'description' => 'Fees Payment',
//                    'shipping' => [
//                        'name' => 'Jenny Rosen',
//                        'address' => [
//                            'line1' => '510 Townsend St',
//                            'postal_code' => '98140',
//                            'city' => 'San Francisco',
//                            'state' => 'CA',
//                            'country' => 'US',
//                        ],
//                    ],
                ]
            );
        } catch (ApiErrorException $e) {
            throw $e;
        }
    }

    /**
     * @param $paymentId
     * @return PaymentIntent
     * @throws ApiErrorException
     */
    public function retrievePaymentIntent($paymentId) {
        try {
            return $this->stripe->paymentIntents->retrieve($paymentId);
        } catch (ApiErrorException $e) {
            throw $e;
        }
    }
}
