<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Branch manager postings.
 *
 * A posting moves ONLY the officer. Branch-owned records (members,
 * disbursements, treasury transactions, import batches) keep their own
 * branch_id and are never rewritten by an assignment change.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('branch_manager_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role_code')->default('BRANCH_MANAGER')->index();
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->text('ended_reason')->nullable();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('branch_handover_id')->nullable();
            $table->boolean('handover_overridden')->default(false);
            $table->timestamps();
            $table->index(['branch_id', 'ended_at']);
            $table->index(['user_id', 'ended_at']);
        });

        Schema::table('branches', function (Blueprint $table) {
            $table->foreignId('current_manager_id')->nullable()->after('is_active')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        // Manual rollback recommended for financial systems.
    }
};
