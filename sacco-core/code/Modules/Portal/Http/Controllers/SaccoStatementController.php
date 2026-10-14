<?php

namespace Modules\Portal\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Loan\Entities\Loan;
use Modules\Portal\Http\Controllers\Concerns\ResolvesPortalClient;
use Modules\Sacco\Services\SavingsStatementService;
use Modules\Savings\Entities\Savings;
use PDF;

class SaccoStatementController extends Controller
{
    use ResolvesPortalClient;

    public function index()
    {
        $this->requireSaccoModule();
        $client = $this->requireClient();
        if ($client instanceof \Illuminate\Http\RedirectResponse) {
            return $client;
        }

        $savings = Savings::where('client_id', $client->id)->where('status', 'active')->get();
        $loans = Loan::where('client_id', $client->id)->get();

        return theme_view('portal::sacco.statements.index', compact('savings', 'loans'));
    }

    public function generate(Request $request, SavingsStatementService $service)
    {
        $this->requireSaccoModule();
        $client = $this->requireClient();
        if ($client instanceof \Illuminate\Http\RedirectResponse) {
            return $client;
        }

        $request->validate([
            'account_type' => 'required|in:savings,loan',
            'account_id' => 'required|integer',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'format' => 'nullable|in:view,pdf',
        ]);

        if ($request->account_type === 'savings') {
            $data = $service->getSavingsTransactions(
                (int) $request->account_id,
                $client->id,
                $request->start_date,
                $request->end_date
            );
            $start_date = $request->start_date;
            $end_date = $request->end_date;

            if ($request->format === 'pdf') {
                $pdf = PDF::loadView(theme_view_file('savings::report.account_statement_pdf'), compact('start_date', 'end_date') + [
                    'data' => $data['transactions'],
                    'savings' => $data['savings'],
                    'opening_balance' => $data['opening_balance'],
                ]);
                return $pdf->download('account_statement.pdf');
            }

            return theme_view('portal::sacco.statements.savings', compact('data', 'start_date', 'end_date'));
        }

        $data = $service->getLoanTransactions(
            (int) $request->account_id,
            $client->id,
            $request->start_date,
            $request->end_date
        );
        $start_date = $request->start_date;
        $end_date = $request->end_date;

        return theme_view('portal::sacco.statements.loan', compact('data', 'start_date', 'end_date'));
    }
}
