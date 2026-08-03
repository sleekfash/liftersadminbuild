<?php
namespace Database\Seeders;
use App\Enums\RoleCode; use App\Models\{Branch,Role,User}; use Illuminate\Database\Seeder; use Illuminate\Support\Facades\Hash; use Illuminate\Support\Str;
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $branch = Branch::where('code','HQ')->first();
        $email = env('ADMIN_SEED_EMAIL', 'admin@example.com');
        $existing = User::where('email',$email)->first();

        // Never re-write the password of an existing admin, and never ship a
        // hardcoded literal: use ADMIN_SEED_PASSWORD when provided, otherwise
        // generate a one-time random credential printed to the seeder output.
        $generated = null;
        if (!$existing) {
            $password = env('ADMIN_SEED_PASSWORD');
            if (!$password) { $password = Str::password(24); $generated = $password; }
        }

        $attrs = ['branch_id'=>$branch?->id,'name'=>'Super Admin','is_active'=>true];
        if (!$existing) { $attrs['password'] = Hash::make($password); }

        $u = User::updateOrCreate(['email'=>$email], $attrs);
        $role = Role::where('code',RoleCode::SUPER_ADMIN->value)->first();
        if ($role) $u->roles()->syncWithoutDetaching([$role->id]);

        if ($generated) {
            $this->command?->warn("Seeded super admin {$email} with one-time password: {$generated}");
            $this->command?->warn('Change this password on first login. It is not stored anywhere else.');
        }
    }
}
