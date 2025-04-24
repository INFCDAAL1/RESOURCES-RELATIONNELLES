<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Resource;
use App\Models\Type;
use App\Models\Category;
use App\Models\Visibility;
use App\Models\Origin;
use App\Models\Comment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentManagementTest extends TestCase
{
    use RefreshDatabase;
    
    protected $citizen;
    protected $moderator;
    protected $admin;
    protected $resource;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Créer les utilisateurs nécessaires
        $this->citizen = User::factory()->create([
            'name' => 'Citoyen Test',
            'email' => 'citoyen@example.com',
            'role' => 'user',
            'is_active' => true,
        ]);
        
        $this->moderator = User::factory()->create([
            'name' => 'Modérateur Test',
            'email' => 'moderateur@example.com',
            'role' => 'moderator',
            'is_active' => true,
        ]);
        
        $this->admin = User::factory()->create([
            'name' => 'Admin Test',
            'email' => 'admin@example.com',
            'role' => 'admin',
            'is_active' => true,
        ]);
        
        // Créer les entités de référence
        $type = Type::firstOrCreate(['name' => 'Document Test']);
        $category = Category::firstOrCreate(['name' => 'Catégorie Test']);
        $visibility = Visibility::firstOrCreate(['name' => 'Visibilité Test']);
        $origin = Origin::firstOrCreate(['libelle' => 'Origine Test']);
        
        // Créer une ressource validée et publiée pour les tests
        $this->resource = Resource::create([
            'name' => 'Ressource pour commentaires',
            'description' => 'Description de la ressource pour les tests de commentaires',
            'published' => true,
            'validated' => true,
            'type_id' => $type->id,
            'category_id' => $category->id,
            'visibility_id' => $visibility->id,
            'origin_id' => $origin->id,
            'user_id' => $this->citizen->id,
            'link' => null,
            'file_path' => null,
        ]);
    }
    
    /**
     * Test d'ajout d'un commentaire par un citoyen connecté
     */
    public function test_citizen_can_add_comment()
    {
        // Connecter le citoyen
        $this->actingAs($this->citizen);
        
        // Ajouter un commentaire via l'API
        $response = $this->post('/api/comments', [
            'content' => 'Ceci est un commentaire de test',
            'resource_id' => $this->resource->id,
        ]);
        
        // Vérifier que la requête a réussi
        $response->assertStatus(201);
        
        // Vérifier que le commentaire a été enregistré en base de données
        $this->assertDatabaseHas('comments', [
            'content' => 'Ceci est un commentaire de test',
            'resource_id' => $this->resource->id,
            'user_id' => $this->citizen->id,
            'status' => 'published', // Par défaut, les commentaires sont publiés
        ]);
    }
    
    /**
     * Test de modération d'un commentaire par un modérateur
     */
    public function test_moderator_can_moderate_comment()
    {
        // Créer un commentaire à modérer
        $comment = Comment::create([
            'content' => 'Commentaire à modérer',
            'status' => 'published',
            'resource_id' => $this->resource->id,
            'user_id' => $this->citizen->id,
        ]);
        
        // Connecter le modérateur
        $this->actingAs($this->moderator);
        
        // Modérer le commentaire via l'API
        $response = $this->put("/api/comments/{$comment->id}", [
            'content' => $comment->content,
            'status' => 'hidden', 
        ]);
        
        $response->assertStatus(200); 
        
        // Vérifier que le commentaire a été modéré
        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'status' => 'hidden',
        ]);
    }
    
    /**
     * Test qu'un citoyen ne peut pas modérer un commentaire
     */
    public function test_citizen_cannot_moderate_comment()
    {
        // Créer un commentaire à modérer
        $comment = Comment::create([
            'content' => 'Commentaire à modérer',
            'status' => 'published',
            'resource_id' => $this->resource->id,
            'user_id' => $this->citizen->id,
        ]);
        
        // Créer un autre citoyen
        $anotherCitizen = User::factory()->create([
            'role' => 'user',
            'is_active' => true,
        ]);
        
        // Connecter l'autre citoyen
        $this->actingAs($anotherCitizen);
        
        // Tenter de modérer le commentaire via l'API
        $response = $this->put("/api/comments/{$comment->id}", [
            'content' => $comment->content,
            'status' => 'hidden', // Tenter de masquer le commentaire
        ]);
        
        
        // Vérifier que le commentaire n'a pas été modéré
        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'status' => 'published', 
        ]);
    }
    
    /**
     * Test de signalement d'un commentaire par un citoyen
     */
    public function test_citizen_can_flag_comment()
    {
        // Créer un commentaire
        $comment = Comment::create([
            'content' => 'Commentaire à signaler',
            'status' => 'published',
            'resource_id' => $this->resource->id,
            'user_id' => $this->moderator->id, 
        ]);
        
        // Connecter le citoyen
        $this->actingAs($this->citizen);
        
        // Dans cet exemple, nous supposons que le signalement est fait par un endpoint spécifique
        $response = $this->post("/api/comments/{$comment->id}/flag", [
            'reason' => 'Contenu inapproprié',
        ]);
        
        // Vérifier que la requête a réussi
        $response->assertStatus(200); 
        
        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'status' => 'flagged',
        ]);
    }
    
    /**
     * Test de réponse à un commentaire par un citoyen
     */
    public function test_citizen_can_reply_to_comment()
    {
        // Créer un commentaire parent
        $parentComment = Comment::create([
            'content' => 'Commentaire parent',
            'status' => 'published',
            'resource_id' => $this->resource->id,
            'user_id' => $this->moderator->id,
        ]);
        
        // Connecter le citoyen
        $this->actingAs($this->citizen);
        
        // Répondre au commentaire via l'API
        $response = $this->post('/api/comments', [
            'content' => 'Ceci est une réponse au commentaire',
            'resource_id' => $this->resource->id,
            'parent_id' => $parentComment->id,
        ]);
        
        // Vérifier que la requête a réussi
        $response->assertStatus(201); // Created
        
        // Vérifier que la réponse a été enregistrée en base de données
        $this->assertDatabaseHas('comments', [
            'content' => 'Ceci est une réponse au commentaire',
            'resource_id' => $this->resource->id,
            'user_id' => $this->citizen->id,
            'parent_id' => $parentComment->id,
        ]);
    }
    
    /**
     * Test de réponse à un commentaire par un modérateur
     */
    public function test_moderator_can_reply_to_comment()
    {
        // Créer un commentaire parent
        $parentComment = Comment::create([
            'content' => 'Commentaire parent',
            'status' => 'published',
            'resource_id' => $this->resource->id,
            'user_id' => $this->citizen->id,
        ]);
        
        // Connecter le modérateur
        $this->actingAs($this->moderator);
        
        // Répondre au commentaire via l'API
        $response = $this->post('/api/comments', [
            'content' => 'Réponse du modérateur',
            'resource_id' => $this->resource->id,
            'parent_id' => $parentComment->id,
        ]);
        
        // Vérifier que la requête a réussi
        $response->assertStatus(201);
        
        // Vérifier que la réponse a été enregistrée en base de données
        $this->assertDatabaseHas('comments', [
            'content' => 'Réponse du modérateur',
            'resource_id' => $this->resource->id,
            'user_id' => $this->moderator->id,
            'parent_id' => $parentComment->id,
        ]);
    }
    
    /**
     * Test que les utilisateurs ne peuvent voir que les commentaires publiés (sauf leurs propres commentaires)
     */
    public function test_users_can_only_see_published_comments()
    {
        // Créer différents commentaires avec différents statuts
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
        
        $citizenHiddenComment = Comment::create([
            'content' => 'Commentaire masqué du citoyen',
            'status' => 'hidden',
            'resource_id' => $this->resource->id,
            'user_id' => $this->citizen->id,
        ]);
        
        // Connecter le citoyen
        $this->actingAs($this->citizen);
        
        // Récupérer les commentaires de la ressource via l'API
        $response = $this->get("/api/comments?resource_id={$this->resource->id}");
        
        // Vérifier que la requête a réussi
        $response->assertStatus(200); 
        
        // Vérifier que le citoyen peut voir le commentaire publié
        $response->assertJsonFragment([
            'content' => 'Commentaire publié',
        ]);
        
        // Vérifier que le citoyen ne peut pas voir le commentaire masqué d'un autre utilisateur
        $response->assertJsonMissing([
            'content' => 'Commentaire masqué',
        ]);
        
        // Vérifier que le citoyen peut voir son propre commentaire même s'il est masqué
        $response->assertJsonFragment([
            'content' => 'Commentaire masqué du citoyen',
        ]);
    }
    
    /**
     * Test que les modérateurs peuvent voir tous les commentaires
     */
    public function test_moderators_can_see_all_comments()
    {
        // Créer différents commentaires avec différents statuts
        $publishedComment = Comment::create([
            'content' => 'Commentaire publié',
            'status' => 'published',
            'resource_id' => $this->resource->id,
            'user_id' => $this->citizen->id,
        ]);
        
        $hiddenComment = Comment::create([
            'content' => 'Commentaire masqué',
            'status' => 'hidden',
            'resource_id' => $this->resource->id,
            'user_id' => $this->citizen->id,
        ]);
        
        $flaggedComment = Comment::create([
            'content' => 'Commentaire signalé',
            'status' => 'flagged',
            'resource_id' => $this->resource->id,
            'user_id' => $this->citizen->id,
        ]);
        
        // Connecter le modérateur
        $this->actingAs($this->moderator);
        
        // Récupérer les commentaires de la ressource via l'API
        $response = $this->get("/api/comments?resource_id={$this->resource->id}");
        
        // Vérifier que la requête a réussi
        $response->assertStatus(200);
        
        // Vérifier que le modérateur peut voir tous les commentaires
        $response->assertJsonFragment([
            'content' => 'Commentaire publié',
        ]);
        
        $response->assertJsonFragment([
            'content' => 'Commentaire masqué',
        ]);
        
        $response->assertJsonFragment([
            'content' => 'Commentaire signalé',
        ]);
    }
}