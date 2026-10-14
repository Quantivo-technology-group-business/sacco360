<?php

namespace Modules\Sacco\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Modules\Client\Entities\Client;
use Modules\Loan\Entities\Loan;
use Modules\Sacco\Entities\UssdMemberConfig;
use Modules\Sacco\Entities\UssdSession;
use Modules\Setting\Entities\Setting;

class UssdService
{
    /** How many wrong PINs before locking (reads from settings) */
    private int $maxPinAttempts;
    /** Whether PIN is required (reads from settings) */
    private bool $requirePin;
    /** Global daily transaction limit */
    private float $dailyLimit;

    public function __construct()
    {
        $this->maxPinAttempts = (int) $this->setting('ussd.pin_attempts_max', '3');
        $this->requirePin     = $this->setting('ussd.require_pin', '1') === '1';
        $this->dailyLimit     = (float) $this->setting('ussd.daily_limit', '50000');
    }

    // ─────────────────────────────────────────────────────────────────────
    // Entry point
    // ─────────────────────────────────────────────────────────────────────

    public function handleRequest(array $input): string
    {
        $phone     = $input['phoneNumber'] ?? '';
        $text      = $input['text'] ?? '';
        $sessionId = $input['sessionId'] ?? uniqid('ussd_');

        // Check global USSD enabled
        if ($this->setting('ussd.enabled', '1') !== '1') {
            return 'END Service temporarily unavailable. Please try again later.';
        }

        $steps = explode('*', $text);
        $level = count($steps);
        $first = $steps[0] ?? '';

        // Log / update session
        $session = $this->upsertSession($sessionId, $phone, $text);

        // Main menu (no input yet)
        if ($level === 1 && $first === '') {
            return $this->mainMenu();
        }

        $choice = $first;

        // ── PIN-authenticated zone ────────────────────────────────────────
        if ($choice !== '0' && in_array($choice, ['1', '2', '3', '4', '5'])) {
            $client = $this->resolveClient($phone);

            if (!$client) {
                return $this->end($session, 'Member not registered. Visit a branch to register your mobile number.');
            }

            $config = UssdMemberConfig::firstOrNew(['client_id' => $client->id]);

            // Check USSD enabled for this member
            if (!$config->ussd_enabled) {
                return $this->end($session, 'USSD access not activated for your account. Contact your SACCO branch.');
            }

            // Locked?
            if ($config->isLocked()) {
                return $this->end($session, 'Your USSD account is locked due to too many incorrect PIN attempts. Visit branch to unlock.');
            }

            // PIN authentication (step 2 of flow = PIN entry)
            if ($this->requirePin) {
                if ($level < 2) {
                    $session->last_menu = "pin:{$choice}";
                    $session->save();
                    return 'CON Enter your 4-digit USSD PIN:';
                }

                $enteredPin = $steps[1] ?? '';
                $pinResult  = $this->verifyPin($config, $enteredPin);
                if ($pinResult !== true) {
                    return $this->end($session, $pinResult);
                }
            }

            // Update last access
            $config->last_access_at = now();
            $config->save();
            $session->client_id = $client->id;
            $session->save();

            // Adjust steps/level for authenticated zone
            $authSteps = array_merge([$choice], array_slice($steps, $this->requirePin ? 2 : 1));
            $authLevel = count($authSteps);

            return $this->routeAuthenticated($choice, $client, $config, $authSteps, $authLevel, $session);
        }

        if ($choice === '0') {
            return $this->end($session, 'Thank you for using SACCO Mobile Banking. Goodbye!');
        }

        return $this->end($session, 'Invalid option selected.');
    }

    // ─────────────────────────────────────────────────────────────────────
    // Menu
    // ─────────────────────────────────────────────────────────────────────

    private function mainMenu(): string
    {
        $welcome = $this->setting('ussd.welcome_message', 'Welcome to SACCO Mobile Banking');
        return "CON {$welcome}\n1. Balance Inquiry\n2. Mini Statement\n3. Fund Transfer\n4. Loan Services\n5. More Services\n0. Exit";
    }

