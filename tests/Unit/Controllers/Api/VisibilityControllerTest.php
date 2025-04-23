<?php

namespace Tests\Unit\Controllers\Api;

use App\Http\Controllers\Api\VisibilityController;
use App\Http\Requests\VisibilityRequest;
use App\Http\Resources\VisibilityResource;
use App\Models\Visibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Mockery;
use Tests\TestCase;

class VisibilityControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $controller;

    public function setUp(): void
    {
        parent::setUp();
        $this->controller = new VisibilityController();
    }

    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_index_returns_all_visibilities()
    {
        // Créer quelques visibilités
        Visibility::factory()->count(3)->create();
        
        // Exécuter la méthode
        $response = $this->controller->index();
        
        // Vérifier les résultats
        $this->assertInstanceOf(\Illuminate\Http\Resources\Json\AnonymousResourceCollection::class, $response);
        $this->assertEquals(3, $response->resource->count());
    }

    public function test_store_creates_new_visibility()
    {
        // Données de visibilité
        $visibilityData = [
            'name' => 'Test Visibility',
            'url' => 'https://example.com/visibility'
        ];
        
        // Mock de la requête
        $request = Mockery::mock(VisibilityRequest::class);
        $request->shouldReceive('validated')
            ->once()
            ->andReturn($visibilityData);
        
        // Exécuter la méthode
        $response = $this->controller->store($request);
        
        // Vérifier les résultats
        $this->assertInstanceOf(VisibilityResource::class, $response);
        $this->assertEquals('Test Visibility', $response->resource->name);
        $this->assertEquals('https://example.com/visibility', $response->resource->url);
        
        // Vérifier que la visibilité est enregistrée en base de données
        $this->assertDatabaseHas('visibilities', [
            'name' => 'Test Visibility',
            'url' => 'https://example.com/visibility'
        ]);
    }

    public function test_show_returns_specified_visibility()
    {
        // Créer une visibilité
        $visibility = Visibility::factory()->create([
            'name' => 'Test Visibility Show',
            'url' => 'https://example.com/visibility-show'
        ]);
        
        // Exécuter la méthode
        $response = $this->controller->show($visibility);
        
        // Vérifier les résultats
        $this->assertInstanceOf(VisibilityResource::class, $response);
        $this->assertEquals('Test Visibility Show', $response->resource->name);
        $this->assertEquals('https://example.com/visibility-show', $response->resource->url);
    }

    public function test_update_modifies_existing_visibility()
    {
        // Créer une visibilité
        $visibility = Visibility::factory()->create([
            'name' => 'Original Name',
            'url' => 'https://example.com/original'
        ]);
        
        // Données de mise à jour
        $updateData = [
            'name' => 'Updated Name',
            'url' => 'https://example.com/updated'
        ];
        
        // Mock de la requête
        $request = Mockery::mock(VisibilityRequest::class);
        $request->shouldReceive('validated')
            ->once()
            ->andReturn($updateData);
        
        // Exécuter la méthode
        $response = $this->controller->update($request, $visibility);
        
        // Vérifier les résultats
        $this->assertInstanceOf(VisibilityResource::class, $response);
        $this->assertEquals('Updated Name', $response->resource->name);
        $this->assertEquals('https://example.com/updated', $response->resource->url);
        
        // Vérifier que la visibilité est mise à jour en base de données
        $this->assertDatabaseHas('visibilities', [
            'name' => 'Updated Name',
            'url' => 'https://example.com/updated'
        ]);
    }

    public function test_destroy_removes_visibility_with_no_resources()
    {
        // Créer une visibilité
        $visibility = Visibility::factory()->create([
            'name' => 'Visibility To Delete'
        ]);
        
        // Mock permettant de simuler l'absence de ressources
        $visibilityMock = Mockery::mock(Visibility::class)->makePartial();
        $visibilityMock->shouldReceive('resources->count')
            ->once()
            ->andReturn(0);
        
        // Simuler l'appel à delete() sur le mock
        $visibilityMock->shouldReceive('delete')
            ->once()
            ->andReturn(true);
        
        // Exécuter la méthode
        $response = $this->controller->destroy($visibilityMock);
        
        // Vérifier les résultats
        $this->assertEquals(Response::HTTP_NO_CONTENT, $response->status());
    }

    public function test_destroy_fails_when_visibility_has_resources()
    {
        // Créer une visibilité
        $visibility = Visibility::factory()->create([
            'name' => 'Visibility With Resources'
        ]);
        
        // Mock pour simuler la présence de ressources
        $visibilityMock = Mockery::mock(Visibility::class)->makePartial();
        $visibilityMock->shouldReceive('resources->count')
            ->once()
            ->andReturn(2);
        
        // Exécuter la méthode
        $response = $this->controller->destroy($visibilityMock);
        
        // Vérifier les résultats
        $this->assertEquals(Response::HTTP_CONFLICT, $response->status());
        $this->assertEquals('Cannot delete this visibility because it is used by resources', json_decode($response->getContent())->message);
    }
}