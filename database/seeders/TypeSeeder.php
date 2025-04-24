<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TypeSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('types')->insert([
            ['name' => 'pdf'],
            ['name' => 'csv'],
            ['name' => 'doc'],
        ]);
    }
}