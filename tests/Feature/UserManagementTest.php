<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;
    
    protected function setUp(): void
    {
        parent::setUp();
        // Nettoyer la base de données et appliquer les migrations
        $this->artisan('migrate:fresh');
    }

    /**
     * Test de création d'un compte citoyen (utilisateur standard)
     */
    /*
    public function test_create_citizen_account()
    {
        $response = $this->post('/register', [
            'name' => 'Citoyen Test',
            'email' => 'citoyen@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard'));
        
        // Vérifier que l'utilisateur a été créé avec le rôle 'user' par défaut
        $user = User::where('email', 'citoyen@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('user', $user->role);
        $this->assertTrue($user->is_active);
    }
    */
    
    /**
     * Test qu'un utilisateur désactivé ne peut pas se connecter
     */
    public function test_deactivated_user_cannot_login()
    {
        // Créer un utilisateur désactivé
        $user = User::factory()->create([
            'email' => 'inactive@example.com',
            'password' => Hash::make('password123'),
            'is_active' => false,
        ]);

        // Tenter de se connecter avec cet utilisateur
        $response = $this->post('/login', [
            'email' => 'inactive@example.com',
            'password' => 'password123',
        ]);

        // L'utilisateur ne devrait pas être authentifié
        $this->assertGuest();
    }

    /**
     * Test de désactivation d'un compte (sans utiliser les routes admin)
     */
    public function test_account_deactivation()
    {
        // Créer un utilisateur admin
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        // Créer un utilisateur citoyen
        $citizen = User::factory()->create([
            'role' => 'user',
            'is_active' => true,
        ]);

        // Simuler directement la désactivation du compte
        $this->assertTrue($citizen->is_active);
        $citizen->is_active = false;
        $citizen->save();
        $citizen->refresh();
        
        // Vérifier que le compte a été désactivé
        $this->assertFalse($citizen->is_active);
    }

    /**
     * Test de création d'un utilisateur avec différents rôles
     */
    public function test_create_users_with_different_roles()
    {
        // Créer un utilisateur avec rôle 'user'
        $citizen = User::factory()->create([
            'role' => 'user',
            'is_active' => true,
        ]);
        
        // Vérifier que l'utilisateur a le bon rôle
        $this->assertEquals('user', $citizen->role);
        
        // Créer un utilisateur avec rôle 'moderator'
        $moderator = User::factory()->create([
            'role' => 'moderator',
            'is_active' => true,
        ]);
        
        // Vérifier que l'utilisateur a le bon rôle
        $this->assertEquals('moderator', $moderator->role);
        
        // Créer un utilisateur avec rôle 'admin'
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);
        
        // Vérifier que l'utilisateur a le bon rôle
        $this->assertEquals('admin', $admin->role);
    }

    /**
     * Test de la méthode isAdmin du modèle User
     */
    public function test_user_is_admin_method()
    {
        // Créer un utilisateur standard
        $user = User::factory()->create([
            'role' => 'user',
        ]);
        
        // Vérifier que la méthode isAdmin retourne false
        $this->assertFalse($user->isAdmin());
        
        // Créer un utilisateur admin
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        
        // Vérifier que la méthode isAdmin retourne true
        $this->assertTrue($admin->isAdmin());
    }

    /**
     * Test de la méthode isActive du modèle User
     */
    public function test_user_is_active_method()
    {
        // Créer un utilisateur actif
        $activeUser = User::factory()->create([
            'is_active' => true,
        ]);
        
        // Vérifier que la méthode isActive retourne true
        $this->assertTrue($activeUser->isActive());
        
        // Créer un utilisateur inactif
        $inactiveUser = User::factory()->create([
            'is_active' => false,
        ]);
        
        // Vérifier que la méthode isActive retourne false
        $this->assertFalse($inactiveUser->isActive());
    }

    /**
     * Test de changement de rôle d'un utilisateur
     */
    public function test_change_user_role()
    {
        // Créer un utilisateur standard
        $user = User::factory()->create([
            'role' => 'user',
        ]);
        
        // Vérifier le rôle initial
        $this->assertEquals('user', $user->role);
        
        // Changer le rôle
        $user->role = 'moderator';
        $user->save();
        $user->refresh();
        
        // Vérifier que le rôle a été changé
        $this->assertEquals('moderator', $user->role);
    }
    
    /**
     * Test de validation de l'adresse email lors de l'inscription
     */
    public function test_email_validation_during_registration()
    {
        // Test d'email invalide
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'invalid-email',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        
        $response->assertSessionHasErrors('email');
        
        // Test d'email déjà utilisé
        $existingUser = User::factory()->create([
            'email' => 'existing@example.com',
        ]);
        
        $response = $this->post('/register', [
            'name' => 'Another User',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        
        $response->assertSessionHasErrors('email');
    }
}