<?php
namespace Database\Seeders;
use App\Enums\RoleCode; use App\Models\Role; use Illuminate\Database\Seeder;
class RoleSeeder extends Seeder { public function run(): void { foreach(RoleCode::cases() as $r){ Role::updateOrCreate(['code'=>$r->value],['name'=>str($r->value)->replace('_',' ')->title(),'description'=>"System role: {$r->value}"]); } } }
