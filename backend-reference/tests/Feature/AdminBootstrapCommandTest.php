<?php

namespace Tests\Feature;

use App\Enums\RoleCode;
use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminBootstrapCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_bootstrap_creates_hq_admin_and_credentials_sign_in(): void
    {
        $this->artisan('admin:bootstrap')->assertSuccessful();

        $this->assertDatabaseHas('branches', [
            'code' => 'HQ',
            'name' => 'Head Office',
            'is_active' => true,
        ]);

        $admin = User::query()->where('email', 'admin@lifterscenter.com')->firstOrFail();
        $this->assertTrue($admin->is_active);
        $this->assertNull($admin->branch_id);
        $this->assertTrue($admin->hasRole(RoleCode::SUPER_ADMIN->value));

        $output = Artisan::output();
        $this->assertSame(1, preg_match('/Temporary password \(shown once\): (\S+)/', $output, $matches));

        $this->postJson('/api/v1/login', [
            'email' => 'admin@lifterscenter.com',
            'password' => $matches[1],
        ])->assertOk()
            ->assertJsonPath('data.email', 'admin@lifterscenter.com')
            ->assertJsonPath('data.is_active', true)
            ->assertJsonPath('data.roles.0.code', RoleCode::SUPER_ADMIN->value)
            ->assertJsonStructure(['token']);
    }

    public function test_bootstrap_refuses_to_change_an_existing_admin_or_branch(): void
    {
        $branch = Branch::query()->create([
            'code' => 'HQ',
            'name' => 'Existing Office',
            'is_active' => false,
        ]);
        $role = Role::query()->create([
            'code' => RoleCode::AUDITOR->value,
            'name' => 'Auditor',
        ]);
        $admin = User::query()->create([
            'name' => 'Existing Account',
            'email' => 'admin@lifterscenter.com',
            'password' => 'existing-secure-password',
            'is_active' => false,
        ]);
        $admin->roles()->attach($role->id);
        $originalPasswordHash = $admin->password;

        $this->artisan('admin:bootstrap')
            ->expectsOutputToContain('already exists')
            ->assertExitCode(1);

        $this->assertSame($originalPasswordHash, $admin->fresh()->password);
        $this->assertFalse($admin->fresh()->is_active);
        $this->assertTrue($admin->fresh()->hasRole(RoleCode::AUDITOR->value));
        $this->assertDatabaseHas('branches', [
            'id' => $branch->id,
            'name' => 'Existing Office',
            'is_active' => false,
        ]);
        $this->assertFalse(Hash::check('any-new-password', $admin->fresh()->password));
    }
}