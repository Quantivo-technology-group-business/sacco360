<?php

namespace Modules\Sacco\Services;

use Carbon\Carbon;
use Modules\Sacco\Entities\SaccoStandingOrder;

class StandingOrderService
{
    protected $transferService;

    public function __construct(TransferService $transferService)
    {
        $this->transferService = $transferService;
    }

    public function processDueOrders(): int
    {
        $orders = SaccoStandingOrder::where('status', 'active')
            ->where('next_run_date', '<=', date('Y-m-d'))
            ->get();

        $processed = 0;
        foreach ($orders as $order) {
            try {
                if ($order->destination_loan_id) {
                    $this->transferService->execute([
                        'client_id' => $order->client_id,
                        'transfer_type' => 'savings_to_loan',
                        'source_id' => $order->source_savings_id,
                        'destination_id' => $order->destination_loan_id,
                        'amount' => $order->amount,
                        'transfer_date' => date('Y-m-d'),
                        'notes' => 'Standing order #' . $order->id,
                    ]);
                } elseif ($order->destination_savings_id) {
                    $this->transferService->execute([
                        'client_id' => $order->client_id,
                        'transfer_type' => 'savings_to_savings',
                        'source_id' => $order->source_savings_id,
                        'destination_id' => $order->destination_savings_id,
                        'amount' => $order->amount,
                        'transfer_date' => date('Y-m-d'),
                        'notes' => 'Standing order #' . $order->id,
                    ]);
                }
                $order->next_run_date = $this->nextRunDate($order);
                if ($order->end_date && $order->next_run_date > $order->end_date) {
                    $order->status = 'completed';
                }
                $order->save();
                $processed++;
            } catch (\Exception $e) {
                // log and continue
            }
        }
        return $processed;
    }

    protected function nextRunDate(SaccoStandingOrder $order): string
    {
        $current = Carbon::parse($order->next_run_date ?? $order->start_date);
        switch ($order->frequency) {
            case 'daily':
                return $current->addDay()->format('Y-m-d');
            case 'weekly':
                return $current->addWeek()->format('Y-m-d');
            default:
                return $current->addMonth()->format('Y-m-d');
        }
    }
}
