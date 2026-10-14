<?php

namespace Modules\Portal\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Portal\Http\Controllers\Concerns\ResolvesPortalClient;
use Modules\Sacco\Services\BeneficiaryService;

class SaccoBeneficiaryController extends Controller
{
    use ResolvesPortalClient;

    public function index(BeneficiaryService $service)
    {
        $this->requireSaccoModule();
        $client = $this->requireClient();
        if ($client instanceof \Illuminate\Http\RedirectResponse) {
            return $client;
        }

        $beneficiaries = $service->listForClient($client->id);

        return theme_view('portal::sacco.beneficiaries.index', compact('beneficiaries'));
    }

    public function create()
    {
        $this->requireSaccoModule();
        $client = $this->requireClient();
        if ($client instanceof \Illuminate\Http\RedirectResponse) {
            return $client;
        }

        return theme_view('portal::sacco.beneficiaries.create');
    }

    public function store(Request $request, BeneficiaryService $service)
    {
        $this->requireSaccoModule();
        $client = $this->requireClient();
        if ($client instanceof \Illuminate\Http\RedirectResponse) {
            return $client;
        }

        $request->validate([
            'nickname' => 'required|string|max:100',
            'name' => 'required|string|max:255',
            'phone' => 'required|string',
        ]);

        try {
            $service->create($client->id, $request->only(['nickname', 'name', 'phone']));
            return redirect('portal/sacco/beneficiaries')->with('success', trans('sacco::general.beneficiary_saved'));
        } catch (\Exception $e) {
            return redirect('portal/sacco/beneficiaries/create')->with('error', $e->getMessage());
        }
    }

    public function destroy($id, BeneficiaryService $service)
    {
        $this->requireSaccoModule();
        $client = $this->requireClient();
        if ($client instanceof \Illuminate\Http\RedirectResponse) {
            return $client;
        }

        try {
            $service->deactivate($client->id, (int)$id);
            return redirect('portal/sacco/beneficiaries')->with('success', trans('sacco::general.beneficiary_removed'));
        } catch (\Exception $e) {
            return redirect('portal/sacco/beneficiaries')->with('error', $e->getMessage());
        }
    }
}
