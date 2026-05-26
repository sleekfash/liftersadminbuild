<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {

        Schema::create('monthly_periods', function (Blueprint $table) { $table->id(); $table->string('name'); $table->unsignedTinyInteger('month'); $table->unsignedSmallInteger('year'); $table->string('status')->index(); $table->decimal('opening_balance',15,2)->default(0); $table->decimal('closing_balance',15,2)->nullable(); $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamp('closed_at')->nullable(); $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamp('locked_at')->nullable(); $table->timestamps(); $table->unique(['month','year']); });
        Schema::create('treasury_transactions', function (Blueprint $table) { $table->id(); $table->foreignId('monthly_period_id')->constrained(); $table->foreignId('disbursement_request_id')->nullable()->constrained()->nullOnDelete(); $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); $table->foreignId('reversed_transaction_id')->nullable()->constrained('treasury_transactions')->nullOnDelete(); $table->string('type')->index(); $table->decimal('amount',15,2); $table->decimal('balance_after',15,2); $table->text('description')->nullable(); $table->text('remarks')->nullable(); $table->date('occurred_on'); $table->timestamps(); });
        Schema::create('trial_balance_records', function (Blueprint $table) { $table->id(); $table->foreignId('monthly_period_id')->constrained(); $table->string('source')->nullable(); $table->string('label'); $table->decimal('amount',15,2); $table->json('metadata')->nullable(); $table->timestamps(); });
        Schema::create('reconciliation_runs', function (Blueprint $table) { $table->id(); $table->foreignId('monthly_period_id')->constrained(); $table->foreignId('triggered_by')->nullable()->constrained('users')->nullOnDelete(); $table->string('status')->index(); $table->decimal('operational_total',15,2)->default(0); $table->decimal('ledger_total',15,2)->default(0); $table->decimal('variance',15,2)->default(0); $table->timestamp('completed_at')->nullable(); $table->timestamps(); });
        Schema::create('reconciliation_items', function (Blueprint $table) { $table->id(); $table->foreignId('reconciliation_run_id')->constrained()->cascadeOnDelete(); $table->foreignId('related_disbursement_request_id')->nullable()->constrained('disbursement_requests')->nullOnDelete(); $table->foreignId('related_treasury_transaction_id')->nullable()->constrained('treasury_transactions')->nullOnDelete(); $table->string('severity')->index(); $table->string('status')->index(); $table->text('description'); $table->decimal('variance',15,2)->default(0); $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamp('resolved_at')->nullable(); $table->text('resolution_note')->nullable(); $table->timestamps(); });

    }
    public function down(): void {
        // Manual rollback recommended for financial systems.
    }
};