    // ─────────────────────────────────────────────────────────────────────
    // Authenticated routing
    // ─────────────────────────────────────────────────────────────────────

    private function routeAuthenticated(
        string $choice,
        Client $client,
        UssdMemberConfig $config,
        array $steps,
        int $level,
        UssdSession $session
    ): string {
        $session->last_menu = $choice;
        $session->save();

        return match ($choice) {
            '1' => $this->serviceCheck($config, 'balance',
                     fn () => $this->balanceInquiry($client, $session)),
            '2' => $this->serviceCheck($config, 'statement',
                     fn () => $this->miniStatement($client, $session)),
            '3' => $this->serviceCheck($config, 'transfer',
                     fn () => $this->handleTransfer($client, $config, $steps, $level, $session)),
            '4' => $this->serviceCheck($config, 'loan',
                     fn () => $this->handleLoanServices($client, $steps, $level, $session)),
            '5' => $this->moreServices($client, $config, $steps, $level, $session),
            default => $this->end($session, 'Invalid option.'),
        };
    }

    private function serviceCheck(UssdMemberConfig $config, string $service, callable $handler): string
    {
        if (!$config->isServiceAllowed($service)) {
            return 'END This service is not enabled on your account. Contact your branch.';
        }
        return $handler();
    }

    // ─────────────────────────────────────────────────────────────────────
    // Services
    // ─────────────────────────────────────────────────────────────────────

    private function balanceInquiry(Client $client, UssdSession $session): string
    {
        try {
            $wing    = new WingService();
            $fosa    = $wing->getSavingsBalanceByWing($client->id, WingService::WING_FOSA);
            $bosa    = $wing->getSavingsBalanceByWing($client->id, WingService::WING_BOSA);
            $summary = (new Member360Service())->getSummary($client->id);
            return $this->end($session, implode("\n", [
                "Account: {$client->account_number}",
                "FOSA Balance: " . number_format($fosa, 2),
                "BOSA Balance: " . number_format($bosa, 2),
                "Shares Value: " . number_format($summary['share_value'] ?? 0, 2),
                "Loan Balance: " . number_format($summary['loan_balance'] ?? 0, 2),
            ]));
        } catch (\Throwable $e) {
            return $this->end($session, 'Unable to retrieve balance. Try again later.');
        }
    }

    private function miniStatement(Client $client, UssdSession $session): string
    {
        try {
            $fosaAccount = (new WingService())->getFosaAccount($client->id);
            $query = DB::table('savings_transactions')
                ->join('savings', 'savings.id', '=', 'savings_transactions.savings_id')
                ->where('savings.client_id', $client->id)
                ->when($fosaAccount, fn ($q) => $q->where('savings_transactions.savings_id', $fosaAccount->id))
                ->where('savings_transactions.status', '!=', 'declined')
                ->orderByDesc('savings_transactions.id')
                ->limit(5)
                ->select('savings_transactions.name', 'savings_transactions.amount', 'savings_transactions.submitted_on')
                ->get();

            if ($query->isEmpty()) {
                return $this->end($session, 'No recent transactions found.');
            }

            $lines = ["Last {$query->count()} transactions:"];
            foreach ($query as $t) {
                $lines[] = "{$t->submitted_on}: {$t->name} " . number_format($t->amount, 2);
            }
            return $this->end($session, implode("\n", $lines));
        } catch (\Throwable $e) {
            return $this->end($session, 'Unable to retrieve statement. Try again later.');
        }
    }

