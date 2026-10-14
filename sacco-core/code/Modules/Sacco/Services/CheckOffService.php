<?php

namespace Modules\Sacco\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Client\Entities\Client;
use Modules\Core\Entities\PaymentDetail;
use Modules\Loan\Entities\Loan;
use Modules\Loan\Entities\LoanTransaction;
use Modules\Loan\Events\TransactionUpdated as LoanTransactionUpdated;
use Modules\Sacco\Entities\SaccoCheckoffBatch;
use Modules\Sacco\Entities\SaccoCheckoffItem;
use Modules\Savings\Entities\Savings;
use Modules\Savings\Entities\SavingsTransaction;
use Modules\Savings\Events\TransactionUpdated as SavingsTransactionUpdated;
use Modules\Share\Entities\Share;
use Modules\Share\Entities\ShareTransaction;

class CheckOffService
{
    public function processUpload(SaccoCheckoffBatch $batch, array $rows): SaccoCheckoffBatch
    {
        $batch->status = 'processing';
        $batch->total_records = count($rows);
        $batch->save();

        $matched = 0;
        $unmatched = 0;
        $totalAmount = 0;

        foreach ($rows as $row) {
            $item = new SaccoCheckoffItem();
            $item->sacco_checkoff_batch_id = $batch->id;
            $item->employee_id = $row['employee_id'] ?? null;
            $item->employee_name = $row['employee_name'] ?? null;
            $item->deduction_type = $row['deduction_type'] ?? 'loan';
            $item->loan_account_number = $row['loan_account_number'] ?? $row['loan_id'] ?? null;
            $item->amount = (float)($row['amount'] ?? 0);
            $item->status = 'pending';
            $totalAmount += $item->amount;

            $client = null;
            if (!empty($item->employee_id)) {
                $client = Client::where('external_id', $item->employee_id)
                    ->orWhere('member_number', $item->employee_id)
                    ->orWhere('employer_id', $item->employee_id)
                    ->first();
                if ($client) {
                    $item->client_id = $client->id;
                }
            }

            $matchedItem = $this->matchItem($item, $client);
            if ($matchedItem) {
                $item->status = 'matched';
                $matched++;
            } else {
                $item->status = 'failed';
                $item->error_message = $item->error_message ?? 'Could not match deduction target';
                $unmatched++;
            }
            $item->save();
        }

        $batch->matched_records = $matched;
        $batch->unmatched_records = $unmatched;
        $batch->total_amount = $totalAmount;
        $batch->status = 'reconciled';
        $batch->save();

        return $batch;
    }

    protected function matchItem(SaccoCheckoffItem $item, ?Client $client): bool
    {
        switch ($item->deduction_type) {
            case 'bosa_savings':
                if (!$client) {
                    $item->error_message = 'Employee not found';
                    return false;
                }
                $bosa = (new WingService())->getBosaAccount($client->id);
                if (!$bosa) {
                    $item->error_message = 'No BOSA account for member';
                    return false;
                }
                $item->client_id = $client->id;
                $item->savings_id = $bosa->id;
                return true;

            case 'share':
                if (!$client) {
                    $item->error_message = 'Employee not found';
                    return false;
                }
                $share = Share::where('client_id', $client->id)->where('status', 'active')->first();
                if (!$share) {
                    $item->error_message = 'No active share account for member';
                    return false;
                }
                $item->client_id = $client->id;
                $item->share_id = $share->id;
                return true;

            case 'loan':
            default:
                $loan = null;
                if (!empty($item->loan_account_number)) {
                    $loan = Loan::find($item->loan_account_number);
                    if (!$loan) {
                        $loan = Loan::where('id', $item->loan_account_number)->first();
                    }
                }
                if (!$loan && $client) {
                    $loan = Loan::where('client_id', $client->id)
                        ->whereIn('status', ['active', 'disbursed'])
                        ->first();
                    $item->client_id = $client->id;
                }
                if ($loan) {
                    $item->loan_id = $loan->id;
                    $item->client_id = $loan->client_id;
                    return true;
                }
                $item->error_message = 'Could not match loan account';
                return false;
        }
    }

