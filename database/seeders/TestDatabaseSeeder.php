<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Origin; // Ajustez selon votre namespace

class TestDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Désactiver les contraintes de clés étrangères temporairement
        DB::statement('SET FOREIGN_KEY_CHECKS=0;'); // MySQL/MariaDB
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys=OFF');
        }

        try {
            // Créer les données de base nécessaires pour les tests
            $this->createOrigins();
            $this->createOtherRequiredData();
            
        } catch (\Exception $e) {
            // Log l'erreur mais ne pas arrêter les tests
            \Log::warning('TestDatabaseSeeder error: ' . $e->getMessage());
        } finally {
            // Réactiver les contraintes
            DB::statement('SET FOREIGN_KEY_CHECKS=1;'); // MySQL/MariaDB
            if (DB::connection()->getDriverName() === 'sqlite') {
                DB::statement('PRAGMA foreign_keys=ON');
            }
        }
    }

    private function createOrigins(): void
    {
        // Vérifier si la table existe
        if (!DB::getSchemaBuilder()->hasTable('origins')) {
            return;
        }

        // Créer les origins nécessaires pour les tests
        $origins = [
            ['libelle' => 'Personnel'],
            ['libelle' => 'Public'],
            ['libelle' => 'Externe'],
        ];

        foreach ($origins as $origin) {
            DB::table('origins')->updateOrInsert(
                ['libelle' => $origin['libelle']],
                array_merge($origin, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }

    private function createOtherRequiredData(): void
    {
        // Ajoutez ici d'autres données requises pour vos tests
        // Par exemple, des statuts, des catégories, etc.
    }
}