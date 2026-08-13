<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Denormalise the owning branch onto import_batches.
 *
 * Branch scoping previously walked uploader->branch_id, which silently
 * re-homed historical uploads whenever the uploader was posted to another
 * branch. The batch now owns its branch for the life of the record.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('import_batches', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('uploaded_by')
                ->constrained()->nullOnDelete();
            $table->index('branch_id');
        });

        DB::table('import_batches')->orderBy('id')->chunkById(200, function ($batches) {
            foreach ($batches as $batch) {
                if (!$batch->uploaded_by) continue;
                $branchId = DB::table('users')->where('id', $batch->uploaded_by)->value('branch_id');
                if ($branchId) {
                    DB::table('import_batches')->where('id', $batch->id)->update(['branch_id' => $branchId]);
                }
            }
        });
    }

    public function down(): void
    {
        // Manual rollback recommended for financial systems.
    }
};
