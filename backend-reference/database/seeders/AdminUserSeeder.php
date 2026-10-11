<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Kept as a no-op for compatibility with deployments that invoke this seeder
 * directly. Production credentials are issued only by admin:bootstrap.
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->warn('No administrator was created. Run `php artisan admin:bootstrap` over private SSH when initial setup is intended.');
    }
}
