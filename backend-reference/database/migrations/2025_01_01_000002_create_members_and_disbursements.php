<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {

        Schema::create('members', function (Blueprint $table) {
            $table->id(); $table->foreignId('branch_id')->constrained(); $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete(); $table->string('umid')->unique(); $table->string('first_name'); $table->string('last_name'); $table->string('phone')->nullable(); $table->string('email')->nullable(); $table->string('status')->index(); $table->string('bank_name')->nullable(); $table->string('bank_account_number')->nullable(); $table->string('id_document_ref')->nullable(); $table->text('status_reason')->nullable(); $table->timestamp('activated_at')->nullable(); $table->timestamp('terminated_at')->nullable(); $table->timestamps();
        });
        Schema::create('member_status_histories', function (Blueprint $table) { $table->id(); $table->foreignId('member_id')->constrained()->cascadeOnDelete(); $table->string('from_status')->nullable(); $table->string('to_status'); $table->text('reason')->nullable(); $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamps(); });
        Schema::create('disbursement_requests', function (Blueprint $table) { $table->id(); $table->foreignId('member_id')->constrained(); $table->foreignId('branch_id')->constrained(); $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete(); $table->decimal('amount',15,2); $table->text('purpose')->nullable(); $table->string('stage')->index(); $table->foreignId('authorized_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamp('authorized_at')->nullable(); $table->timestamp('paid_at')->nullable(); $table->text('rejected_reason')->nullable(); $table->text('cancelled_reason')->nullable(); $table->timestamps(); });
        Schema::create('disbursement_approvals', function (Blueprint $table) { $table->id(); $table->foreignId('disbursement_request_id')->constrained()->cascadeOnDelete(); $table->foreignId('user_id')->constrained(); $table->string('role_code'); $table->string('stage'); $table->text('remarks')->nullable(); $table->timestamp('approved_at'); $table->timestamps(); $table->unique(['disbursement_request_id','stage']); });
        Schema::create('disbursement_status_histories', function (Blueprint $table) { $table->id(); $table->foreignId('disbursement_request_id')->constrained()->cascadeOnDelete(); $table->string('from_stage')->nullable(); $table->string('to_stage'); $table->text('reason')->nullable(); $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamps(); });

    }
    public function down(): void {
        // Manual rollback recommended for financial systems.
    }
};
