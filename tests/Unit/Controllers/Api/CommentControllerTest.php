<?php

namespace Tests\Unit\Controllers\Api;

use App\Http\Controllers\Api\CommentController;
use App\Http\Requests\CommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Models\Resource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Mockery;
use Tests\TestCase;

class CommentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $controller;
    protected $user;
    protected $resource;

    public function setUp(): void
    {
        parent::setUp();
        $this->controller = new CommentController();
        $this->user = User::factory()->create(['role' => 'admin']);
        
        // Créer les dépendances nécessaires pour les ressources
        $type = \App\Models\Type::factory()->create();
        $category = \App\Models\Category::factory()->create();
        $visibility = \App\Models\Visibility::factory()->create();
        $origin = \App\Models\Origin::factory()->create();
        
        // Créer une ressource sans les champs problématiques
        $this->resource = Resource::factory()->create([
            'type_id' => $type->id,
            'category_id' => $category->id,
            'visibility_id' => $visibility->id,
            'user_id' => $this->user->id,
            'origin_id' => $origin->id,
            'link' => 'https://example.com/resource',
        ]);
    }

    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_index_returns_published_comments()
    {
        // Créer des commentaires publiés
        Comment::create([
            'content' => 'Test comment 1',
            'status' => 'published',
            'resource_id' => $this->resource->id,
            'user_id' => $this->user->id
        ]);
        
        Comment::create([
            'content' => 'Test comment 2',
            'status' => 'published',
            'resource_id' => $this->resource->id,
            'user_id' => $this->user->id
        ]);
        
        // Mock pour simuler la requête
        request()->merge(['resource_id' => $this->resource->id]);
        
        // Mock Auth
        Auth::shouldReceive('user->isAdmin')->andReturn(true);
        
        // Exécuter la méthode
        $response = $this->controller->index();
        
        // Vérifier les résultats
        $this->assertInstanceOf(\Illuminate\Http\Resources\Json\AnonymousResourceCollection::class, $response);
        $this->assertEquals(2, $response->resource->count());
    }

    public function test_store_creates_new_comment()
    {
        // Créer les données du commentaire
        $commentData = [
            'content' => 'New comment for testing',
            'resource_id' => $this->resource->id
        ];
        
        // Mock de la requête
        $request = Mockery::mock(CommentRequest::class);
        $request->shouldReceive('validated')
            ->once()
            ->andReturn($commentData);
        
        // Mock Auth
        Auth::shouldReceive('id')
            ->andReturn($this->user->id);
        
        // Exécuter la méthode
        $response = $this->controller->store($request);
        
        // Vérifier les résultats
        $this->assertInstanceOf(CommentResource::class, $response);
        
        // Vérifier que le commentaire est bien enregistré en base de données
        $this->assertDatabaseHas('comments', [
            'content' => 'New comment for testing',
            'resource_id' => $this->resource->id,
            'user_id' => $this->user->id,
            'status' => 'published'
        ]);
    }

    public function test_show_returns_comment_for_owner()
    {
        // Créer un commentaire
        $comment = Comment::create([
            'content' => 'Owner comment',
            'status' => 'published',
            'resource_id' => $this->resource->id,
            'user_id' => $this->user->id
        ]);
        
        // Mock Auth
        Auth::shouldReceive('id')
            ->andReturn($this->user->id);
        Auth::shouldReceive('user->isAdmin')
            ->andReturn(false);
        
        // Exécuter la méthode
        $response = $this->controller->show($comment);
        
        // Vérifier les résultats
        $this->assertInstanceOf(CommentResource::class, $response);
    }

    public function test_show_returns_forbidden_for_hidden_comment()
    {
        // Créer un autre utilisateur
        $otherUser = User::factory()->create(['role' => 'user']);
        
        // Créer un commentaire caché d'un autre utilisateur
        $comment = Comment::create([
            'content' => 'Hidden comment',
            'status' => 'hidden',
            'resource_id' => $this->resource->id,
            'user_id' => $otherUser->id
        ]);
        
        // Mock Auth (non-admin, non-propriétaire)
        Auth::shouldReceive('id')
            ->andReturn($this->user->id);
        Auth::shouldReceive('user->isAdmin')
            ->andReturn(false);
        
        // Exécuter la méthode
        $response = $this->controller->show($comment);
        
        // Vérifier les résultats
        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->status());
        $this->assertEquals('Unauthorized', json_decode($response->getContent())->message);
    }

    public function test_update_modifies_comment_for_owner()
    {
        // Créer un commentaire
        $comment = Comment::create([
            'content' => 'Original content',
            'status' => 'published',
            'resource_id' => $this->resource->id,
            'user_id' => $this->user->id
        ]);
        
        // Données de mise à jour
        $updateData = [
            'content' => 'Updated content'
        ];
        
        // Mock de la requête
        $request = Mockery::mock(CommentRequest::class);
        $request->shouldReceive('validated')
            ->once()
            ->andReturn($updateData);
        
        // Mock Auth
        Auth::shouldReceive('id')
            ->andReturn($this->user->id);
        Auth::shouldReceive('user->isAdmin')
            ->andReturn(false);
        
        // Exécuter la méthode
        $response = $this->controller->update($request, $comment);
        
        // Vérifier les résultats
        $this->assertInstanceOf(CommentResource::class, $response);
        
        // Vérifier que le commentaire est bien mis à jour
        $this->assertEquals('Updated content', $comment->fresh()->content);
    }

    public function test_destroy_deletes_comment_for_admin()
    {
        // Créer un commentaire
        $comment = Comment::create([
            'content' => 'Comment to delete',
            'status' => 'published',
            'resource_id' => $this->resource->id,
            'user_id' => $this->user->id
        ]);
        
        // Mock Auth
        Auth::shouldReceive('id')
            ->andReturn($this->user->id);
        Auth::shouldReceive('user->isAdmin')
            ->andReturn(true);
        
        // Exécuter la méthode
        $response = $this->controller->destroy($comment);
        
        // Vérifier les résultats
        $this->assertEquals(Response::HTTP_NO_CONTENT, $response->status());
        
        // Vérifier que le commentaire est bien supprimé
        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }
}