<?php

namespace Database\Seeders;

use App\Models\Origin;
use Illuminate\Database\Seeder;

class OriginSeeder extends Seeder
{
    public function run(): void
    {
        Origin::firstOrCreate(['libelle' => 'Interne']);
        Origin::firstOrCreate(['libelle' => 'Externe']);
        Origin::firstOrCreate(['libelle' => 'Partenaire']);
    }
}