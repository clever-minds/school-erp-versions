<?php

namespace App\Http\Controllers;

use Throwable;
use Carbon\Carbon;
use Razorpay\Api\Api;
use App\Models\Parents;
use App\Models\FeesPaid;
use Illuminate\Http\Request;
use UnexpectedValueException;
use App\Services\ResponseService;
use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Repositories\FeesPaid\FeesPaidInterface;
use App\Repositories\CompulsoryFee\CompulsoryFeeInterface;
use App\Repositories\OptionalFee\OptionalFeeInterface;
use App\Repositories\PaymentConfiguration\PaymentConfigurationInterface;
use App\Repositories\PaymentTransaction\PaymentTransactionInterface;

class WebhookController extends Controller {
    private PaymentTransactionInterface $paymentTransaction;
    private FeesPaidInterface $feesPaid;
    private CompulsoryFeeInterface $compulsoryFees;
    private PaymentConfigurationInterface $paymentConfigurations;
    private OptionalFeeInterface $optionalFees;

    public function __construct(PaymentTransactionInterface $paymentTransaction, FeesPaidInterface $feesPaid, CompulsoryFeeInterface $compulsoryFees, PaymentConfigurationInterface $paymentConfigurations, OptionalFeeInterface $optionalFees) {
        $this->paymentTransaction = $paymentTransaction;
        $this->feesPaid = $feesPaid;
        $this->compulsoryFees = $compulsoryFees;
        $this->paymentConfigurations = $paymentConfigurations;
        $this->optionalFees = $optionalFees;
    }

    public function stripe() {
        // You can find your endpoint's secret in your webhook settings

        $endpoint_secret = $this->paymentConfigurations->builder()->where('payment_method','stripe')->pluck('webhook_secret_key')->first();

        $payload = @file_get_contents('php://input');
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];

        // Verify webhook signature and extract the evenst.
        // See https://stripe.com/docs/webhooks/signatures for more information.
        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload, $sig_header, $endpoint_secret
            );
            Log::error("Signature Verified");
        } catch (UnexpectedValueException $e) {
            // Invalid payload
            Log::error("Payload Mismatch");
            http_response_code(400);
            exit();
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            // Invalid signature
            Log::error("Signature Verification Failed");
            http_response_code(400);
            exit();
        }

        // get the metadata
        $feesId = $event->data->object->metadata->fees_id;
        $class_id = $event->data->object->metadata->class_id;
        $student_id = $event->data->object->metadata->student_id;
        $session_year_id = $event->data->object->metadata->session_year_id;
        $paymentTransactionId = $event->data->object->metadata->payment_transaction_id;
        $type_of_fee = $event->data->object->metadata->type_of_fee;
        $is_full_paid = $event->data->object->metadata->is_full_paid;
        Log::error($event->type);

        //get the current today's date
        $current_date = date('Y-m-d');

        // handle the events
        switch ($event->type) {
            case 'payment_intent.succeeded':
                Log::error($paymentTransactionId);
                $paymentTransactionData = $this->paymentTransaction->findById($paymentTransactionId);
                if (!empty($paymentTransactionData)) {
                    if ($paymentTransactionData->status != 1) {
                        $paymentTotalAmount = $paymentTransactionData->amount;
                        $school_id = $paymentTransactionData->school_id;

                        $this->paymentTransaction->update($paymentTransactionId,['payment_status' => "1",'school_id' => $school_id]);
                    }else{
                        Log::error("Transaction Already Successes --->");
                        break;
                    }
                }else {
                    Log::error("Payment Transaction id not found --->");
                    break;
                }

                $feesPaidQuery = $this->feesPaid->builder()->where(['fees_id' => $feesId, 'student_id' => $student_id, 'class_id' => $class_id, 'session_year_id' => $session_year_id, 'school_id' => $school_id]); // Get Query Of Fees Paid
                $feesPaidDB = $feesPaidQuery->first(); // Get Fees Paid Data

                // Check if Fees Paid Exists Then Add The optional Fees Amount with Fess Paid Amount
                $totalAmount = $feesPaidQuery->count() ? $feesPaidDB->amount + $paymentTotalAmount : $paymentTotalAmount;

                // Fees Paid Array
                $feesPaidData = array(
                    'amount'        => $totalAmount,
                    'date'          => date('Y-m-d',strtotime($current_date)),
                    'is_fully_paid' => $is_full_paid,
                    "school_id"     => $school_id
                );

                // If Fees Paid Doesn't Exists
                if (!$feesPaidQuery->count()) {

                    // Then Merge the Fees Paid Data with the This Data
                    $feesPaidData = array_merge($feesPaidData, [
                        'fees_id'           => $feesId,
                        'student_id'        => $student_id,
                        'class_id'          => $class_id,
                        'session_year_id'   => $session_year_id,
                        'school_id'         => $school_id
                    ]);

                    // Store Fees Paid New Entry
                    $feesPaidResult = $this->feesPaid->create($feesPaidData);
                }else{
                    // If Data Exists then Update only Date and Amount
                    $feesPaidResult = $this->feesPaid->update($feesPaidDB->id,$feesPaidData);
                }



                if($type_of_fee == 1 || $type_of_fee == 2){
                    $this->compulsoryFees->builder()->where('payment_transaction_id', $paymentTransactionId)->update([
                        'status'       => "1",
                        'fees_paid_id' => $feesPaidResult->id,
                    ]);
                }else{
                    $this->optionalFees->builder()->where('payment_transaction_id', $paymentTransactionId)->update([
                        'status'       => "1",
                        'fees_paid_id' => $feesPaidResult->id,
                    ]);
                }

                $user = $this->user->builder()->role('Guardian')->where('id', $parent_id)->pluck('user_id');
                $body = 'Amount :- ' . $paymentTotalAmount;
                $type = 'Online';
                send_notification($user, 'Payment Success', $body, $type);
                http_response_code(200);
                break;

            case 'payment_intent.payment_failed':
                $paymentTransactionData = $this->paymentTransaction->findById($paymentTransactionId);
                if (!empty($paymentTransactionData)) {
                    if ($paymentTransactionData->status != 1) {
                        $paymentTotalAmount = $paymentTransactionData->amount;
                        $school_id = $paymentTransactionData->school_id;

                        $this->paymentTransaction->update($paymentTransactionId,['payment_status' => "0",'school_id' => $school_id]);

                        if($type_of_fee == 1 || $type_of_fee == 2){
                            $this->compulsoryFees->builder()->where('payment_transaction_id', $paymentTransactionId)->update([
                                'status'       => "0",
                            ]);
                        }else{
                            $this->optionalFees->builder()->where('payment_transaction_id', $paymentTransactionId)->update([
                                'status'       => "0",
                            ]);
                        }


                        http_response_code(400);
                        $user = $this->user->builder()->role('Guardian')->where('id', $parent_id)->pluck('user_id');
                        $body = 'Amount :- ' . $paymentTotalAmount;
                        $type = 'Online';
                        send_notification($user, 'Payment Failed', $body, $type);
                        break;
                    }
                }else {
                    Log::error("Payment Transaction id not found --->");
                    break;
                }
            default:
                // Unexpected event type
                Log::error('Received unknown event type');
        }
    }
}