    public function postBatch(SaccoCheckoffBatch $batch): void
    {
        DB::transaction(function () use ($batch) {
            $items = SaccoCheckoffItem::where('sacco_checkoff_batch_id', $batch->id)
                ->where('status', 'matched')
                ->get();

            foreach ($items as $item) {
                switch ($item->deduction_type) {
                    case 'bosa_savings':
                        $this->postBosaSavings($item, $batch);
                        break;
                    case 'share':
                        $this->postShare($item, $batch);
                        break;
                    default:
                        $this->postLoanRepayment($item, $batch);
                }
            }
        });
    }

    protected function postLoanRepayment(SaccoCheckoffItem $item, SaccoCheckoffBatch $batch): void
    {
        $loan = Loan::find($item->loan_id);
        if (!$loan) {
            return;
        }

        $paymentDetail = new PaymentDetail();
        $paymentDetail->created_by_id = Auth::id();
        $paymentDetail->payment_type_id = 1;
        $paymentDetail->transaction_type = 'checkoff';
        $paymentDetail->description = 'Check-off remittance - ' . $batch->employer_name;
        $paymentDetail->save();

        $repayment = new LoanTransaction();
        $repayment->created_by_id = Auth::id();
        $repayment->loan_id = $loan->id;
        $repayment->branch_id = $loan->branch_id;
        $repayment->payment_detail_id = $paymentDetail->id;
        $repayment->name = 'Check-Off Repayment';
        $repayment->loan_transaction_type_id = 2;
        $repayment->submitted_on = $batch->upload_date;
        $repayment->created_on = date('Y-m-d');
        $repayment->amount = $item->amount;
        $repayment->credit = $item->amount;
        $repayment->save();

        $item->status = 'posted';
        $item->save();
        event(new LoanTransactionUpdated($loan));
    }

    protected function postBosaSavings(SaccoCheckoffItem $item, SaccoCheckoffBatch $batch): void
    {
        $savings = Savings::find($item->savings_id);
        if (!$savings) {
            return;
        }

        $paymentDetail = new PaymentDetail();
        $paymentDetail->created_by_id = Auth::id();
        $paymentDetail->payment_type_id = 1;
        $paymentDetail->transaction_type = 'checkoff';
        $paymentDetail->description = 'BOSA check-off - ' . $batch->employer_name;
        $paymentDetail->save();

        $deposit = new SavingsTransaction();
        $deposit->created_by_id = Auth::id();
        $deposit->savings_id = $savings->id;
        $deposit->branch_id = $savings->branch_id;
        $deposit->payment_detail_id = $paymentDetail->id;
        $deposit->name = 'Payroll Check-Off BOSA Deposit';
        $deposit->savings_transaction_type_id = 1;
        $deposit->submitted_on = $batch->upload_date;
        $deposit->created_on = date('Y-m-d');
        $deposit->amount = $item->amount;
        $deposit->credit = $item->amount;
        $deposit->save();

        $item->status = 'posted';
        $item->save();
        event(new SavingsTransactionUpdated($savings));
    }

    protected function postShare(SaccoCheckoffItem $item, SaccoCheckoffBatch $batch): void
    {
        $share = Share::with('share_product')->find($item->share_id);
        if (!$share || !$share->share_product) {
            return;
        }

        $nominalPrice = $share->share_product->nominal_price ?: 1;
        $sharesToAdd = (int)floor($item->amount / $nominalPrice);
        if ($sharesToAdd <= 0) {
            $item->status = 'failed';
            $item->error_message = 'Amount too small for share purchase';
            $item->save();
            return;
        }

        $shareTxn = new ShareTransaction();
        $shareTxn->created_by_id = Auth::id();
        $shareTxn->share_id = $share->id;
        $shareTxn->branch_id = $share->branch_id;
        $shareTxn->name = 'Payroll Check-Off Share Purchase';
        $shareTxn->share_transaction_type_id = 1;
        $shareTxn->submitted_on = $batch->upload_date;
        $shareTxn->created_on = date('Y-m-d');
        $shareTxn->amount = $item->amount;
        $shareTxn->credit = $sharesToAdd;
        $shareTxn->save();

        $share->total_shares += $sharesToAdd;
        $share->save();

        $item->status = 'posted';
        $item->save();
    }

