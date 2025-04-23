<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OriginSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('origins')->insert([
            ['libelle' => 'Interne'],
            ['libelle' => 'Externe'],
            ['libelle' => 'Partenaire'],
        ]);
    }
}