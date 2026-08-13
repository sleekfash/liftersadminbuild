<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('branch_handovers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('outgoing_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('incoming_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->index();
            $table->json('snapshot')->nullable();
            $table->timestamp('outgoing_signed_at')->nullable();
            $table->text('outgoing_notes')->nullable();
            $table->timestamp('incoming_signed_at')->nullable();
            $table->text('incoming_notes')->nullable();
            $table->text('dispute_reason')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['branch_id', 'status']);
        });
    }

    public function down(): void
    {
        // Manual rollback recommended for financial systems.
    }
};
