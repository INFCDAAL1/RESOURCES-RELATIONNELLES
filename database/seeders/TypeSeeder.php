<?php

namespace Database\Seeders;

use App\Models\Type;
use Illuminate\Database\Seeder;

class TypeSeeder extends Seeder
{
    public function run(): void
    {
        Type::firstOrCreate(['name' => 'pdf']);
        Type::firstOrCreate(['name' => 'csv']);
        Type::firstOrCreate(['name' => 'doc']);
    }
}