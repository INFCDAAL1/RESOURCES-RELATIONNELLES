<?php

namespace Tests\Unit\Controllers\Api;

use App\Http\Controllers\Api\ResourceInteractionController;
use App\Http\Requests\ResourceInteractionRequest;
use App\Http\Resources\ResourceInteractionResource;
use App\Models\ResourceInteraction;
use App\Models\Resource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Mockery;
use Tests\TestCase;

class ResourceInteractionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $controller;
    protected $user;
    protected $resource;

    public function setUp(): void
    {
        parent::setUp();
        $this->controller = new ResourceInteractionController();
        $this->user = User::factory()->create(['role' => 'user']);
        
        // Créer les dépendances nécessaires pour les ressources
        $type = \App\Models\Type::factory()->create(['name' => 'Type-' . uniqid()]);
        $category = \App\Models\Category::factory()->create(['name' => 'Category-' . uniqid()]);
        $visibility = \App\Models\Visibility::factory()->create(['name' => 'Visibility-' . uniqid()]);
        $origin = \App\Models\Origin::factory()->create(['libelle' => 'Origin-' . uniqid()]);
        
        // Créer une ressource
        $this->resource = Resource::factory()->create([
            'type_id' => $type->id,
            'category_id' => $category->id,
            'visibility_id' => $visibility->id,
            'user_id' => $this->user->id,
            'origin_id' => $origin->id,
            'link' => 'https://example.com/resource'
        ]);
    }

    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_index_returns_user_interactions()
    {
        // Créer des interactions
        ResourceInteraction::create([
            'user_id' => $this->user->id,
            'resource_id' => $this->resource->id,
            'type' => 'favorite',
            'notes' => 'Test favorite'
        ]);
        
        // Préparer les paramètres de la requête
        request()->merge(['resource_id' => $this->resource->id]);
        
        // Mock Auth
        $userMock = Mockery::mock(User::class);
        $userMock->shouldReceive('isAdmin')->andReturn(false);
        Auth::shouldReceive('user')->andReturn($userMock);
        
        Auth::shouldReceive('id')
            ->andReturn($this->user->id);
        
        // Exécuter la méthode
        $response = $this->controller->index();
        
        // Vérifier les résultats
        $this->assertInstanceOf(\Illuminate\Http\Resources\Json\AnonymousResourceCollection::class, $response);
        $this->assertEquals(1, $response->resource->count());
    }

    public function test_store_creates_new_interaction()
    {
        // Données d'interaction
        $interactionData = [
            'resource_id' => $this->resource->id,
            'type' => 'favorite',
            'notes' => 'Test favorite notes'
        ];
        
        // Mock de la requête
        $request = Mockery::mock(ResourceInteractionRequest::class);
        $request->shouldReceive('validated')
            ->once()
            ->andReturn($interactionData);
        
        // Mock Auth
        Auth::shouldReceive('id')
            ->andReturn($this->user->id);
        
        // Exécuter la méthode
        $response = $this->controller->store($request);
        
        // Vérifier les résultats
        $this->assertInstanceOf(ResourceInteractionResource::class, $response);
        
        // Vérifier que l'interaction est bien enregistrée
        $this->assertDatabaseHas('resource_interactions', [
            'user_id' => $this->user->id,
            'resource_id' => $this->resource->id,
            'type' => 'favorite',
            'notes' => 'Test favorite notes'
        ]);
    }

    public function test_store_updates_existing_interaction()
    {
        // Créer une interaction existante
        $interaction = ResourceInteraction::create([
            'user_id' => $this->user->id,
            'resource_id' => $this->resource->id,
            'type' => 'favorite',
            'notes' => 'Original notes'
        ]);
        
        // Données de mise à jour
        $updateData = [
            'resource_id' => $this->resource->id,
            'type' => 'favorite',
            'notes' => 'Updated notes'
        ];
        
        // Mock de la requête
        $request = Mockery::mock(ResourceInteractionRequest::class);
        $request->shouldReceive('validated')
            ->once()
            ->andReturn($updateData);
        
        // Mock Auth
        Auth::shouldReceive('id')
            ->andReturn($this->user->id);
        
        // Exécuter la méthode
        $response = $this->controller->store($request);
        
        // Vérifier les résultats
        $this->assertInstanceOf(ResourceInteractionResource::class, $response);
        
        // Vérifier que l'interaction est bien mise à jour
        $this->assertDatabaseHas('resource_interactions', [
            'user_id' => $this->user->id,
            'resource_id' => $this->resource->id,
            'type' => 'favorite',
            'notes' => 'Updated notes'
        ]);
    }

    public function test_show_returns_interaction_for_owner()
    {
        // Créer une interaction
        $interaction = ResourceInteraction::create([
            'user_id' => $this->user->id,
            'resource_id' => $this->resource->id,
            'type' => 'favorite',
            'notes' => 'Test notes'
        ]);
        
        // Mock Auth
        Auth::shouldReceive('id')
            ->andReturn($this->user->id);
            
        // Correction pour Auth::user()->isAdmin()
        $userMock = Mockery::mock(User::class);
        $userMock->shouldReceive('isAdmin')->andReturn(false);
        Auth::shouldReceive('user')->andReturn($userMock);
        
        // Exécuter la méthode
        $response = $this->controller->show($interaction);
        
        // Vérifier les résultats
        $this->assertInstanceOf(ResourceInteractionResource::class, $response);
    }

    public function test_show_returns_interaction_for_admin()
    {
        // Créer un autre utilisateur
        $otherUser = User::factory()->create(['role' => 'user']);
        
        // Créer une interaction d'un autre utilisateur
        $interaction = ResourceInteraction::create([
            'user_id' => $otherUser->id,
            'resource_id' => $this->resource->id,
            'type' => 'favorite',
            'notes' => 'Other user notes'
        ]);
        
        // Mock Auth (admin)
        Auth::shouldReceive('id')
            ->andReturn($this->user->id);
            
        // Correction pour Auth::user()->isAdmin()
        $userMock = Mockery::mock(User::class);
        $userMock->shouldReceive('isAdmin')->andReturn(true);
        Auth::shouldReceive('user')->andReturn($userMock);
        
        // Exécuter la méthode
        $response = $this->controller->show($interaction);
        
        // Vérifier les résultats
        $this->assertInstanceOf(ResourceInteractionResource::class, $response);
    }

    public function test_show_returns_forbidden_for_other_user()
    {
        // Créer un autre utilisateur
        $otherUser = User::factory()->create(['role' => 'user']);
        
        // Créer une interaction d'un autre utilisateur
        $interaction = ResourceInteraction::create([
            'user_id' => $otherUser->id,
            'resource_id' => $this->resource->id,
            'type' => 'favorite',
            'notes' => 'Other user notes'
        ]);
        
        // Mock Auth (non-admin, non-propriétaire)
        Auth::shouldReceive('id')
            ->andReturn($this->user->id);
            
        // Correction pour Auth::user()->isAdmin()
        $userMock = Mockery::mock(User::class);
        $userMock->shouldReceive('isAdmin')->andReturn(false);
        Auth::shouldReceive('user')->andReturn($userMock);
        
        // Exécuter la méthode
        $response = $this->controller->show($interaction);
        
        // Vérifier les résultats
        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->status());
        $this->assertEquals('Unauthorized', json_decode($response->getContent())->message);
    }

    public function test_update_modifies_interaction_for_owner()
    {
        // Créer une interaction
        $interaction = ResourceInteraction::create([
            'user_id' => $this->user->id,
            'resource_id' => $this->resource->id,
            'type' => 'favorite',
            'notes' => 'Original notes'
        ]);
        
        // Données de mise à jour
        $updateData = [
            'notes' => 'Updated notes'
        ];
        
        // Mock de la requête
        $request = Mockery::mock(ResourceInteractionRequest::class);
        $request->shouldReceive('validated')
            ->once()
            ->andReturn($updateData);
        
        // Mock Auth
        Auth::shouldReceive('id')
            ->andReturn($this->user->id);
            
        // Correction: Ajouter le mock pour Auth::user()
        $userMock = Mockery::mock(User::class);
        $userMock->shouldReceive('isAdmin')->andReturn(false);
        Auth::shouldReceive('user')->andReturn($userMock);
        
        // Exécuter la méthode
        $response = $this->controller->update($request, $interaction);
        
        // Vérifier les résultats
        $this->assertInstanceOf(ResourceInteractionResource::class, $response);
        
        // Vérifier que l'interaction est bien mise à jour
        $this->assertEquals('Updated notes', $interaction->fresh()->notes);
    }

    public function test_update_returns_forbidden_for_other_user()
    {
        // Créer un autre utilisateur
        $otherUser = User::factory()->create(['role' => 'user']);
        
        // Créer une interaction d'un autre utilisateur
        $interaction = ResourceInteraction::create([
            'user_id' => $otherUser->id,
            'resource_id' => $this->resource->id,
            'type' => 'favorite',
            'notes' => 'Other user notes'
        ]);
        
        // Données de mise à jour
        $updateData = [
            'notes' => 'Trying to update'
        ];
        
        // Mock de la requête
        $request = Mockery::mock(ResourceInteractionRequest::class);
        $request->shouldReceive('validated')
            ->andReturn($updateData);
        
        // Mock Auth (non-propriétaire)
        Auth::shouldReceive('id')
            ->andReturn($this->user->id);
            
        // Correction: Ajouter le mock pour Auth::user()
        $userMock = Mockery::mock(User::class);
        $userMock->shouldReceive('isAdmin')->andReturn(false);
        Auth::shouldReceive('user')->andReturn($userMock);
        
        // Exécuter la méthode
        $response = $this->controller->update($request, $interaction);
        
        // Vérifier les résultats
        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->status());
        $this->assertEquals('Unauthorized', json_decode($response->getContent())->message);
    }

    public function test_destroy_deletes_interaction_for_owner()
    {
        // Créer une interaction
        $interaction = ResourceInteraction::create([
            'user_id' => $this->user->id,
            'resource_id' => $this->resource->id,
            'type' => 'favorite',
            'notes' => 'Notes to delete'
        ]);
        
        // Mock Auth
        Auth::shouldReceive('id')
            ->andReturn($this->user->id);
            
        // Correction pour Auth::user()->isAdmin()
        $userMock = Mockery::mock(User::class);
        $userMock->shouldReceive('isAdmin')->andReturn(false);
        Auth::shouldReceive('user')->andReturn($userMock);
        
        // Exécuter la méthode
        $response = $this->controller->destroy($interaction);
        
        // Vérifier les résultats
        $this->assertEquals(Response::HTTP_NO_CONTENT, $response->status());
        $this->assertDatabaseMissing('resource_interactions', ['id' => $interaction->id]);
    }

    public function test_destroy_deletes_interaction_for_admin()
    {
        // Créer un autre utilisateur
        $otherUser = User::factory()->create(['role' => 'user']);
        
        // Créer une interaction d'un autre utilisateur
        $interaction = ResourceInteraction::create([
            'user_id' => $otherUser->id,
            'resource_id' => $this->resource->id,
            'type' => 'favorite',
            'notes' => 'Other user notes'
        ]);
        
        // Mock Auth (admin)
        Auth::shouldReceive('id')
            ->andReturn($this->user->id);
            
        // Correction pour Auth::user()->isAdmin()
        $userMock = Mockery::mock(User::class);
        $userMock->shouldReceive('isAdmin')->andReturn(true);
        Auth::shouldReceive('user')->andReturn($userMock);
        
        // Exécuter la méthode
        $response = $this->controller->destroy($interaction);
        
        // Vérifier les résultats
        $this->assertEquals(Response::HTTP_NO_CONTENT, $response->status());
        $this->assertDatabaseMissing('resource_interactions', ['id' => $interaction->id]);
    }
}