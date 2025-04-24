<?php

// namespace Tests\Feature;

// use App\Models\User;
// use Illuminate\Foundation\Testing\RefreshDatabase;
// use Tests\TestCase;

// class AdminControllerTest extends TestCase
// {
//     use RefreshDatabase;

//     protected $admin;
//     protected $regularUser;

//     protected function setUp(): void
//     {
//         parent::setUp();
        
//         // Désactiver le middleware 'authorized'
//         $this->withoutMiddleware(\App\Http\Middleware\Authorized::class);
        
//         // Créer un utilisateur admin et un utilisateur standard pour les tests
//         $this->admin = User::factory()->create(['role' => 'admin']);
//         $this->regularUser = User::factory()->create(['name' => 'Regular User']);
//     }

//     public function test_admin_can_list_profiles()
//     {
//         $response = $this->actingAs($this->admin)->get('/admin/profiles');
        
//         $response->assertStatus(200);
//     }

//     public function test_admin_can_show_profile()
//     {
//         $response = $this->actingAs($this->admin)->get('/admin/profiles/' . $this->regularUser->id);
        
//         $response->assertStatus(200);
//     }

//     public function test_admin_can_edit_profile()
//     {
//         $response = $this->actingAs($this->admin)->get('/admin/profiles/' . $this->regularUser->id . '/edit');
        
//         $response->assertStatus(200);
//     }

//     public function test_admin_can_update_profile()
//     {
//         $updatedProfile = [
//             'name' => 'Updated User',
//             'email' => $this->regularUser->email,
//         ];
        
//         $response = $this->actingAs($this->admin)
//                          ->patch('/admin/profiles/' . $this->regularUser->id, $updatedProfile);
        
//         $response->assertRedirect('/admin/profiles/' . $this->regularUser->id);
//         $this->assertDatabaseHas('users', [
//             'id' => $this->regularUser->id,
//             'name' => 'Updated User'
//         ]);
//     }

//     public function test_admin_can_create_profile_form()
//     {
//         $response = $this->actingAs($this->admin)->get('/admin/profiles/create');
        
//         $response->assertStatus(200);
//     }

//     public function test_admin_can_store_profile()
//     {
//         $newProfile = [
//             'name' => 'New User',
//             'email' => 'newuser@example.com',
//             'password' => 'password',
//             'password_confirmation' => 'password'
//         ];
        
//         $response = $this->actingAs($this->admin)
//                          ->post('/admin/profiles', $newProfile);
        
//         $this->assertDatabaseHas('users', [
//             'name' => 'New User',
//             'email' => 'newuser@example.com'
//         ]);
        
//         $newUser = User::where('email', 'newuser@example.com')->first();
//         $response->assertRedirect('/admin/profiles/' . $newUser->id);
//     }

//     public function test_admin_can_delete_profile()
//     {
//         $response = $this->actingAs($this->admin)
//                          ->delete('/admin/profiles/' . $this->regularUser->id);
        
//         $response->assertRedirect('/admin/profiles/' . $this->regularUser->id);
//         $this->assertDatabaseMissing('users', [
//             'id' => $this->regularUser->id
//         ]);
//     }

//     public function test_non_admin_cannot_access_admin_routes()
//     {
//         // Pour ce test, nous voulons réactiver le middleware d'autorisation
//         $this->withMiddleware(\App\Http\Middleware\Authorized::class);
        
//         $response = $this->actingAs($this->regularUser)->get('/admin/profiles');
        
//         $response->assertStatus(403); // Forbidden
//     }
// }