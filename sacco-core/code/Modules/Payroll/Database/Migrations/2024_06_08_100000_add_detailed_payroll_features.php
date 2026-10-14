<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDetailedPayrollFeatures extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('payroll_employees')) {
            Schema::create('payroll_employees', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('user_id')->nullable();
                $table->unsignedBigInteger('branch_id')->nullable();
                $table->unsignedBigInteger('payroll_template_id')->nullable();
                $table->string('employee_number')->nullable();
                $table->string('department')->nullable();
                $table->string('job_title')->nullable();
                $table->string('kra_pin')->nullable();
                $table->string('nssf_number')->nullable();
                $table->string('shif_number')->nullable();
                $table->enum('salary_type', ['monthly', 'hourly', 'daily'])->default('monthly');
                $table->decimal('basic_salary', 65, 2)->default(0);
                $table->decimal('hourly_rate', 65, 2)->default(0);
                $table->decimal('daily_rate', 65, 2)->default(0);
                $table->string('bank_name')->nullable();
                $table->string('account_number')->nullable();
                $table->date('date_hired')->nullable();
                $table->tinyInteger('is_active')->default(1);
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('payroll_runs')) {
            Schema::create('payroll_runs', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name')->nullable();
                $table->string('period')->nullable();
                $table->date('period_start')->nullable();
                $table->date('period_end')->nullable();
                $table->unsignedBigInteger('branch_id')->nullable();
                $table->unsignedBigInteger('currency_id')->nullable();
                $table->unsignedBigInteger('created_by_id')->nullable();
                $table->unsignedBigInteger('approved_by_id')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->enum('status', ['draft', 'processing', 'approved', 'paid', 'cancelled'])->default('draft');
                $table->decimal('total_gross', 65, 2)->default(0);
                $table->decimal('total_net', 65, 2)->default(0);
                $table->decimal('total_paye', 65, 2)->default(0);
                $table->decimal('total_nssf', 65, 2)->default(0);
                $table->decimal('total_shif', 65, 2)->default(0);
                $table->decimal('total_housing_levy', 65, 2)->default(0);
                $table->integer('employee_count')->default(0);
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('payroll')) {
            Schema::table('payroll', function (Blueprint $table) {
                if (!Schema::hasColumn('payroll', 'payroll_run_id')) {
                    $table->unsignedBigInteger('payroll_run_id')->nullable()->after('payroll_template_id');
                }
                if (!Schema::hasColumn('payroll', 'basic_salary')) {
                    $table->decimal('basic_salary', 65, 2)->default(0)->after('total_duration_amount');
                }
                if (!Schema::hasColumn('payroll', 'gross_pay')) {
                    $table->decimal('gross_pay', 65, 2)->default(0)->after('basic_salary');
                }
                if (!Schema::hasColumn('payroll', 'taxable_pay')) {
                    $table->decimal('taxable_pay', 65, 2)->default(0)->after('gross_pay');
                }
                if (!Schema::hasColumn('payroll', 'net_pay')) {
                    $table->decimal('net_pay', 65, 2)->default(0)->after('taxable_pay');
                }
                if (!Schema::hasColumn('payroll', 'paye')) {
                    $table->decimal('paye', 65, 2)->default(0)->after('net_pay');
                }
                if (!Schema::hasColumn('payroll', 'nssf_employee')) {
                    $table->decimal('nssf_employee', 65, 2)->default(0)->after('paye');
                }
                if (!Schema::hasColumn('payroll', 'nssf_employer')) {
                    $table->decimal('nssf_employer', 65, 2)->default(0)->after('nssf_employee');
                }
                if (!Schema::hasColumn('payroll', 'shif')) {
                    $table->decimal('shif', 65, 2)->default(0)->after('nssf_employer');
                }
                if (!Schema::hasColumn('payroll', 'housing_levy_employee')) {
                    $table->decimal('housing_levy_employee', 65, 2)->default(0)->after('shif');
                }
                if (!Schema::hasColumn('payroll', 'housing_levy_employer')) {
                    $table->decimal('housing_levy_employer', 65, 2)->default(0)->after('housing_levy_employee');
                }
                if (!Schema::hasColumn('payroll', 'employee_number')) {
                    $table->string('employee_number')->nullable()->after('employee_name');
                }
                if (!Schema::hasColumn('payroll', 'department')) {
                    $table->string('department')->nullable()->after('employee_number');
                }
                if (!Schema::hasColumn('payroll', 'job_title')) {
                    $table->string('job_title')->nullable()->after('department');
                }
                if (!Schema::hasColumn('payroll', 'kra_pin')) {
                    $table->string('kra_pin')->nullable()->after('job_title');
                }
                if (!Schema::hasColumn('payroll', 'ytd_gross')) {
                    $table->decimal('ytd_gross', 65, 2)->default(0)->after('total_deductions');
                }
                if (!Schema::hasColumn('payroll', 'ytd_paye')) {
                    $table->decimal('ytd_paye', 65, 2)->default(0)->after('ytd_gross');
                }
                if (!Schema::hasColumn('payroll', 'ytd_nssf')) {
                    $table->decimal('ytd_nssf', 65, 2)->default(0)->after('ytd_paye');
                }
                if (!Schema::hasColumn('payroll', 'ytd_net')) {
                    $table->decimal('ytd_net', 65, 2)->default(0)->after('ytd_nssf');
                }
                if (!Schema::hasColumn('payroll', 'status')) {
                    $table->enum('status', ['draft', 'approved', 'paid'])->default('draft')->after('ytd_net');
                }
            });
        }

        if (Schema::hasTable('payroll_items')) {
            Schema::table('payroll_items', function (Blueprint $table) {
                if (!Schema::hasColumn('payroll_items', 'category')) {
                    $table->string('category')->default('other')->after('type');
                }
                if (!Schema::hasColumn('payroll_items', 'is_statutory')) {
                    $table->tinyInteger('is_statutory')->default(0)->after('category');
                }
                if (!Schema::hasColumn('payroll_items', 'is_taxable')) {
                    $table->tinyInteger('is_taxable')->default(1)->after('is_statutory');
                }
                if (!Schema::hasColumn('payroll_items', 'calculation_order')) {
                    $table->integer('calculation_order')->default(100)->after('is_taxable');
                }
            });
        }

        if (Schema::hasTable('payroll_items_meta')) {
            Schema::table('payroll_items_meta', function (Blueprint $table) {
                if (!Schema::hasColumn('payroll_items_meta', 'computed_amount')) {
                    $table->decimal('computed_amount', 65, 2)->default(0)->after('amount');
                }
                if (!Schema::hasColumn('payroll_items_meta', 'category')) {
                    $table->string('category')->nullable()->after('computed_amount');
                }
                if (!Schema::hasColumn('payroll_items_meta', 'is_taxable')) {
                    $table->tinyInteger('is_taxable')->default(1)->after('category');
                }
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('payroll_runs');
        Schema::dropIfExists('payroll_employees');
    }
}
