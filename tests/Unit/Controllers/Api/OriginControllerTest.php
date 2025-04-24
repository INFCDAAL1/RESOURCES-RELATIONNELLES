<?php

// namespace Tests\Unit\Controllers\Api;

// use App\Models\Origin;
// use App\Models\User;
// use Illuminate\Foundation\Testing\RefreshDatabase;
// use Tests\TestCase;

// class OriginControllerTest extends TestCase
// {
//     use RefreshDatabase;

//     protected $admin;
//     protected $user;
//     protected $origin;

//     protected function setUp(): void
//     {
//         parent::setUp();
        
//         // Désactiver les middleware d'authentification pour les tests
//         $this->withoutMiddleware(\Tymon\JWTAuth\Http\Middleware\Authenticate::class);
//         $this->withoutMiddleware(\App\Http\Middleware\Authorized::class);
        
//         // Créer un utilisateur admin et un utilisateur standard
//         $this->admin = User::factory()->create(['role' => 'admin']);
//         $this->user = User::factory()->create();
        
//         // Créer une origine de test
//         $this->origin = Origin::create([
//             'libelle' => 'Test Origin ' . uniqid()
//         ]);
//     }

//     public function test_index_returns_origins()
//     {
//         $response = $this->actingAs($this->user)->get('/api/origins');
        
//         $response->assertStatus(200);
//     }

//     public function test_show_returns_single_origin()
//     {
//         $response = $this->actingAs($this->user)
//                          ->get('/api/origins/' . $this->origin->id);
        
//         $response->assertStatus(200);
//     }

//     public function test_store_creates_new_origin()
//     {
//         $newOrigin = [
//             'libelle' => 'New Origin ' . uniqid()
//         ];
        
//         $response = $this->actingAs($this->admin)
//                          ->post('/api/origins', $newOrigin);
        
//         $response->assertStatus(201);
//         $this->assertDatabaseHas('origins', ['libelle' => $newOrigin['libelle']]);
//     }

//     public function test_regular_user_cannot_create_origin()
//     {
//         // Pour ce test, nous voulons réactiver le middleware d'autorisation
//         // pour vérifier que les utilisateurs normaux ne peuvent pas créer d'origines
//         $this->withMiddleware(\App\Http\Middleware\Authorized::class);
        
//         $newOrigin = [
//             'libelle' => 'Regular User Origin ' . uniqid()
//         ];
        
//         $response = $this->actingAs($this->user)
//                          ->post('/api/origins', $newOrigin);
        
//         $response->assertStatus(403); // Forbidden
//     }

//     public function test_update_modifies_origin()
//     {
//         $updatedLibelle = 'Updated Origin ' . uniqid();
        
//         $response = $this->actingAs($this->admin)
//                          ->put('/api/origins/' . $this->origin->id, [
//                              'libelle' => $updatedLibelle
//                          ]);
        
//         $response->assertStatus(200);
//         $this->assertDatabaseHas('origins', [
//             'id' => $this->origin->id,
//             'libelle' => $updatedLibelle
//         ]);
//     }

//     public function test_destroy_deletes_origin()
//     {
//         // Créer une origine spécifique pour ce test
//         $originToDelete = Origin::create([
//             'libelle' => 'Origin To Delete ' . uniqid()
//         ]);
        
//         $response = $this->actingAs($this->admin)
//                          ->delete('/api/origins/' . $originToDelete->id);
        
//         $response->assertStatus(204); // No content
//         $this->assertDatabaseMissing('origins', ['id' => $originToDelete->id]);
//     }

//     public function test_cannot_delete_origin_with_resources()
//     {
//         // Créer une origine spécifique pour ce test
//         $originWithResource = Origin::create([
//             'libelle' => 'Origin With Resource ' . uniqid()
//         ]);
        
//         // Créer une ressource qui utilise cette origine
//         $type = \App\Models\Type::create(['name' => 'Test Type ' . uniqid()]);
//         $category = \App\Models\Category::create(['name' => 'Test Category ' . uniqid()]);
//         $visibility = \App\Models\Visibility::create(['name' => 'Test Visibility ' . uniqid()]);
        
//         \App\Models\Resource::create([
//             'name' => 'Test Resource',
//             'type_id' => $type->id,
//             'category_id' => $category->id,
//             'visibility_id' => $visibility->id,
//             'user_id' => $this->user->id,
//             'origin_id' => $originWithResource->id,
//             'link' => 'https://example.com'
//         ]);
        
//         $response = $this->actingAs($this->admin)
//                          ->delete('/api/origins/' . $originWithResource->id);
        
//         $response->assertStatus(409); // Conflict
//         $this->assertDatabaseHas('origins', ['id' => $originWithResource->id]);
//     }
// }