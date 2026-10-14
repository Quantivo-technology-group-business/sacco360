<?php

namespace Modules\Sacco\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Sacco\Entities\SaccoBorrowing;
use Modules\Sacco\Services\ComplianceAuditService;
use Modules\Sacco\Services\PrudentialRatioService;

class BorrowingController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', '2fa']);
        $this->middleware(['permission:sacco.compliance.ratios']);
    }

    public function index()
    {
        $borrowings = SaccoBorrowing::orderByDesc('disbursement_date')->get();
        $ratio = (new PrudentialRatioService())->calculateAll(date('Y-m-d'));
        $borrowingRatio = $ratio['external_borrowing'] ?? null;
        return theme_view('sacco::compliance.borrowings', compact('borrowings', 'borrowingRatio'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'lender_name' => 'required|string|max:255',
            'principal' => 'required|numeric|min:0',
            'outstanding' => 'required|numeric|min:0',
            'interest_rate' => 'nullable|numeric|min:0',
            'disbursement_date' => 'required|date',
            'maturity_date' => 'nullable|date',
            'sasra_approved' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);
        $data['created_by_id'] = Auth::id();
        $data['status'] = 'active';
        $data['sasra_approved'] = $request->boolean('sasra_approved');
        $borrowing = SaccoBorrowing::create($data);
        (new ComplianceAuditService())->log('create', 'compliance', 'borrowing', $borrowing->id, 'External borrowing recorded');
        return redirect('sacco/compliance/borrowings')->with('success', 'Borrowing record saved.');
    }
}
