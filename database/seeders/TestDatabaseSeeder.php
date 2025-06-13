<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\Origin;
use App\Models\Type;
use App\Models\Category;
use App\Models\Visibility;

class TestDatabaseSeeder extends Seeder
{
    public function run()
    {
        // Utiliser updateOrCreate pour éviter les doublons
        Origin::updateOrCreate(
            ['libelle' => 'Personnel'],
            ['created_at' => now(), 'updated_at' => now()]
        );
        
        // Ajouter aussi les autres entités nécessaires
        Type::updateOrCreate(['name' => 'Document']);
        Type::updateOrCreate(['name' => 'Activité']);
        Category::updateOrCreate(['name' => 'Education']);
        Visibility::updateOrCreate(['name' => 'Public']);
    }
}