    private function handleTransfer(
        Client $client,
        UssdMemberConfig $config,
        array $steps,
        int $level,
        UssdSession $session
    ): string {
        if ($level < 2) {
            return "CON Transfer type:\n1. FOSA to Loan Repayment\n2. BOSA to FOSA\n0. Back";
        }

        $type = $steps[1] ?? '';
        if ($type === '0') {
            return $this->mainMenu();
        }

        if ($level < 3) {
            return $type === '1' ? 'CON Enter loan ID:' : 'CON Enter amount (BOSA → FOSA):';
        }

        $amount = (float)($steps[2] ?? 0);

        // Enforce daily limit
        $limit = $config->daily_transaction_limit ?? $this->dailyLimit;
        $usedToday = $this->dailySpend($client->id);
        if ($amount > 0 && ($usedToday + $amount) > $limit) {
            return $this->end($session, 'Daily transaction limit of ' . number_format($limit, 2) . ' exceeded.');
        }

        try {
            $wing = new WingService();

            if ($type === '1') {
                $loanId = (int)$steps[2];
                if ($level < 4) {
                    return 'CON Enter repayment amount:';
                }
                $amount = (float)($steps[3] ?? 0);
                $loan   = Loan::where('id', $loanId)->where('client_id', $client->id)->first();
                if (!$loan) {
                    return $this->end($session, 'Loan not found.');
                }
                $fosa = $wing->getFosaAccount($client->id);
                if (!$fosa) {
                    return $this->end($session, 'FOSA account not found.');
                }
                (new TransferService())->execute([
                    'client_id' => $client->id, 'branch_id' => $fosa->branch_id,
                    'transfer_type' => 'savings_to_loan',
                    'source_id' => $fosa->id, 'destination_id' => $loan->id, 'amount' => $amount,
                ]);
                return $this->end($session, "Loan repayment of " . number_format($amount, 2) . " posted successfully.");
            }

            $bosa = $wing->getBosaAccount($client->id);
            $fosa = $wing->getFosaAccount($client->id);
            if (!$bosa || !$fosa) {
                return $this->end($session, 'BOSA or FOSA account not found.');
            }
            (new TransferService())->execute([
                'client_id' => $client->id, 'branch_id' => $bosa->branch_id,
                'transfer_type' => 'savings_to_savings',
                'source_id' => $bosa->id, 'destination_id' => $fosa->id, 'amount' => $amount,
            ]);
            return $this->end($session, "Transferred " . number_format($amount, 2) . " from BOSA to FOSA.");
        } catch (\Throwable $e) {
            return $this->end($session, 'Transfer failed: ' . $e->getMessage());
        }
    }

