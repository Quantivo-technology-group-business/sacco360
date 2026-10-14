<?php

namespace Modules\Sacco\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Sacco\Entities\SaccoMpesaC2bTransaction;
use Modules\ActivityLog\Services\AuditLogger;
use Modules\Sacco\Services\MpesaC2bService;

class MpesaC2bController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', '2fa'])->only(['index']);
        $this->middleware(['permission:sacco.mpesa_c2b.index'])->only(['index']);
    }

    public function validate(Request $request, MpesaC2bService $service)
    {
        $result = $service->validate($request->all());
        return response()->json([
            'ResultCode' => $result['ResultCode'],
            'ResultDesc' => $result['ResultDesc'],
        ]);
    }

    public function confirm(Request $request, MpesaC2bService $service)
    {
        $record = $service->confirm($request->all());
        AuditLogger::log('M-Pesa C2B Received', $record, ['id' => $record->id, 'amount' => $record->TransAmount ?? null, 'msisdn' => $record->MSISDN ?? null, 'status' => $record->status], 'sacco', 'create');
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');
        $response->setContent(json_encode([
            'ResultCode' => $record->status === 'posted' ? 0 : 1,
            'ResultDesc' => $record->response_message,
        ]));
        return $response;
    }

    public function index()
    {
        $transactions = SaccoMpesaC2bTransaction::orderByDesc('id')->paginate(30);
        return theme_view('sacco::mpesa_c2b.index', compact('transactions'));
    }

    /**
     * Daraja STK callback for a chama group contribution.
     *
     * Matched on CheckoutRequestID, which MpesaStkService::pushGroupContribution()
     * persisted on the contribution at push time. Idempotent — Safaricom retries
     * callbacks, and a repeat delivery must not double-post a contribution.
     *
     * Always answers 200: returning an error makes Daraja keep retrying a
     * callback we have already handled or can never handle.
     */
    public function stkGroupContribution(Request $request, \Modules\Sacco\Services\GroupService $groupService)
    {
        $callback = $request->input('Body.stkCallback', []);

        $checkoutRequestId = $callback['CheckoutRequestID'] ?? null;
        $resultCode        = $callback['ResultCode'] ?? null;
        $resultDesc        = $callback['ResultDesc'] ?? null;

        if (!$checkoutRequestId) {
            return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Ignored: no CheckoutRequestID']);
        }

        // Non-zero ResultCode means the member cancelled, timed out, or had
        // insufficient funds. Leave the contribution 'pending' so an officer can
        // still confirm it manually if the money in fact arrived.
        if ((int) $resultCode !== 0) {
            \Modules\Sacco\Entities\SaccoGroupContribution::where('checkout_request_id', $checkoutRequestId)
                ->where('status', 'pending')
                ->update(['rejection_reason' => 'M-Pesa STK not completed: ' . $resultDesc]);

            return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
        }

        // The callback URL is unauthenticated, so the payload is spoofable.
        // Confirm the checkout against Daraja before posting anything: a forged
        // callback carrying a guessed CheckoutRequestID must not credit a member.
        $stk = (new \Modules\Sacco\Services\MpesaStkService())->queryStkStatus($checkoutRequestId);

        if (!$stk['verified']) {
            \Modules\Sacco\Entities\SaccoGroupContribution::where('checkout_request_id', $checkoutRequestId)
                ->where('status', 'pending')
                ->update(['rejection_reason' => 'Callback could not be verified with Daraja: ' . $stk['result_desc']]);

            \Illuminate\Support\Facades\Log::warning('Chama STK callback failed Daraja verification.', [
                'checkout_request_id' => $checkoutRequestId,
                'result'              => $stk,
            ]);

            // Still 200: a genuine-but-unverifiable callback should not be
            // retried forever, and the contribution stays pending for an officer.
            return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted, pending verification']);
        }

        $items = $callback['CallbackMetadata']['Item'] ?? [];
        $amount = null;
        $receipt = null;

        foreach ($items as $item) {
            if (($item['Name'] ?? null) === 'Amount') {
                $amount = (float) ($item['Value'] ?? 0);
            }
            if (($item['Name'] ?? null) === 'MpesaReceiptNumber') {
                $receipt = $item['Value'] ?? null;
            }
        }

        $contribution = $groupService->confirmMpesaContribution($checkoutRequestId, (string) $receipt, $amount);

        if ($contribution) {
            AuditLogger::log(
                'Chama Contribution via M-Pesa STK',
                $contribution,
                [
                    'id'                 => $contribution->id,
                    'sacco_group_id'     => $contribution->sacco_group_id,
                    'amount'             => $contribution->amount,
                    'status'             => $contribution->status,
                    'mpesa_receipt'      => $receipt,
                    'checkout_request_id'=> $checkoutRequestId,
                ],
                'sacco',
                'update'
            );
        }

        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }
}
