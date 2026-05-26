<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {

        Schema::create('roles', function (Blueprint $table) { $table->id(); $table->string('name'); $table->string('code')->unique(); $table->text('description')->nullable(); $table->timestamps(); });
        Schema::create('role_user', function (Blueprint $table) { $table->id(); $table->foreignId('role_id')->constrained()->cascadeOnDelete(); $table->foreignId('user_id')->constrained()->cascadeOnDelete(); $table->timestamps(); $table->unique(['role_id','user_id']); });
        Schema::create('branches', function (Blueprint $table) { $table->id(); $table->string('name'); $table->string('code')->unique(); $table->text('address')->nullable(); $table->boolean('is_active')->default(true); $table->timestamps(); });
        Schema::create('app_settings', function (Blueprint $table) { $table->id(); $table->string('key')->unique(); $table->text('value')->nullable(); $table->string('type')->default('string'); $table->text('description')->nullable(); $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamps(); });

    }
    public function down(): void {
        // Manual rollback recommended for financial systems.
    }
};
