<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {

        Schema::create('audit_logs', function (Blueprint $table) { $table->id(); $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete(); $table->string('event_type')->index(); $table->string('auditable_type')->nullable(); $table->unsignedBigInteger('auditable_id')->nullable(); $table->text('summary'); $table->json('details')->nullable(); $table->string('ip_address')->nullable(); $table->text('user_agent')->nullable(); $table->timestamps(); $table->index(['auditable_type','auditable_id']); });
        Schema::create('import_batches', function (Blueprint $table) { $table->id(); $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete(); $table->string('filename'); $table->string('status')->index(); $table->json('metadata')->nullable(); $table->timestamps(); });
        Schema::create('import_sheet_snapshots', function (Blueprint $table) { $table->id(); $table->foreignId('import_batch_id')->constrained()->cascadeOnDelete(); $table->string('sheet_name'); $table->json('title_blocks')->nullable(); $table->json('heading_map')->nullable(); $table->json('raw_payload')->nullable(); $table->timestamps(); });
        Schema::create('import_rows', function (Blueprint $table) { $table->id(); $table->foreignId('import_batch_id')->constrained()->cascadeOnDelete(); $table->foreignId('import_sheet_snapshot_id')->nullable()->constrained()->nullOnDelete(); $table->unsignedInteger('row_number'); $table->string('status')->index(); $table->json('raw_payload'); $table->json('mapped_payload')->nullable(); $table->json('errors')->nullable(); $table->timestamps(); });
        Schema::create('idempotency_keys', function (Blueprint $table) { $table->id(); $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); $table->string('key')->index(); $table->string('method',12); $table->string('path'); $table->string('request_hash',64); $table->unsignedSmallInteger('response_status')->nullable(); $table->json('response_body')->nullable(); $table->timestamp('locked_at')->nullable(); $table->timestamp('completed_at')->nullable(); $table->timestamps(); $table->unique(['user_id','key']); });

    }
    public function down(): void {
        // Manual rollback recommended for financial systems.
    }
};
