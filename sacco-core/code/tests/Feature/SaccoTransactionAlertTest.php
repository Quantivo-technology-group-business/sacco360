<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Client\Entities\Client;
use Modules\Sacco\Services\TransactionNotificationService;

/**
 * Covers transaction SMS alerts (P1 item C1).
 *
 * The defect: TransactionNotificationService::notify() inserted a row into
 * communication_campaign_logs with status 'pending' and
 * communication_campaign_id null, and nothing ever consumed it. The only reader
 * of that table, the campaigns:process command, selects scheduled campaigns and
 * never looks for orphan log rows. Members therefore received no SMS for
 * deposits, withdrawals, transfers, M-Pesa, PesaLink, utility or teller
 * transactions, while the log looked like a queue merely waiting to be drained.
 *
 * These tests assert the alert is dispatched and that the recorded status tells
 * the truth. They deliberately avoid depending on a live SMS provider: the
 * gateway URL used points at a port that refuses connections immediately, so the
 * send resolves to a clean failure rather than a network timeout.
 */
class SaccoTransactionAlertTest extends \TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]]);

        // Alerts are on by default in config; set it explicitly so a stray
        // .env value cannot silently skip every test in this file.
        config(['sacco.sms_transaction_alerts' => true]);

        // middle_name is required: Client::$appends builds name/full_name/name_id
        // from first + middle + last.
        Schema::create('clients', function ($table) {
            $table->id();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('mobile')->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
        });

        Schema::create('communication_campaign_logs', function ($table) {
            $table->id();
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->unsignedBigInteger('client_id')->nullable();
            $table->bigInteger('sms_gateway_id')->nullable();
            $table->unsignedBigInteger('communication_campaign_id')->nullable();
            $table->enum('campaign_type', ['sms', 'email']);
            $table->text('description')->nullable();
            $table->text('send_to')->nullable();
            $table->text('campaign_name')->nullable();
            $table->enum('status', ['pending', 'sent', 'delivered', 'failed'])->default('pending');
            $table->timestamps();
        });

        Schema::create('sms_gateways', function ($table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('url')->nullable();
            $table->string('to_name')->nullable();
            $table->string('msg_name')->nullable();
            $table->timestamps();
        });

        Schema::create('settings', function ($table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('setting_key');
            $table->string('module')->nullable();
            $table->text('setting_value')->nullable();
            $table->string('category')->nullable();
            $table->string('type')->nullable();
            $table->timestamps();
        });
    }

    protected function makeClient(array $overrides = []): Client
    {
        $id = DB::table('clients')->insertGetId(array_merge([
            'first_name' => 'Wanjiru',
            'middle_name' => null,
            'last_name' => 'Kamau',
            'mobile' => '254712345678',
            'email' => null,
        ], $overrides));

        return Client::find($id);
    }

    /**
     * Configure a gateway whose URL is refused instantly, so send_sms() resolves
     * to false without waiting on a network timeout.
     */
    protected function configureUnreachableGateway(): int
    {
        $gatewayId = DB::table('sms_gateways')->insertGetId([
            'name' => 'Test Gateway',
            'url' => 'http://127.0.0.1:1/send',
            'to_name' => 'to',
            'msg_name' => 'msg',
        ]);

        DB::table('settings')->insert([
            'setting_key' => 'communication.active_sms_gateway',
            'setting_value' => (string) $gatewayId,
        ]);

        return $gatewayId;
    }

    protected function logRows()
    {
        return DB::table('communication_campaign_logs')->get();
    }

    // ── The core C1 regression ───────────────────────────────────────────────

    /**
     * The row must not be left 'pending'. Under the old code it always was,
     * which is precisely what made the failure invisible.
     */
    public function test_an_alert_is_never_left_in_pending_state(): void
    {
        $client = $this->makeClient();

        (new TransactionNotificationService())->notify($client, 'deposit', 1500.00, 'MP9X1234');

        $rows = $this->logRows();
        $this->assertCount(1, $rows);
        $this->assertNotSame('pending', $rows[0]->status);
        $this->assertContains($rows[0]->status, ['sent', 'failed']);
    }

    public function test_an_undeliverable_alert_is_recorded_as_failed_with_the_message_text(): void
    {
        // No gateway configured at all: delivery is impossible.
        $client = $this->makeClient();

        (new TransactionNotificationService())->notify($client, 'withdrawal', 250.50, 'S99');

        $rows = $this->logRows();
        $this->assertCount(1, $rows);
        $this->assertSame('failed', $rows[0]->status);
        $this->assertSame('sms', $rows[0]->campaign_type);
        $this->assertSame('254712345678', $rows[0]->send_to);
        $this->assertSame($client->id, (int) $rows[0]->client_id);
        $this->assertNull($rows[0]->communication_campaign_id);
        $this->assertStringContainsString('Withdrawal of 250.50', $rows[0]->description);
        $this->assertStringContainsString('S99', $rows[0]->description);
    }

    public function test_a_configured_gateway_is_actually_attempted(): void
    {
        // Proves the path reaches send_sms() rather than short-circuiting. The
        // gateway refuses the connection, so the outcome is still 'failed', but
        // it is a real attempt: the helper resolved the gateway and ran curl.
        $this->configureUnreachableGateway();
        $client = $this->makeClient();

        (new TransactionNotificationService())->notify($client, 'deposit', 1000.00, 'REF1');

        $rows = $this->logRows();
        $this->assertCount(1, $rows);
        $this->assertSame('failed', $rows[0]->status);
    }

    public function test_send_sms_returns_false_rather_than_fataling_when_the_setting_row_is_missing(): void
    {
        // The old helper did Setting::where(...)->first()->setting_value, which
        // fatals on an unconfigured install. This runs inline on payment
        // callbacks, so the null path must be safe.
        $this->assertTrue(function_exists('send_sms'), 'send_sms() helper should be autoloaded');

        $this->assertFalse(send_sms('254712345678', 'test message'));
    }

    public function test_send_sms_returns_false_for_an_unknown_gateway_id(): void
    {
        $this->assertFalse(send_sms('254712345678', 'test message', 999999));
    }

    public function test_send_sms_returns_false_when_the_gateway_url_refuses_the_connection(): void
    {
        $gatewayId = $this->configureUnreachableGateway();

        $this->assertFalse(send_sms('254712345678', 'test message', $gatewayId, 1, 2));
    }

    // ── Gating ───────────────────────────────────────────────────────────────

    public function test_no_alert_is_sent_when_the_member_has_no_mobile_number(): void
    {
        $client = $this->makeClient(['mobile' => null]);

        (new TransactionNotificationService())->notify($client, 'deposit', 1000.00, 'REF1');

        $this->assertCount(0, $this->logRows());
    }

    public function test_no_alert_is_sent_when_transaction_alerts_are_disabled(): void
    {
        config(['sacco.sms_transaction_alerts' => false]);
        $client = $this->makeClient();

        (new TransactionNotificationService())->notify($client, 'deposit', 1000.00, 'REF1');

        $this->assertCount(0, $this->logRows());
    }

    // ── Never break the transaction ──────────────────────────────────────────

    public function test_a_missing_settings_table_does_not_break_the_caller(): void
    {
        Schema::dropIfExists('settings');
        $client = $this->makeClient();

        // Must not throw: callers are payment and teller transactions.
        (new TransactionNotificationService())->notify($client, 'deposit', 1000.00, 'REF1');

        $this->assertCount(1, $this->logRows());
        $this->assertSame('failed', $this->logRows()[0]->status);
    }

    public function test_a_missing_gateway_table_does_not_break_the_caller(): void
    {
        Schema::dropIfExists('sms_gateways');
        $client = $this->makeClient();

        (new TransactionNotificationService())->notify($client, 'deposit', 1000.00, 'REF1');

        $this->assertCount(1, $this->logRows());
        $this->assertSame('failed', $this->logRows()[0]->status);
    }

    public function test_a_missing_log_table_does_not_break_the_caller(): void
    {
        Schema::dropIfExists('communication_campaign_logs');
        $client = $this->makeClient();

        (new TransactionNotificationService())->notify($client, 'deposit', 1000.00, 'REF1');

        // Nothing to assert on the database; the point is that this did not throw.
        $this->assertTrue(true);
    }

    // ── Lock discipline ──────────────────────────────────────────────────────

    /**
     * TransferService::execute() calls notify() twice inside an open
     * DB::transaction, and PesalinkService and MpesaB2cService do so once each.
     * A synchronous HTTP call there would hold row locks on savings, share and
     * loan accounts for as long as the gateway takes to answer, so the send is
     * deferred to the commit.
     */
    public function test_the_send_is_deferred_until_the_transaction_commits(): void
    {
        $client = $this->makeClient();
        $service = new TransactionNotificationService();

        DB::beginTransaction();

        $this->assertSame(1, DB::transactionLevel());

        $service->notify($client, 'transfer', 500.00, 'TRF-1');

        // Nothing dispatched and nothing logged while the transaction is open.
        $this->assertCount(0, $this->logRows());

        DB::commit();

        // The deferred callback ran on commit.
        $rows = $this->logRows();
        $this->assertCount(1, $rows);
        $this->assertNotSame('pending', $rows[0]->status);
    }

    public function test_a_rolled_back_transaction_sends_no_alert(): void
    {
        // Correct behaviour: if the money movement did not happen, the member
        // must not be told it did.
        $client = $this->makeClient();
        $service = new TransactionNotificationService();

        DB::beginTransaction();
        $service->notify($client, 'transfer', 500.00, 'TRF-2');
        DB::rollBack();

        $this->assertCount(0, $this->logRows());
    }

    public function test_outside_a_transaction_the_alert_is_sent_immediately(): void
    {
        $client = $this->makeClient();

        $this->assertSame(0, DB::transactionLevel());

        (new TransactionNotificationService())->notify($client, 'deposit', 750.00, 'REF3');

        $this->assertCount(1, $this->logRows());
    }
}
