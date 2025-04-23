<?php

namespace Tests\Unit\Controllers\Api;

use App\Http\Controllers\Api\CategoryController;
use App\Http\Requests\CategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Mockery;
use Tests\TestCase;

class CategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $controller;

    public function setUp(): void
    {
        parent::setUp();
        $this->controller = new CategoryController();
    }

    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_index_returns_all_categories()
    {
        // Arrange
        $categories = Category::factory()->count(3)->create();
        
        // Act
        $response = $this->controller->index();
        
        // Assert
        $this->assertInstanceOf(\Illuminate\Http\Resources\Json\AnonymousResourceCollection::class, $response);
        $this->assertEquals(3, $response->resource->count());
    }

    public function test_store_creates_new_category()
    {
        // Arrange
        $categoryData = ['name' => 'Test Category'];
        
        $request = Mockery::mock(CategoryRequest::class);
        $request->shouldReceive('validated')
            ->once()
            ->andReturn($categoryData);
        
        // Act
        $response = $this->controller->store($request);
        
        // Assert
        $this->assertInstanceOf(CategoryResource::class, $response);
        $this->assertEquals('Test Category', $response->resource->name);
        $this->assertDatabaseHas('categories', ['name' => 'Test Category']);
    }

    public function test_show_returns_specified_category()
{
    // Arrange
    $uniqueName = 'Test Category ' . uniqid();
    $category = Category::factory()->create(['name' => $uniqueName]);
    
    // Act
    $response = $this->controller->show($category);
    
    // Assert
    $this->assertInstanceOf(CategoryResource::class, $response);
    $this->assertEquals($uniqueName, $response->resource->name);
}

    public function test_update_modifies_existing_category()
    {
        // Arrange
        $category = Category::factory()->create(['name' => 'Old Name']);
        $updatedData = ['name' => 'New Name'];
        
        $request = Mockery::mock(CategoryRequest::class);
        $request->shouldReceive('validated')
            ->once()
            ->andReturn($updatedData);
        
        // Act
        $response = $this->controller->update($request, $category);
        
        // Assert
        $this->assertInstanceOf(CategoryResource::class, $response);
        $this->assertEquals('New Name', $response->resource->name);
        $this->assertDatabaseHas('categories', ['name' => 'New Name']);
    }

    public function test_destroy_removes_category_when_no_resources()
    {
        // Arrange
        $category = Category::factory()->create();
        
        // Mock the resources method to return a collection with count 0
        $categoryMock = Mockery::mock(Category::class)->makePartial();
        $categoryMock->shouldReceive('resources->count')
            ->once()
            ->andReturn(0);
        
        // Act
        $response = $this->controller->destroy($categoryMock);
        
        // Assert
        $this->assertEquals(204, $response->status());
    }

    public function test_destroy_fails_when_category_has_resources()
    {
        // Arrange
        $category = Category::factory()->create();
        
        // Mock the resources method to return a collection with count > 0
        $categoryMock = Mockery::mock(Category::class)->makePartial();
        $categoryMock->shouldReceive('resources->count')
            ->once()
            ->andReturn(5);
        
        // Act
        $response = $this->controller->destroy($categoryMock);
        
        // Assert
        $this->assertEquals(409, $response->status());
        $this->assertEquals('Cannot delete this category because it is used by resources', json_decode($response->getContent())->message);
    }
}