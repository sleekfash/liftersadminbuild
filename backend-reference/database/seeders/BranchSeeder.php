<?php
namespace Database\Seeders;
use App\Models\Branch; use Illuminate\Database\Seeder;
class BranchSeeder extends Seeder { public function run(): void { Branch::updateOrCreate(['code'=>'HQ'],['name'=>'Head Office','is_active'=>true]); } }
