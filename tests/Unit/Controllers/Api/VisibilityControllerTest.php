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
        // Utiliser un compteur pour s'assurer que les noms sont uniques
        static $counter = 0;
        $counter++;
        
        // Créer des visibilités avec des noms garantis uniques
        $visibilityNames = [
            "Test Visibility A{$counter}_" . time(),
            "Test Visibility B{$counter}_" . time(),
            "Test Visibility C{$counter}_" . time(),
        ];
        
        $createdVisibilities = [];
        foreach ($visibilityNames as $name) {
            $createdVisibilities[] = Visibility::factory()->create(['name' => $name]);
        }
        
        // Exécuter la méthode
        $response = $this->controller->index();
        
        // Vérifier les résultats
        $this->assertInstanceOf(\Illuminate\Http\Resources\Json\AnonymousResourceCollection::class, $response);
        
        // Vérifier que tous nos types créés sont présents dans la réponse
        $responseData = $response->resource->toArray();
        $responseNames = array_map(function($item) {
            return $item['name'];
        }, $responseData);
        
        foreach ($visibilityNames as $name) {
            $this->assertContains($name, $responseNames);
        }
    }

    public function test_store_creates_new_visibility()
    {
        // Créer un nom unique pour cette visibilité
        $uniqueName = 'Test Visibility ' . uniqid() . '_' . time();
        
        // Données de visibilité
        $visibilityData = [
            'name' => $uniqueName
        ];
        
        // Ajouter le champ 'url' seulement s'il est présent dans le modèle
        if (in_array('url', (new Visibility())->getFillable())) {
            $visibilityData['url'] = 'https://example.com/visibility-' . uniqid();
        }
        
        // Mock de la requête
        $request = Mockery::mock(VisibilityRequest::class);
        $request->shouldReceive('validated')
            ->once()
            ->andReturn($visibilityData);
        
        // Exécuter la méthode
        $response = $this->controller->store($request);
        
        // Vérifier les résultats
        $this->assertInstanceOf(VisibilityResource::class, $response);
        $this->assertEquals($uniqueName, $response->resource->name);
        
        if (isset($visibilityData['url'])) {
            $this->assertEquals($visibilityData['url'], $response->resource->url);
        }
        
        // Vérifier que la visibilité est enregistrée en base de données
        $this->assertDatabaseHas('visibilities', [
            'name' => $uniqueName
        ]);
    }

    public function test_show_returns_specified_visibility()
    {
        // Créer une visibilité avec un nom unique
        $uniqueName = 'Test Visibility Show ' . uniqid() . '_' . time();
        $visibilityData = ['name' => $uniqueName];
        
        // Ajouter le champ 'url' seulement s'il est présent dans le modèle
        if (in_array('url', (new Visibility())->getFillable())) {
            $visibilityData['url'] = 'https://example.com/visibility-show-' . uniqid();
        }
        
        $visibility = Visibility::factory()->create($visibilityData);
        
        // Exécuter la méthode
        $response = $this->controller->show($visibility);
        
        // Vérifier les résultats
        $this->assertInstanceOf(VisibilityResource::class, $response);
        $this->assertEquals($uniqueName, $response->resource->name);
        
        if (isset($visibilityData['url'])) {
            $this->assertEquals($visibilityData['url'], $response->resource->url);
        }
    }

    public function test_update_modifies_existing_visibility()
    {
        // Créer une visibilité avec un nom unique
        $originalName = 'Original Name ' . uniqid() . '_' . time();
        $visibilityData = ['name' => $originalName];
        
        // Ajouter le champ 'url' seulement s'il est présent dans le modèle
        if (in_array('url', (new Visibility())->getFillable())) {
            $visibilityData['url'] = 'https://example.com/original-' . uniqid();
        }
        
        $visibility = Visibility::factory()->create($visibilityData);
        
        // Créer un nom unique pour la mise à jour
        $updatedName = 'Updated Name ' . uniqid() . '_' . time();
        
        // Données de mise à jour
        $updateData = ['name' => $updatedName];
        
        // Ajouter le champ 'url' seulement s'il est présent dans le modèle
        if (in_array('url', (new Visibility())->getFillable())) {
            $updateData['url'] = 'https://example.com/updated-' . uniqid();
        }
        
        // Mock de la requête
        $request = Mockery::mock(VisibilityRequest::class);
        $request->shouldReceive('validated')
            ->once()
            ->andReturn($updateData);
        
        // Exécuter la méthode
        $response = $this->controller->update($request, $visibility);
        
        // Vérifier les résultats
        $this->assertInstanceOf(VisibilityResource::class, $response);
        $this->assertEquals($updatedName, $response->resource->name);
        
        if (isset($updateData['url'])) {
            $this->assertEquals($updateData['url'], $response->resource->url);
        }
        
        // Vérifier que la visibilité est mise à jour en base de données
        $this->assertDatabaseHas('visibilities', [
            'name' => $updatedName
        ]);
    }

    public function test_destroy_removes_visibility_with_no_resources()
    {
        // Créer une visibilité
        $visibility = Visibility::factory()->create([
            'name' => 'Visibility To Delete ' . uniqid() . '_' . time()
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
            'name' => 'Visibility With Resources ' . uniqid() . '_' . time()
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