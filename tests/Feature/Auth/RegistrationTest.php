<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Registered;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Nous n'allons pas tester le rendu de la page d'inscription
     * puisque cela nécessite Vite et le frontend
     */

    public function test_new_users_can_register()
    {
        Event::fake([Registered::class]);

        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ];

        // Tester la logique d'inscription
        $response = $this->post('/register', $userData);

        // Vérifier que l'utilisateur a été créé en base de données
        $this->assertDatabaseHas('users', [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Vérifier que l'événement attendu a été déclenché
        Event::assertDispatched(Registered::class);
        
        // Vérifier la redirection vers le tableau de bord
        $response->assertRedirect('/dashboard');
    }
}