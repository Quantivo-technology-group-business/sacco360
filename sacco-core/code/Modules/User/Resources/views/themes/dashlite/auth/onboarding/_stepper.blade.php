<div class="mb-4">
    <div class="d-flex justify-content-between text-center">
        <div class="flex-fill {{ ($currentStep ?? 1) >= 1 ? 'text-primary fw-bold' : 'text-muted' }}">
            <span class="badge bg-{{ ($currentStep ?? 1) >= 1 ? 'primary' : 'secondary' }}">1</span>
            <div class="small mt-1">{{ trans_choice('user::general.onboarding_step_account', 1) }}</div>
        </div>
        <div class="flex-fill {{ ($currentStep ?? 1) >= 2 ? 'text-primary fw-bold' : 'text-muted' }}">
            <span class="badge bg-{{ ($currentStep ?? 1) >= 2 ? 'primary' : 'secondary' }}">2</span>
            <div class="small mt-1">{{ trans_choice('user::general.onboarding_step_identity', 1) }}</div>
        </div>
        <div class="flex-fill {{ ($currentStep ?? 1) >= 3 ? 'text-primary fw-bold' : 'text-muted' }}">
            <span class="badge bg-{{ ($currentStep ?? 1) >= 3 ? 'primary' : 'secondary' }}">3</span>
            <div class="small mt-1">{{ trans_choice('user::general.onboarding_step_kyc', 1) }}</div>
        </div>
    </div>
</div>
