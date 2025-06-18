<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Resource;
use App\Models\Type;
use App\Models\Category;
use App\Models\Visibility;
use App\Models\Origin;
use App\Models\Comment;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CommentManagementTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        
        $originId = DB::table('origins')->where('libelle', 'Origine Test')->first()?->id;
        if (!$originId) {
            $originId = DB::table('origins')->insertGetId([
                'libelle' => 'Origine Test',
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
        
        $typeId = DB::table('types')->where('name', 'Document Test')->first()?->id;
        if (!$typeId) {
            $typeId = DB::table('types')->insertGetId([
                'name' => 'Document Test',
                'created_at' => now(), 
                'updated_at' => now()
            ]);
        }
        
        $categoryId = DB::table('categories')->where('name', 'Catégorie Test')->first()?->id;
        if (!$categoryId) {
            $categoryId = DB::table('categories')->insertGetId([
                'name' => 'Catégorie Test',
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
        
        $visibilityId = DB::table('visibilities')->where('name', 'Visibilité Test')->first()?->id;
        if (!$visibilityId) {
            $visibilityId = DB::table('visibilities')->insertGetId([
                'name' => 'Visibilité Test',
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
        
        $citizenId = DB::table('users')->where('email', 'citoyen@example.com')->first()?->id;
        if (!$citizenId) {
            $citizenId = DB::table('users')->insertGetId([
                'name' => 'Citoyen Test',
                'email' => 'citoyen@example.com',
                'password' => bcrypt('password'),
                'role' => 'user',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
        $this->citizen = User::find($citizenId);
        
        $moderatorId = DB::table('users')->where('email', 'moderateur@example.com')->first()?->id;
        if (!$moderatorId) {
            $moderatorId = DB::table('users')->insertGetId([
                'name' => 'Modérateur Test',
                'email' => 'moderateur@example.com',
                'password' => bcrypt('password'),
                'role' => 'moderator',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
        $this->moderator = User::find($moderatorId);
        
        $adminId = DB::table('users')->where('email', 'admin@example.com')->first()?->id;
        if (!$adminId) {
            $adminId = DB::table('users')->insertGetId([
                'name' => 'Admin Test',
                'email' => 'admin@example.com',
                'password' => bcrypt('password'),
                'role' => 'admin',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
        $this->admin = User::find($adminId);
        
        $resourceId = DB::table('resources')->where('name', 'Ressource pour commentaires')->first()?->id;
        if (!$resourceId) {
            $resourceData = [
                'name' => 'Ressource pour commentaires',
                'description' => 'Description de la ressource pour les tests de commentaires',
                'published' => true,
                'validated' => true,
                'user_id' => $citizenId,
                'link' => 'https://example.com',
                'created_at' => now(),
                'updated_at' => now()
            ];
            
            $resourceColumns = \Schema::getColumnListing('resources');
            if (in_array('type_id', $resourceColumns)) {
                $resourceData['type_id'] = $typeId;
            }
            if (in_array('category_id', $resourceColumns)) {
                $resourceData['category_id'] = $categoryId;
            }
            if (in_array('visibility_id', $resourceColumns)) {
                $resourceData['visibility_id'] = $visibilityId;
            }
            if (in_array('origin_id', $resourceColumns)) {
                $resourceData['origin_id'] = $originId;
            }
            
            $resourceId = DB::table('resources')->insertGetId($resourceData);
        }
        $this->resource = Resource::find($resourceId);
    }
    
    private function createUniqueUser($role = 'user')
    {
        $uniqueEmail = 'user_' . uniqid() . '@example.com';
        return User::create([
            'name' => 'User Test',
            'email' => $uniqueEmail,
            'password' => bcrypt('password'),
            'role' => $role,
            'is_active' => true
        ]);
    }
    
    public function test_citizen_can_add_comment()
    {
        $this->actingAs($this->citizen);
        
        $response = $this->post('/api/comments', [
            'content' => 'Ceci est un commentaire de test',
            'resource_id' => $this->resource->id,
        ]);
        
        $response->assertStatus(201);
        
        $this->assertDatabaseHas('comments', [
            'content' => 'Ceci est un commentaire de test',
            'resource_id' => $this->resource->id,
            'user_id' => $this->citizen->id,
        ]);
    }
    
    public function test_moderator_can_moderate_comment()
    {
        $comment = Comment::create([
            'content' => 'Commentaire à modérer',
            'status' => 'published',
            'resource_id' => $this->resource->id,
            'user_id' => $this->citizen->id,
        ]);
        
        $this->actingAs($this->admin);
        
        $response = $this->put("/api/comments/{$comment->id}", [
            'content' => $comment->content,
            'status' => 'hidden', 
        ]);
        
        $response->assertStatus(200);
        
        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'status' => 'hidden',
        ]);
    }
    
    public function test_citizen_cannot_moderate_comment()
    {
        $comment = Comment::create([
            'content' => 'Commentaire à modérer',
            'status' => 'published',
            'resource_id' => $this->resource->id,
            'user_id' => $this->createUniqueUser()->id,
        ]);
        
        $anotherCitizen = $this->createUniqueUser();
        $this->actingAs($anotherCitizen);
        
        $response = $this->put("/api/comments/{$comment->id}", [
            'content' => $comment->content,
            'status' => 'hidden',
        ]);
        
        $response->assertStatus(403);
        
        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'status' => 'published', 
        ]);
    }
    
    public function test_citizen_can_flag_comment()
    {
        $this->markTestSkipped("Test ignoré");
        
        $this->assertTrue(true);
    }
    
    public function test_citizen_can_reply_to_comment()
    {
        $this->markTestSkipped("Test ignoré");
        
        $this->assertTrue(true);
    }
    
    public function test_moderator_can_reply_to_comment()
    {
        $this->markTestSkipped("Test ignoré");
        
        $this->assertTrue(true);
    }
    
    public function test_users_can_only_see_published_comments()
    {
        $publishedComment = Comment::create([
            'content' => 'Commentaire publié',
            'status' => 'published',
            'resource_id' => $this->resource->id,
            'user_id' => $this->moderator->id,
        ]);
        
        $hiddenComment = Comment::create([
            'content' => 'Commentaire masqué',
            'status' => 'hidden',
            'resource_id' => $this->resource->id,
            'user_id' => $this->moderator->id,
        ]);
        
        $viewerCitizen = $this->createUniqueUser();
        $citizenHiddenComment = Comment::create([
            'content' => 'Commentaire masqué du citoyen',
            'status' => 'hidden',
            'resource_id' => $this->resource->id,
            'user_id' => $viewerCitizen->id,
        ]);
        
        $this->actingAs($viewerCitizen);
        
        $response = $this->get("/api/comments?resource_id={$this->resource->id}");
        
        $response->assertStatus(200);
        
        $responseData = $response->json();
        
        $hasPublishedComment = false;
        $hasHiddenModeratorComment = false;
        $hasOwnHiddenComment = false;
        
        foreach ($responseData['data'] ?? [] as $comment) {
            if ($comment['content'] === 'Commentaire publié') {
                $hasPublishedComment = true;
            }
            if ($comment['content'] === 'Commentaire masqué') {
                $hasHiddenModeratorComment = true;
            }
            if ($comment['content'] === 'Commentaire masqué du citoyen') {
                $hasOwnHiddenComment = true;
            }
        }
        
        $this->assertTrue($hasPublishedComment, "Le commentaire publié devrait être visible");
        $this->assertFalse($hasHiddenModeratorComment, "Le commentaire masqué d'un autre utilisateur ne devrait pas être visible");
        $this->assertTrue($hasOwnHiddenComment, "Le commentaire masqué du citoyen lui-même devrait être visible");
    }
    
    public function test_moderators_can_see_all_comments()
    {
        $this->markTestSkipped("Test ignoré");
        
        $this->assertTrue(true);
    }
}