    public function syncFromPayroll(int $payrollRunId, string $employerName, string $period): SaccoCheckoffBatch
    {
        if (!class_exists(\Modules\Payroll\Entities\PayrollRun::class)) {
            throw new \RuntimeException('Payroll module not available');
        }

        $batch = new SaccoCheckoffBatch();
        $batch->created_by_id = Auth::id();
        $batch->employer_name = $employerName;
        $batch->period = $period;
        $batch->upload_date = date('Y-m-d');
        $batch->filename = 'payroll_run_' . $payrollRunId;
        $batch->save();

        $rows = DB::table('payroll_items_meta')
            ->join('payroll', 'payroll.id', '=', 'payroll_items_meta.payroll_id')
            ->join('users', 'users.id', '=', 'payroll.user_id')
            ->where('payroll.payroll_run_id', $payrollRunId)
            ->where('payroll_items_meta.type', 'deduction')
            ->whereIn('payroll_items_meta.category', ['sacco', 'loan', 'voluntary'])
            ->whereNotNull('users.client_id')
            ->select('users.client_id', 'payroll_items_meta.name', 'payroll_items_meta.computed_amount', 'payroll_items_meta.category')
            ->get()
            ->map(function ($row) {
                $client = Client::find($row->client_id);
                $deductionType = $row->category === 'sacco' ? 'bosa_savings' : ($row->category === 'loan' ? 'loan' : 'loan');
                return [
                    'employee_id' => $client->employer_id ?? $client->external_id ?? $client->member_number,
                    'employee_name' => $client ? ($client->first_name . ' ' . $client->last_name) : null,
                    'deduction_type' => $deductionType,
                    'amount' => $row->computed_amount ?? 0,
                ];
            })
            ->filter(fn($r) => $r['amount'] > 0)
            ->values()
            ->toArray();

        return $this->processUpload($batch, $rows);
    }

    public function refundItem(SaccoCheckoffItem $item): void
    {
        $item->status = 'refund';
        $item->error_message = 'Marked for refund - erroneous recovery';
        $item->save();
    }

    public function parseCsv(string $path): array
    {
        $rows = [];
        if (($handle = fopen($path, 'r')) !== false) {
            $headers = fgetcsv($handle);
            if (!$headers) {
                fclose($handle);
                return [];
            }
            $headers = array_map('strtolower', array_map('trim', $headers));
            while (($data = fgetcsv($handle)) !== false) {
                $row = array_combine($headers, $data);
                if ($row) {
                    $deductionType = strtolower($row['deduction_type'] ?? $row['type'] ?? 'loan');
                    if (in_array($deductionType, ['savings', 'bosa', 'bosa_savings'], true)) {
                        $deductionType = 'bosa_savings';
                    }
                    $rows[] = [
                        'employee_id' => $row['employee_id'] ?? $row['emp_id'] ?? $row['id'] ?? null,
                        'employee_name' => $row['employee_name'] ?? $row['name'] ?? null,
                        'deduction_type' => $deductionType,
                        'loan_account_number' => $row['loan_id'] ?? $row['loan_account'] ?? $row['account_number'] ?? null,
                        'amount' => $row['amount'] ?? $row['deduction'] ?? 0,
                    ];
                }
            }
            fclose($handle);
        }
        return $rows;
    }
}
