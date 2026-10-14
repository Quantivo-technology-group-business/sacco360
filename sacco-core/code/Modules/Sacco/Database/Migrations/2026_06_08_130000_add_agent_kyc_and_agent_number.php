<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAgentKycAndAgentNumber extends Migration
{
    public function up()
    {
        // Add KYC fields and agent_number to sacco_agents
        Schema::table('sacco_agents', function (Blueprint $table) {
            // Agent number for customer-initiated withdrawals (short, easy to remember)
            $table->string('agent_number', 10)->nullable()->unique()->after('agent_code');

            // KYC status fields
            $table->enum('kyc_status', ['incomplete', 'pending_review', 'verified', 'rejected', 'expired'])
                ->default('incomplete')->after('status');
            $table->timestamp('kyc_verified_at')->nullable()->after('kyc_status');
            $table->unsignedBigInteger('kyc_verified_by_id')->nullable()->after('kyc_verified_at');
            $table->date('kyc_expiry_date')->nullable()->after('kyc_verified_by_id');
            $table->text('kyc_notes')->nullable()->after('kyc_expiry_date');

            $table->foreign('kyc_verified_by_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });

        // Create agent KYC documents table
        Schema::create('sacco_agent_kyc_documents', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('sacco_agent_id');
            $table->unsignedBigInteger('uploaded_by_id')->nullable();
            $table->unsignedBigInteger('reviewed_by_id')->nullable();
            $table->string('document_type', 50);
            $table->string('document_number')->nullable();
            $table->string('file_path')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'expired'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->date('expiry_date')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->foreign('sacco_agent_id')
                ->references('id')
                ->on('sacco_agents')
                ->onDelete('cascade');
            $table->foreign('uploaded_by_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
            $table->foreign('reviewed_by_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->index(['sacco_agent_id', 'document_type']);
            $table->index('status');
        });

        // Create agent KYC requirements table
        Schema::create('sacco_agent_kyc_requirements', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->string('category', 50)->default('identity');
            $table->text('description')->nullable();
            $table->boolean('is_mandatory')->default(true);
            $table->boolean('requires_verification')->default(false);
            $table->boolean('active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Seed default agent KYC requirements
        $this->seedAgentKycRequirements();

        // Generate agent numbers for existing agents
        $this->generateAgentNumbers();
    }

    public function down()
    {
        Schema::dropIfExists('sacco_agent_kyc_documents');
        Schema::dropIfExists('sacco_agent_kyc_requirements');

        Schema::table('sacco_agents', function (Blueprint $table) {
            $table->dropForeign(['kyc_verified_by_id']);
            $table->dropColumn([
                'agent_number',
                'kyc_status',
                'kyc_verified_at',
                'kyc_verified_by_id',
                'kyc_expiry_date',
                'kyc_notes',
            ]);
        });
    }

    protected function seedAgentKycRequirements()
    {
        $requirements = [
            ['code' => 'national_id', 'name' => 'National ID', 'category' => 'identity', 'is_mandatory' => true, 'requires_verification' => true, 'sort_order' => 1],
            ['code' => 'kra_pin', 'name' => 'KRA PIN Certificate', 'category' => 'tax', 'is_mandatory' => true, 'requires_verification' => false, 'sort_order' => 2],
            ['code' => 'photo', 'name' => 'Passport Photo', 'category' => 'biometric', 'is_mandatory' => true, 'requires_verification' => false, 'sort_order' => 3],
            ['code' => 'proof_of_address', 'name' => 'Proof of Address', 'category' => 'address', 'is_mandatory' => true, 'requires_verification' => false, 'sort_order' => 4],
            ['code' => 'business_permit', 'name' => 'Business Permit/License', 'category' => 'business', 'is_mandatory' => true, 'requires_verification' => false, 'sort_order' => 5],
            ['code' => 'good_conduct', 'name' => 'Certificate of Good Conduct', 'category' => 'background', 'is_mandatory' => true, 'requires_verification' => false, 'sort_order' => 6],
            ['code' => 'recommendation_letter', 'name' => 'Recommendation Letter', 'category' => 'reference', 'is_mandatory' => false, 'requires_verification' => false, 'sort_order' => 7],
            ['code' => 'bank_statement', 'name' => 'Bank Statement (3 months)', 'category' => 'financial', 'is_mandatory' => false, 'requires_verification' => false, 'sort_order' => 8],
        ];

        foreach ($requirements as $req) {
            \DB::table('sacco_agent_kyc_requirements')->insert(array_merge($req, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    protected function generateAgentNumbers()
    {
        $agents = \DB::table('sacco_agents')->whereNull('agent_number')->get();
        foreach ($agents as $agent) {
            $agentNumber = $this->generateUniqueAgentNumber();
            \DB::table('sacco_agents')
                ->where('id', $agent->id)
                ->update(['agent_number' => $agentNumber]);
        }
    }

    protected function generateUniqueAgentNumber(): string
    {
        do {
            $number = str_pad(mt_rand(1000, 9999), 4, '0', STR_PAD_LEFT);
        } while (\DB::table('sacco_agents')->where('agent_number', $number)->exists());

        return $number;
    }
}
