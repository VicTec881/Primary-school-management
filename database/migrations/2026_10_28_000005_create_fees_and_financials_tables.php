<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Tuition, Meals & Lunch, Activity & Sports, Admission, Transport, Learning Materials
            $table->string('code')->unique(); // TUIT, LUNCH, ACT, ADM, TRANS, MAT
            $table->text('description')->nullable();
            $table->boolean('is_recurring')->default(true);
            $table->boolean('is_optional')->default(false);
            $table->timestamps();
        });

        Schema::create('fee_structures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->foreignId('term_id')->constrained('terms')->cascadeOnDelete();
            $table->foreignId('grade_id')->constrained('grades')->cascadeOnDelete();
            $table->string('title');
            $table->unsignedInteger('version')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['academic_year_id', 'term_id', 'grade_id', 'version'], 'fee_struct_unique');
        });

        Schema::create('fee_structure_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fee_structure_id')->constrained('fee_structures')->cascadeOnDelete();
            $table->foreignId('fee_category_id')->constrained('fee_categories')->cascadeOnDelete();
            $table->string('name');
            $table->decimal('amount', 12, 2);
            $table->boolean('is_optional')->default(false);
            $table->timestamps();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique()->index();
            $table->foreignId('learner_id')->constrained('learners')->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->foreignId('term_id')->constrained('terms')->cascadeOnDelete();
            $table->foreignId('fee_structure_id')->nullable()->constrained('fee_structures')->nullOnDelete();
            $table->decimal('total_amount', 12, 2)->default(0.00);
            $table->decimal('discount_amount', 12, 2)->default(0.00);
            $table->decimal('paid_amount', 12, 2)->default(0.00);
            $table->decimal('balance', 12, 2)->default(0.00);
            $table->string('status')->default('pending'); // pending, partial, paid, overdue, cancelled
            $table->date('issue_date');
            $table->date('due_date');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['learner_id', 'academic_year_id', 'term_id'], 'uniq_learner_term_inv');
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->foreignId('fee_category_id')->nullable()->constrained('fee_categories')->nullOnDelete();
            $table->string('name');
            $table->decimal('amount', 12, 2);
            $table->boolean('is_optional')->default(false);
            $table->timestamps();
        });

        Schema::create('learner_fee_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learner_id')->unique()->constrained('learners')->cascadeOnDelete();
            $table->decimal('opening_balance', 12, 2)->default(0.00);
            $table->decimal('total_billed', 12, 2)->default(0.00);
            $table->decimal('total_paid', 12, 2)->default(0.00);
            $table->decimal('total_discounted', 12, 2)->default(0.00);
            $table->decimal('current_balance', 12, 2)->default(0.00); // positive = owes school, negative/credit = in advance
            $table->decimal('credit_balance', 12, 2)->default(0.00);
            $table->timestamps();
        });

        Schema::create('discounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learner_id')->constrained('learners')->cascadeOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->string('reason');
            $table->decimal('amount', 12, 2);
            $table->foreignId('authorized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_number')->unique()->index();
            $table->foreignId('learner_id')->constrained('learners')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('payment_method'); // cash, mpesa, bank_transfer, cheque
            $table->string('transaction_reference')->nullable()->index();
            $table->date('payment_date');
            $table->string('bank_name')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('completed'); // completed, reversed
            $table->text('reversal_reason')->nullable();
            $table->foreignId('reversed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reversed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->timestamps();
        });

        Schema::create('financial_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learner_id')->constrained('learners')->cascadeOnDelete();
            $table->string('transaction_type'); // charge, payment, discount, reversal, refund, opening_balance
            $table->decimal('amount', 12, 2);
            $table->decimal('balance_after', 12, 2);
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('reference_type')->nullable(); // Invoice, Payment, Discount
            $table->string('description');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('mpesa_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('merchant_request_id')->nullable()->index();
            $table->string('checkout_request_id')->nullable()->unique()->index();
            $table->string('mpesa_receipt_number')->nullable()->unique()->index();
            $table->string('phone_number');
            $table->decimal('amount', 12, 2);
            $table->foreignId('learner_id')->nullable()->constrained('learners')->nullOnDelete();
            $table->string('status')->default('pending'); // pending, completed, failed, cancelled
            $table->string('result_code')->nullable();
            $table->text('result_desc')->nullable();
            $table->longText('raw_callback')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mpesa_transactions');
        Schema::dropIfExists('financial_ledger');
        Schema::dropIfExists('payment_allocations');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('discounts');
        Schema::dropIfExists('learner_fee_accounts');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('fee_structure_items');
        Schema::dropIfExists('fee_structures');
        Schema::dropIfExists('fee_categories');
    }
};

