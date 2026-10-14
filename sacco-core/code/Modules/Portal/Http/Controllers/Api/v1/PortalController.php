<?php

namespace Modules\Portal\Http\Controllers\Api\v1;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Client\Entities\Client;
use Modules\Client\Entities\ClientUser;
use Modules\CustomField\Entities\CustomField;
use Modules\Portal\Http\Controllers\Concerns\ResolvesApiPortalClient;
use Modules\Portal\Services\PortalDashboardService;

class PortalController extends Controller
{
    use ResolvesApiPortalClient;

    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function dashboard(Request $request)
    {
        $client = $this->requireClient($request);
        if ($client instanceof \Illuminate\Http\JsonResponse) {
            return $client;
        }

        $data = (new PortalDashboardService())->getDashboardData($client->id);

        return response()->json(['data' => $data]);
    }

    public function profile(Request $request)
    {
        $client = $this->requireClient($request);
        if ($client instanceof \Illuminate\Http\JsonResponse) {
            return $client;
        }

        $linkedClients = ClientUser::with('client')->where('user_id', Auth::id())->get();
        $customFields = CustomField::where('category', 'add_client')->where('active', 1)->get();

        return response()->json([
            'data' => [
                'client' => $client,
                'linked_clients' => $linkedClients,
                'custom_fields' => $customFields,
            ],
        ]);
    }

    public function linkedClients(Request $request)
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $clients = ClientUser::with('client')->where('user_id', $user->id)->get();

        return response()->json(['data' => $clients]);
    }

    public function switchClient(Request $request)
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $request->validate(['client_id' => 'required|integer']);

        if (!ClientUser::where('user_id', $user->id)->where('client_id', $request->client_id)->exists()) {
            return response()->json(['error' => 'Access denied to this member profile'], 403);
        }

        $client = Client::find($request->client_id);

        return response()->json([
            'message' => 'Client context switched successfully',
            'data' => ['client_id' => $client->id, 'client' => $client],
        ]);
    }
}
