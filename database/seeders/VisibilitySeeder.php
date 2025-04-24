<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VisibilitySeeder extends Seeder
{
    public function run(): void
    {
        DB::table('visibilities')->insert([
            ['name' => 'Public'],
            ['name' => 'Privé'],
            ['name' => 'Restreint'],
        ]);
    }
}
