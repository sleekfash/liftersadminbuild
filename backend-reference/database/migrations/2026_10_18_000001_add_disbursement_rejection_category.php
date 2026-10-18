<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('disbursement_requests', function (Blueprint $table) {
            $table->string('rejection_category')->nullable();
        });
    }
    public function down(): void
    {
        Schema::table('disbursement_requests', function (Blueprint $table) {
            $table->dropColumn('rejection_category');
        });
    }
};