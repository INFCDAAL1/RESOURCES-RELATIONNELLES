<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Désactiver les contraintes de clés étrangères pour SQLite
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys=OFF');
        }
        
        // Exécuter les seeders nécessaires pour les tests
        $this->seed(\Database\Seeders\TestDatabaseSeeder::class);
    }

    protected function tearDown(): void
    {
        // Éviter les commandes VACUUM problématiques avec SQLite en mémoire
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys=ON');
        }
        
        parent::tearDown();
    }
    
    /**
     * Refresh the in-memory database.
     */
    protected function refreshInMemoryDatabase(): void
    {
        $this->artisan('migrate', [
            '--no-interaction' => true,
            '--force' => true,
        ]);

        $this->app[Kernel::class]->setArtisan(null);
    }
}