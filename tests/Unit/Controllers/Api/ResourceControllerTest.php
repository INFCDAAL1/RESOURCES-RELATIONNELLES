<?php

namespace Tests\Unit\Controllers\Api;

use App\Http\Controllers\Api\ResourceController;
use App\Http\Requests\ResourceRequest;
use App\Http\Resources\ResourceResource;
use App\Models\Resource;
use App\Models\User;
use App\Models\Type;
use App\Models\Category;
use App\Models\Visibility;
use App\Models\Origin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class ResourceControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $controller;
    protected $user;

    public function setUp(): void
    {
        parent::setUp();
        
        // Créer les dépendances nécessaires
        $this->user = User::factory()->create(['role' => 'admin']);
        
        // Mocker le middleware
        $this->controller = Mockery::mock(ResourceController::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
        
        // Éviter l'appel à middleware()
        $this->controller->shouldReceive('middleware')
            ->andReturn($this->controller);
        
        // Mock Auth
        Auth::shouldReceive('id')->andReturn($this->user->id);
        Auth::shouldReceive('user')->andReturn($this->user);
        
        // Configurer le stockage test
        Storage::fake('local');
    }

    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_store_creates_new_resource_with_file()
    {
        // Créer les dépendances
        $type = Type::factory()->create();
        $category = Category::factory()->create();
        $visibility = Visibility::factory()->create();
        $origin = Origin::factory()->create();
        
        // Données du resource
        $resourceData = [
            'name' => 'Test Resource',
            'description' => 'Test Description',
            'published' => true,
            'type_id' => $type->id,
            'category_id' => $category->id,
            'visibility_id' => $visibility->id,
            'origin_id' => $origin->id,
            'link' => 'https://example.com/test-resource'
        ];
        
        $file = UploadedFile::fake()->create('document.pdf', 500);
        
        // Mock ResourceRequest
        $request = Mockery::mock(ResourceRequest::class);
        $request->shouldReceive('validated')
            ->once()
            ->andReturn($resourceData);
        $request->shouldReceive('hasFile')
            ->with('file')
            ->once()
            ->andReturn(true);
        $request->shouldReceive('file')
            ->with('file')
            ->once()
            ->andReturn($file);
        
        // Créer un mock partiel de Resource
        $resource = Mockery::mock(Resource::class)->makePartial();
        $resource->shouldReceive('save')->andReturn(true);
        $resource->shouldReceive('uploadFile')->once()->andReturn('resources/test.pdf');
        $resource->shouldReceive('load')->andReturn($resource);
        
        // Remplacer new Resource() par notre mock
        $this->app->bind(Resource::class, function() use ($resource) {
            return $resource;
        });
        
        // Exécuter la méthode à tester
        $response = $this->controller->store($request);
        
        // Vérifier les résultats
        $this->assertInstanceOf(ResourceResource::class, $response);
    }

    public function test_show_returns_resource_for_admin()
    {
        // Arrange
        $type = Type::factory()->create();
        $category = Category::factory()->create();
        $visibility = Visibility::factory()->create();
        $origin = Origin::factory()->create();
        
        $resource = Mockery::mock(Resource::class)->makePartial();
        $resource->user_id = $this->user->id;
        $resource->published = true;
        $resource->validated = true;
        
        $resource->shouldReceive('load')
            ->with(['type', 'category', 'visibility', 'user', 'origin'])
            ->andReturn($resource);
        
        // Admin peut voir toutes les ressources
        Auth::shouldReceive('user->isAdmin')->andReturn(true);
        
        // Act
        $response = $this->controller->show($resource);
        
        // Assert
        $this->assertInstanceOf(ResourceResource::class, $response);
    }

    public function test_show_returns_403_for_non_admin_unpublished_resource()
    {
        // Arrange
        $resource = Mockery::mock(Resource::class)->makePartial();
        $resource->published = false;
        $resource->validated = true;
        $resource->user_id = 999; // Différent de l'utilisateur authentifié
        
        // Utilisateur non-admin
        Auth::shouldReceive('user->isAdmin')->andReturn(false);
        
        // Act
        $response = $this->controller->show($resource);
        
        // Assert
        $this->assertEquals(403, $response->status());
        $this->assertEquals('Unauthorized', json_decode($response->getContent())->message);
    }

    public function test_download_returns_file_for_authorized_user()
    {
        // Arrange
        $resource = Mockery::mock(Resource::class)->makePartial();
        $resource->file_path = 'resources/document.pdf';
        $resource->name = 'Test Document';
        $resource->published = true;
        $resource->validated = true;
        
        // Admin user
        Auth::shouldReceive('user->isAdmin')->andReturn(true);
        
        // Mock Storage facade
        Storage::shouldReceive('exists')
            ->with($resource->file_path)
            ->andReturn(true);
        Storage::shouldReceive('download')
            ->with($resource->file_path, 'Test Document.pdf')
            ->andReturn(response('file content'));
        
        // Act
        $response = $this->controller->download($resource);
        
        // Assert
        $this->assertEquals('file content', $response->getContent());
    }

    public function test_download_returns_404_when_file_not_found()
    {
        // Arrange
        $resource = Mockery::mock(Resource::class)->makePartial();
        $resource->file_path = 'resources/nonexistent.pdf';
        $resource->published = true;
        $resource->validated = true;
        
        // Admin user
        Auth::shouldReceive('user->isAdmin')->andReturn(true);
        
        // Mock Storage facade
        Storage::shouldReceive('exists')
            ->with($resource->file_path)
            ->andReturn(false);
        
        // Act
        $response = $this->controller->download($resource);
        
        // Assert
        $this->assertEquals(404, $response->status());
        $this->assertEquals('File not found', json_decode($response->getContent())->message);
    }
}