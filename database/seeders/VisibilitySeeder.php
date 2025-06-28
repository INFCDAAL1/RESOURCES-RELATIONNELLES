<?php

namespace Database\Seeders;

use App\Models\Visibility;
use Illuminate\Database\Seeder;

class VisibilitySeeder extends Seeder
{
    public function run(): void
    {
        Visibility::firstOrCreate(['name' => 'Public']);
        Visibility::firstOrCreate(['name' => 'Privé']);
        Visibility::firstOrCreate(['name' => 'Restreint']);
    }
}
