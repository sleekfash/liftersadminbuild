<?php
namespace Database\Seeders;
use App\Models\AppSetting; use Illuminate\Database\Seeder;
class AppSettingSeeder extends Seeder { public function run(): void { AppSetting::updateOrCreate(['key'=>'foundation_name'],['value'=>"Lifter's Touch Empowerment Foundation",'type'=>'string']); } }
