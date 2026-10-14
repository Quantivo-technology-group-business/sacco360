<?php

namespace Modules\User\Services;

use Illuminate\Support\Facades\Hash;
use Modules\Client\Entities\Client;
use Modules\Client\Entities\ClientIdentification;
use Modules\Client\Entities\ClientIdentificationType;
use Modules\Client\Entities\ClientUser;
use Modules\Setting\Entities\Setting;
use Modules\User\Entities\User;
use Spatie\Permission\Models\Role;

class MemberOnboardingService
{
    public function createMemberAccount(array $data): User
    {
        $user = User::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'gender' => $data['gender'],
            'phone' => $data['phone'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'email_verified_at' => date('Y-m-d H:i:s'),
        ]);

        $role = Role::findByName('client');
        $user->assignRole($role);

        $client = new Client();
        $client->first_name = $user->first_name;
        $client->last_name = $user->last_name;
        $client->created_by_id = $user->id;
        $client->gender = $user->gender;
        $client->branch_id = $data['branch_id'];
        $client->mobile = $user->phone;
        $client->email = $user->email;
        $client->created_date = date('Y-m-d');
        $client->status = 'pending';
        if (\Schema::hasColumn('clients', 'kyc_status')) {
            $client->kyc_status = 'incomplete';
        }
        $client->save();

        if (function_exists('sacco_assign_member_number')) {
            sacco_assign_member_number($client);
        }

        $clientUser = new ClientUser();
        $clientUser->client_id = $client->id;
        $clientUser->created_by_id = $user->id;
        $clientUser->user_id = $user->id;
        $clientUser->save();

        session(['client_id' => $client->id]);

        return $user;
    }

    public function getClientForUser(User $user): ?Client
    {
        $clientId = session('client_id')
            ?? ClientUser::where('user_id', $user->id)->value('client_id');

        return $clientId ? Client::find($clientId) : null;
    }

    public function saveIdentity(Client $client, array $data): void
    {
        $client->dob = $data['dob'];
        $client->address = $data['address'] ?? null;
        if (\Schema::hasColumn('clients', 'kra_pin') && !empty($data['kra_pin'])) {
            $client->kra_pin = strtoupper($data['kra_pin']);
        }
        $client->save();

        if (!empty($data['national_id'])) {
            $typeId = $this->resolveNationalIdTypeId($data['client_identification_type_id'] ?? null);
            if (!$typeId) {
                return;
            }

            $existing = ClientIdentification::where('client_id', $client->id)
                ->where('client_identification_type_id', $typeId)
                ->first();

            if ($existing) {
                $existing->identification_value = $data['national_id'];
                $existing->save();
            } else {
                $identification = new ClientIdentification();
                $identification->created_by_id = auth()->id();
                $identification->client_id = $client->id;
                $identification->identification_value = $data['national_id'];
                $identification->client_identification_type_id = $typeId;
                $identification->save();
            }
        }
    }

    public function getKycProfile(int $clientId): array
    {
        if (!class_exists(\Modules\Sacco\Services\KycService::class)) {
            return [
                'client' => Client::findOrFail($clientId),
                'requirements' => collect(),
                'documents' => collect(),
                'verifications' => collect(),
                'checklist' => [],
                'completion_percent' => 0,
                'mandatory_met' => 0,
                'mandatory_total' => 0,
                'iprs_verified' => false,
                'iprs_required' => false,
                'can_approve' => false,
                'kyc_status' => 'incomplete',
            ];
        }

        return (new \Modules\Sacco\Services\KycService())->getMemberProfile($clientId);
    }

    public function completeOnboarding(Client $client): void
    {
        if (\Schema::hasColumn('clients', 'onboarding_completed_at')) {
            $client->onboarding_completed_at = now();
            $client->save();
        }

        if (class_exists(\Modules\Sacco\Services\KycService::class)) {
            (new \Modules\Sacco\Services\KycService())->recalculateStatus($client->id);
        }
    }

    public function isOnboardingComplete(Client $client): bool
    {
        if (!\Schema::hasColumn('clients', 'onboarding_completed_at')) {
            return false;
        }

        return !empty($client->onboarding_completed_at);
    }

    public function getRegistrationWorkflow(): string
    {
        $setting = Setting::where('setting_key', 'user.registration_workflow')->first();
        $workflow = $setting ? $setting->setting_value : 'detail';

        return in_array($workflow, ['basic', 'detail'], true) ? $workflow : 'detail';
    }

    public function isDetailWorkflow(): bool
    {
        return $this->getRegistrationWorkflow() === 'detail';
    }

    public function hasIdentityStepComplete(Client $client): bool
    {
        if (empty($client->dob)) {
            return false;
        }

        return ClientIdentification::where('client_id', $client->id)->exists();
    }

    public function getOnboardingResumeUrl(Client $client): ?string
    {
        if ($this->isOnboardingComplete($client)) {
            return null;
        }

        if (!$this->hasIdentityStepComplete($client)) {
            return '/register/identity';
        }

        return '/register/kyc';
    }

    protected function resolveNationalIdTypeId(?int $typeId): ?int
    {
        if ($typeId && ClientIdentificationType::find($typeId)) {
            return $typeId;
        }

        $type = ClientIdentificationType::where('name', 'like', '%national%')
            ->orWhere('name', 'like', '%ID%')
            ->first();

        if ($type) {
            return $type->id;
        }

        return ClientIdentificationType::value('id');
    }
}
