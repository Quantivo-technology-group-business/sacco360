<?php

namespace Modules\Flutterwave\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Flutterwave\Flutterwave;
use function GuzzleHttp\json_decode;

class FlutterwaveController extends Controller
{
    public function capture_payment(Request $request)
    {

        $data = json_decode($request->resp);
        $flutterwave_class = new Flutterwave($data->tx->txRef, 'loan');
        if ($data->tx->status == 'successful') {
            $response = Http::withToken($flutterwave_class->getSetting('flutterwave.secret_key'))->get("https://api.flutterwave.com/v3/transactions/{$data->tx->id}/verify");
           $response_data=$response->json();
            $flutterwave_class->processPayment([
                'module' => 'loan',
                'loan_id' =>$response_data['data']['tx_ref'],
                'reference' => $response_data['data']['id'],
                'amount' => $response_data['data']['amount'],
            ]);
            \flash('Thank you for making payment.')->success()->important();
            return redirect('portal/loan/' . $response_data['data']['tx_ref'] . '/show');
        }
        \flash('Payment was cancelled.')->warning()->important();
        return redirect('portal/loan/' . $data->tx->txRef . '/show');

    }

    /**
     * Flutterwave webhook.
     *
     * This method was previously a verbatim copy of StripeController::webhook():
     * it read the STRIPE_SIGNATURE header, instantiated an unimported Stripe
     * class and called \Stripe\Webhook::constructEvent(). It therefore fataled
     * on every delivery and no Flutterwave payment could ever be confirmed
     * server-side.
     *
     * Flutterwave authenticates webhooks with a shared secret sent in the
     * `verif-hash` header, not a signature over the payload. The amount is never
     * trusted from the payload — the transaction is re-verified against the
     * Flutterwave API before anything is posted.
     */
    public function webhook(Request $request)
    {
        $class = new Flutterwave();
        $expectedHash = $class->getSetting('flutterwave.webhook_secret_hash');

        $providedHash = $request->header('verif-hash');

        if (empty($expectedHash)) {
            Log::error('Flutterwave webhook rejected: flutterwave.webhook_secret_hash is not configured.');
            return response()->json(['status' => 'error', 'message' => 'Webhook not configured'], 500);
        }

        // hash_equals avoids a timing attack on the shared secret.
        if (!is_string($providedHash) || !hash_equals($expectedHash, $providedHash)) {
            Log::warning('Flutterwave webhook rejected: verif-hash mismatch.');
            return response()->json(['status' => 'error', 'message' => 'Invalid hash'], 401);
        }

        // Leading backslash forces the global json_decode. This file imports the
        // deprecated GuzzleHttp\json_decode shim, which throws on malformed
        // input instead of returning null — that would turn a bad payload into a
        // 500 rather than the 400 we want to answer Flutterwave with.
        $payload = \json_decode($request->getContent(), true);

        if (!is_array($payload)) {
            return response()->json(['status' => 'error', 'message' => 'Malformed payload'], 400);
        }

        $event = $payload['event'] ?? null;
        $data  = $payload['data'] ?? [];

        // Flutterwave retries until it gets a 2xx, so acknowledge events we do
        // not act on rather than leaving them to be redelivered forever.
        if ($event !== 'charge.completed') {
            return response()->json(['status' => 'success', 'message' => 'Event ignored']);
        }

        $transactionId = $data['id'] ?? null;
        if (!$transactionId) {
            return response()->json(['status' => 'error', 'message' => 'No transaction id'], 400);
        }

        // Re-verify with Flutterwave. The webhook payload alone is not
        // authoritative for amount or status.
        $response = Http::withToken($class->getSetting('flutterwave.secret_key'))
            ->get("https://api.flutterwave.com/v3/transactions/{$transactionId}/verify");

        $verified = $response->json();

        if (!$response->successful() || ($verified['status'] ?? null) !== 'success') {
            Log::warning('Flutterwave webhook: transaction verification failed.', [
                'transaction_id' => $transactionId,
                'response'       => $verified,
            ]);
            return response()->json(['status' => 'error', 'message' => 'Verification failed'], 400);
        }

        $transaction = $verified['data'] ?? [];

        if (($transaction['status'] ?? null) !== 'successful') {
            return response()->json(['status' => 'success', 'message' => 'Payment not successful']);
        }

        $module = $transaction['meta']['module'] ?? ($data['meta']['module'] ?? 'loan');
        $resourceId = $transaction['tx_ref'] ?? null;

        if (!$resourceId) {
            return response()->json(['status' => 'error', 'message' => 'No tx_ref'], 400);
        }

        $gateway = new Flutterwave($resourceId, $module);
        $gateway->processPayment([
            'module'    => $module,
            'loan_id'   => $resourceId,
            'amount'    => (float) ($transaction['amount'] ?? 0),
            'reference' => (string) ($transaction['flw_ref'] ?? $transactionId),
        ]);

        return response()->json(['status' => 'success', 'message' => 'Payment processed']);
    }
}
