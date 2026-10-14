<?php

namespace Modules\Portal\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\User\Services\AuthorizationService;

class EnsurePortalPermission
{
    protected AuthorizationService $authService;

    public function __construct(AuthorizationService $authService)
    {
        $this->authService = $authService;
    }

    public function handle(Request $request, Closure $next, ?string $permission = null)
    {
        $user = $request->user();

        if (!$user) {
            return redirect('login');
        }

        if (!$user->hasRole('client')) {
            abort(403, 'Portal access requires client role.');
        }

        $required = $permission ?: $this->resolvePermissionFromRoute($request);

        if ($required && !$this->authService->checkPermission($user, $required)) {
            abort(403, 'You do not have permission to access this portal feature.');
        }

        return $next($request);
    }

    protected function resolvePermissionFromRoute(Request $request): ?string
    {
        $routeName = $request->route() ? $request->route()->getName() : null;
        if ($routeName && Str::startsWith($routeName, 'portal.')) {
            return $routeName;
        }

        $path = trim($request->path(), '/');

        $map = [
            'portal' => 'portal.dashboard.index',
            'portal/dashboard' => 'portal.dashboard.index',
            'portal/loan' => 'portal.loans.index',
            'portal/loan/application' => 'portal.loans.apply',
            'portal/savings' => 'portal.savings.index',
            'portal/share' => 'portal.shares.index',
            'portal/share/marketplace' => 'portal.shares.marketplace',
            'portal/share/marketplace/listing/create' => 'portal.shares.list',
            'portal/share/marketplace/listing/store' => 'portal.shares.list',
            'portal/share/marketplace/listings' => 'portal.shares.list',
            'portal/share/marketplace/direct/create' => 'portal.shares.transfer',
            'portal/share/marketplace/direct/store' => 'portal.shares.transfer',
            'portal/client' => 'portal.client.index',
            'portal/wallet' => 'portal.wallet.index',
            'portal/sacco/accounts' => 'portal.sacco.accounts',
            'portal/sacco/statements' => 'portal.sacco.statements',
            'portal/sacco/earmarks' => 'portal.sacco.earmarks',
            'portal/sacco/transfer' => 'portal.sacco.transfer',
            'portal/sacco/transfers' => 'portal.sacco.transfer',
            'portal/sacco/member-transfer' => 'portal.sacco.member_transfer',
            'portal/sacco/standing_order' => 'portal.sacco.standing_order',
            'portal/sacco/pesalink' => 'portal.sacco.pesalink',
            'portal/sacco/beneficiaries' => 'portal.sacco.beneficiaries',
            'portal/sacco/mpesa' => 'portal.sacco.mpesa',
            'portal/sacco/utility' => 'portal.sacco.utility',
            'portal/sacco/cheques' => 'portal.sacco.cheques',
            'portal/sacco/kyc' => 'portal.sacco.kyc',
            'portal/sacco/cards' => 'portal.sacco.cards',
            'portal/sacco/deposit' => 'portal.sacco.deposit',
            'portal/sacco/fixed_deposit' => 'portal.sacco.fixed_deposit',
            'portal/sacco/dividends' => 'portal.sacco.dividends',
            'portal/sacco/checkoff' => 'portal.sacco.checkoff',
            'portal/sacco/atm' => 'portal.sacco.atm',
            'portal/sacco/exit' => 'portal.sacco.exit',
            'portal/sacco/otp' => 'portal.sacco.transfer',
        ];

        foreach ($map as $prefix => $permission) {
            if ($path === $prefix || strpos($path, $prefix . '/') === 0) {
                return $permission;
            }
        }

        return 'portal.dashboard.index';
    }
}