    private function handleLoanServices(
        Client $client,
        array $steps,
        int $level,
        UssdSession $session
    ): string {
        if ($level < 2) {
            return "CON Loan Services:\n1. Check Loan Balance\n2. Apply for Loan\n0. Back";
        }

        $sub = $steps[1] ?? '';
        if ($sub === '0') {
            return $this->mainMenu();
        }

        if ($sub === '1') {
            $loans = Loan::where('client_id', $client->id)
                ->whereIn('status', ['active', 'disbursed'])
                ->get(['id', 'principal', 'status']);
            if ($loans->isEmpty()) {
                return $this->end($session, 'No active loans found.');
            }
            $lines = ['Active Loans:'];
            foreach ($loans as $loan) {
                $lines[] = "Loan #{$loan->id}: " . number_format($loan->principal, 2);
            }
            return $this->end($session, implode("\n", $lines));
        }

        if ($sub === '2') {
            if ($level < 3) {
                return 'CON Enter loan amount to apply for:';
            }
            $amount = (float)($steps[2] ?? 0);
            if ($amount <= 0) {
                return $this->end($session, 'Invalid amount entered.');
            }
            // Queue the loan application
            DB::table('loan_applications')->insert([
                'branch_id'       => $client->branch_id,
                'client_id'       => $client->id,
                'loan_product_id' => DB::table('loan_products')->where('active', 1)->value('id') ?? 1,
                'amount'          => $amount,
                'status'          => 'pending',
                'notes'           => 'USSD application via ' . $client->mobile,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
            return $this->end($session, "Loan application for " . number_format($amount, 2) . " received. We will contact you shortly.");
        }

        return $this->end($session, 'Invalid option.');
    }

    private function moreServices(
        Client $client,
        UssdMemberConfig $config,
        array $steps,
        int $level,
        UssdSession $session
    ): string {
        if ($level < 2) {
            $menu = "CON More Services:\n";
            if ($config->isServiceAllowed('utility')) {
                $menu .= "1. Pay Utility Bill\n";
            }
            $menu .= "2. Change USSD PIN\n0. Back";
            return $menu;
        }

        $sub = $steps[1] ?? '';
        if ($sub === '0') {
            return $this->mainMenu();
        }

        if ($sub === '1' && $config->isServiceAllowed('utility')) {
            if ($level < 3) {
                $providers = DB::table('sacco_utility_providers')->where('active', 1)->pluck('name', 'id');
                $list = "CON Select provider:\n";
                foreach ($providers as $id => $name) {
                    $list .= "{$id}. {$name}\n";
                }
                return rtrim($list);
            }
            return 'END Utility payments via USSD — coming soon.';
        }

        if ($sub === '2') {
            // PIN change flow
            if ($level < 3) {
                return 'CON Enter current PIN:';
            }
            $currentPin = $steps[2] ?? '';
            if ($this->requirePin && !Hash::check($currentPin, $config->pin_hash ?? '')) {
                return $this->end($session, 'Incorrect current PIN. PIN change cancelled.');
            }
            if ($level < 4) {
                return 'CON Enter new 4-digit PIN:';
            }
            if ($level < 5) {
                return 'CON Confirm new PIN:';
            }
            $newPin     = $steps[3] ?? '';
            $confirmPin = $steps[4] ?? '';
            if ($newPin !== $confirmPin || strlen($newPin) !== 4 || !ctype_digit($newPin)) {
                return $this->end($session, 'PINs do not match or invalid. Please try again.');
            }
            $config->pin_hash = Hash::make($newPin);
            $config->pin_set  = 1;
            $config->save();
            return $this->end($session, 'PIN changed successfully. Keep it confidential.');
        }

        return $this->end($session, 'Invalid option.');
    }

    // ─────────────────────────────────────────────────────────────────────
    // PIN verification
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @return true|string  true on success, error string on failure
     */
    private function verifyPin(UssdMemberConfig $config, string $enteredPin): bool|string
    {
        if (!$config->pin_set || !$config->pin_hash) {
            return 'No PIN set for your account. Visit a branch to set a PIN.';
        }

        if (!Hash::check($enteredPin, $config->pin_hash)) {
            $config->failed_pin_attempts++;
            if ($config->failed_pin_attempts >= $this->maxPinAttempts) {
                $config->locked_at = now();
                $config->save();
                return "Incorrect PIN. Account locked after {$this->maxPinAttempts} attempts. Visit branch to unlock.";
            }
            $remaining = $this->maxPinAttempts - $config->failed_pin_attempts;
            $config->save();
            return "Incorrect PIN. {$remaining} attempt(s) remaining.";
        }

        // Correct PIN — reset failed attempts
        $config->failed_pin_attempts = 0;
        $config->save();
        return true;
    }

    // ─────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────

    private function upsertSession(string $sessionId, string $phone, string $text): UssdSession
    {
        $session = UssdSession::firstOrNew(['session_id' => $sessionId]);
        $session->phone_number  = $phone;
        $session->session_text  = $text;
        $session->status        = 'active';
        if (!$session->exists || !$session->started_at) {
            $session->started_at = now();
        }
        $session->save();
        return $session;
    }

    private function end(UssdSession $session, string $message): string
    {
        $session->status    = 'completed';
        $session->ended_at  = now();
        $session->save();
        return 'END ' . $message;
    }

    private function resolveClient(string $phone): ?Client
    {
        $normalized = substr(preg_replace('/\D/', '', $phone), -9);
        return Client::where('mobile', 'like', "%{$normalized}")->first();
    }

    private function dailySpend(int $clientId): float
    {
        return (float) DB::table('sacco_transfers')
            ->where('client_id', $clientId)
            ->whereDate('created_at', today())
            ->sum('amount');
    }

    private function setting(string $key, string $default = ''): string
    {
        static $cache = [];
        if (!isset($cache[$key])) {
            $cache[$key] = \Modules\Setting\Entities\Setting::where('setting_key', $key)->value('setting_value') ?? $default;
        }
        return $cache[$key];
    }
}
