<?php
namespace Database\Seeders;
use App\Enums\RoleCode; use App\Models\{Branch,Role,User}; use Illuminate\Database\Seeder; use Illuminate\Support\Facades\Hash;
class AdminUserSeeder extends Seeder { public function run(): void { $branch=Branch::where('code','HQ')->first(); $u=User::updateOrCreate(['email'=>'admin@example.com'],['branch_id'=>$branch?->id,'name'=>'Super Admin','password'=>Hash::make('password'),'is_active'=>true]); $role=Role::where('code',RoleCode::SUPER_ADMIN->value)->first(); if($role)$u->roles()->syncWithoutDetaching([$role->id]); } }
