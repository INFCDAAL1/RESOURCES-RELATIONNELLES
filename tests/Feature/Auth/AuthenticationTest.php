<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Désactiver les middleware problématiques
        $this->withoutMiddleware(\App\Http\Middleware\HandleInertiaRequests::class);
        $this->withoutMiddleware(\Tymon\JWTAuth\Http\Middleware\Authenticate::class);
    }

    public function test_users_can_authenticate_using_the_login_screen()
    {
        $user = User::factory()->create();
        
        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);
        
        $response->assertStatus(302); 
        $response->assertRedirect('/dashboard');
    }

    public function test_users_can_not_authenticate_with_invalid_password()
    {
        $user = User::factory()->create();
        
        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);
        
        $response->assertSessionHasErrors('email');
        $this->assertGuest('web');
    }

    public function test_users_can_logout()
    {
        $user = User::factory()->create();
        
        // Simuler un utilisateur connecté
        $response = $this->actingAs($user, 'web')
                         ->post('/logout');
        
        // Vérifier la redirection
        $response->assertStatus(302);
        $response->assertRedirect('/');
    }
}