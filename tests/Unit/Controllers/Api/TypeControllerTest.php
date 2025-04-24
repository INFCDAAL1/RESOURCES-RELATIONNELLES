<?php

namespace Tests\Unit\Controllers\Api;

use App\Http\Controllers\Api\TypeController;
use App\Http\Requests\TypeRequest;
use App\Http\Resources\TypeResource;
use App\Models\Type;
use App\Models\Resource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Mockery;
use Tests\TestCase;

class TypeControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $controller;

    public function setUp(): void
    {
        parent::setUp();
        $this->controller = new TypeController();
    }

    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_index_returns_all_types()
    {
        // Utiliser un compteur pour s'assurer que les noms sont uniques
        static $counter = 0;
        $counter++;
        
        // Créer des types avec des noms garantis uniques
        $typeNames = [
            "Test Type A{$counter}_" . time(),
            "Test Type B{$counter}_" . time(),
            "Test Type C{$counter}_" . time(),
        ];
        
        $createdTypes = [];
        foreach ($typeNames as $name) {
            $createdTypes[] = Type::factory()->create(['name' => $name]);
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
        
        foreach ($typeNames as $name) {
            $this->assertContains($name, $responseNames);
        }
    }
    
    public function test_store_creates_new_type()
    {
        // Données du type
        $typeData = [
            'name' => 'Test Type'
        ];
        
        // Mock de la requête
        $request = Mockery::mock(TypeRequest::class);
        $request->shouldReceive('validated')
            ->once()
            ->andReturn($typeData);
        
        // Exécuter la méthode
        $response = $this->controller->store($request);
        
        // Vérifier les résultats
        $this->assertInstanceOf(TypeResource::class, $response);
        $this->assertEquals('Test Type', $response->resource->name);
        
        // Vérifier que le type est enregistré en base de données
        $this->assertDatabaseHas('types', ['name' => 'Test Type']);
    }

    public function test_show_returns_specified_type()
    {
        // Créer un type
        $type = Type::factory()->create(['name' => 'Test Type Show']);
        
        // Exécuter la méthode
        $response = $this->controller->show($type);
        
        // Vérifier les résultats
        $this->assertInstanceOf(TypeResource::class, $response);
        $this->assertEquals('Test Type Show', $response->resource->name);
    }

    public function test_update_modifies_existing_type()
    {
        // Créer un type
        $type = Type::factory()->create(['name' => 'Original Name']);
        
        // Données de mise à jour
        $updateData = [
            'name' => 'Updated Name'
        ];
        
        // Mock de la requête
        $request = Mockery::mock(TypeRequest::class);
        $request->shouldReceive('validated')
            ->once()
            ->andReturn($updateData);
        
        // Exécuter la méthode
        $response = $this->controller->update($request, $type);
        
        // Vérifier les résultats
        $this->assertInstanceOf(TypeResource::class, $response);
        $this->assertEquals('Updated Name', $response->resource->name);
        
        // Vérifier que le type est mis à jour en base de données
        $this->assertDatabaseHas('types', ['name' => 'Updated Name']);
    }

    public function test_destroy_removes_type_with_no_resources()
    {
        // Créer un type
        $type = Type::factory()->create(['name' => 'Type To Delete']);
        
        // Mock permettant de simuler l'absence de ressources
        $typeMock = Mockery::mock(Type::class)->makePartial();
        $typeMock->shouldReceive('resources->count')
            ->once()
            ->andReturn(0);
        
        // Simuler l'appel à delete() sur le mock
        $typeMock->shouldReceive('delete')
            ->once()
            ->andReturn(true);
        
        // Exécuter la méthode
        $response = $this->controller->destroy($typeMock);
        
        // Vérifier les résultats
        $this->assertEquals('Type deleted successfully', json_decode($response->getContent())->message);
    }

    public function test_destroy_fails_when_type_has_resources()
    {
        // Créer un type
        $type = Type::factory()->create(['name' => 'Type With Resources']);
        
        // Mock pour simuler la présence de ressources
        $typeMock = Mockery::mock(Type::class)->makePartial();
        $typeMock->shouldReceive('resources->count')
            ->once()
            ->andReturn(3);
        
        // Exécuter la méthode
        $response = $this->controller->destroy($typeMock);
        
        // Vérifier les résultats
        $this->assertEquals(Response::HTTP_CONFLICT, $response->status());
        $this->assertEquals('Cannot delete this type as it is being used by resources', json_decode($response->getContent())->message);
    }
}