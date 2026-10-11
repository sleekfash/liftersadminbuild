<?php

namespace App\Console\Commands;

use App\Enums\RoleCode;
use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BootstrapAdminCommand extends Command
{
    protected $signature = 'admin:bootstrap';

    protected $description = 'Create the initial HQ branch and super administrator once';

    public function handle(): int
    {
        $email = 'admin@lifterscenter.com';

        if (User::query()->where('email', $email)->exists()) {
            $this->error("{$email} already exists. No account, role, or branch was changed.");

            return self::FAILURE;
        }

        $password = Str::password(32);

        DB::transaction(function () use ($email, $password): void {
            if (User::query()->where('email', $email)->lockForUpdate()->exists()) {
                throw new \RuntimeException("{$email} was created concurrently; no credentials were issued.");
            }

            Branch::query()->firstOrCreate(
                ['code' => 'HQ'],
                ['name' => 'Head Office', 'is_active' => true],
            );

            $role = Role::query()->firstOrCreate(
                ['code' => RoleCode::SUPER_ADMIN->value],
                ['name' => 'Super Admin', 'description' => 'System role: SUPER_ADMIN'],
            );

            $admin = User::query()->create([
                'name' => 'Super Admin',
                'email' => $email,
                'password' => $password,
                'is_active' => true,
                'branch_id' => null,
            ]);

            $admin->roles()->attach($role->id);
        });

        $this->info('Created active super administrator and ensured the HQ branch exists.');
        $this->line('Temporary password (shown once): '.$password);
        $this->warn('Save this password securely, sign in, and change it immediately.');

        return self::SUCCESS;
    }
}