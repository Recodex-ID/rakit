<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Demo company block for printed orders. Replace it in System > Settings.
        Setting::put('company_name', 'PT Demo Rakit');
        Setting::put('company_address', 'Jakarta, Indonesia');
    }
}
