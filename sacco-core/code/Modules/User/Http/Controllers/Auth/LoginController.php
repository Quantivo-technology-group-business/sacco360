<?php

namespace Modules\User\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Facades\Auth;
use Laracasts\Flash\Flash;
use Modules\Client\Entities\ClientUser;
use Modules\User\Services\AuthorizationService;
use Modules\User\Services\MemberOnboardingService;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */

    protected $redirectTo = '/dashboard';

    protected MemberOnboardingService $onboarding;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(MemberOnboardingService $onboarding)
    {
        $this->middleware('guest')->except('logout');
        $this->onboarding = $onboarding;
    }

    public function showLoginForm()
    {
        return theme_view('user::auth.login');
    }

    /**
     * The user has been authenticated.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  mixed $user
     * @return mixed
     */
    protected function authenticated(Request $request, $user)
    {
        if (Auth::user()->hasRole('client')) {
            if (!app(AuthorizationService::class)->checkPermission($user, 'portal.dashboard.index')) {
                Flash::warning(trans_choice('portal::general.no_portal_access', 1));
                $this->guard()->logout();
                $request->session()->invalidate();
                return redirect('login');
            }
            $client = ClientUser::with('client')->where('user_id', Auth::id())->first();
            if (!empty($client->client)) {
                session(['client_id' => $client->client_id]);

                if ($this->onboarding->isDetailWorkflow()
                    && !$this->onboarding->isOnboardingComplete($client->client)) {
                    $resume = $this->onboarding->getOnboardingResumeUrl($client->client);
                    if ($resume) {
                        return redirect($resume);
                    }
                }

                return redirect('/portal/dashboard');
            } else {
                Flash::warning(trans_choice('portal::general.no_linked_client_found', 1));
                $this->guard()->logout();
                $request->session()->invalidate();
                return redirect('login');
            }
        } else {
            return redirect()->intended($this->redirectPath());
        }
    }
}
