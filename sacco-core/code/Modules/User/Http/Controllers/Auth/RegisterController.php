<?php

namespace Modules\User\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\RegistersUsers;
use Modules\Branch\Entities\Branch;
use Modules\User\Services\MemberOnboardingService;

class RegisterController extends Controller
{
    use RegistersUsers;

    protected $redirectTo = '/register/identity';

    protected MemberOnboardingService $onboarding;

    public function __construct(MemberOnboardingService $onboarding)
    {
        $this->middleware(['guest', 'registration.enabled']);
        $this->onboarding = $onboarding;
    }

    public function showRegistrationForm()
    {
        $branches = Branch::all();
        $registrationWorkflow = $this->onboarding->getRegistrationWorkflow();

        return theme_view('user::auth.register', compact('branches', 'registrationWorkflow'));
    }

    protected function validator(array $data)
    {
        return Validator::make($data, [
            'branch_id' => ['required'],
            'first_name' => ['required'],
            'last_name' => ['required'],
            'gender' => ['required'],
            'phone' => ['required', 'numeric'],
            'agree' => ['required'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);
    }

    protected function create(array $data)
    {
        return $this->onboarding->createMemberAccount($data);
    }

    protected function registered(Request $request, $user)
    {
        if ($this->onboarding->isDetailWorkflow()) {
            return redirect('register/identity');
        }

        return redirect('portal/dashboard')
            ->with('success', trans_choice('user::general.onboarding_basic_welcome', 1));
    }
}
