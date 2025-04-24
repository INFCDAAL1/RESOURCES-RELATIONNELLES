<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Resource;
use App\Models\Type;
use App\Models\Category;
use App\Models\Visibility;
use App\Models\Origin;
use App\Models\Invitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ResourceCreationTest extends TestCase
{
    use RefreshDatabase;
    
    protected $citizen;
    protected $moderator;
    protected $type;
    protected $category;
    protected $visibility;
    protected $origin;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Configurer le système de stockage fictif
        Storage::fake('local');
        
        // Créer les données de base nécessaires
        $this->citizen = User::factory()->create([
            'role' => 'user',
            'is_active' => true,
        ]);
        
        $this->moderator = User::factory()->create([
            'role' => 'moderator', 
            'is_active' => true,
        ]);
        
        $this->admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);
        
        // Créer les entités nécessaires pour les ressources
        $this->type = Type::create(['name' => 'Document']);
        $this->category = Category::create(['name' => 'Education']);
        $this->visibility = Visibility::create(['name' => 'Public']);
        $this->origin = Origin::create(['libelle' => 'Personnel']);
    }
    
    /**
     * Test de création d'une ressource par un citoyen connecté
     */
    public function test_citizen_can_create_resource()
    {
        $this->actingAs($this->citizen);
        
        $file = UploadedFile::fake()->create('document.pdf', 500);
        
        $response = $this->post('/api/resources', [
            'name' => 'Ma première ressource',
            'description' => 'Description de ma ressource',
            'published' => false, // Par défaut non publiée
            'type_id' => $this->type->id,
            'category_id' => $this->category->id,
            'visibility_id' => $this->visibility->id,
            'origin_id' => $this->origin->id,
            'file' => $file,
        ]);
        
        $response->assertStatus(201); // Created
        
        // Vérifier que la ressource a été créée en base de données
        $this->assertDatabaseHas('resources', [
            'name' => 'Ma première ressource',
            'description' => 'Description de ma ressource',
            'published' => false,
            'validated' => false, // Par défaut non validée
            'user_id' => $this->citizen->id,
        ]);
        
        // Vérifier que le fichier a été stocké
        $resource = Resource::where('name', 'Ma première ressource')->first();
        $this->assertNotNull($resource->file_path);
        Storage::disk('local')->assertExists($resource->file_path);
    }
    
    /**
     * Test d'édition d'une ressource par son propriétaire
     */
    public function test_citizen_can_edit_own_resource()
    {
        $this->actingAs($this->citizen);
        
        // Créer une ressource pour l'utilisateur
        $resource = Resource::create([
            'name' => 'Ressource à éditer',
            'description' => 'Description initiale',
            'published' => false,
            'validated' => false,
            'type_id' => $this->type->id,
            'category_id' => $this->category->id,
            'visibility_id' => $this->visibility->id,
            'origin_id' => $this->origin->id,
            'user_id' => $this->citizen->id,
            'link' => null,
            'file_path' => null,
        ]);
        
        // Éditer la ressource
        $response = $this->put("/api/resources/{$resource->id}", [
            'name' => 'Ressource modifiée',
            'description' => 'Nouvelle description',
            'published' => true, 
            'type_id' => $this->type->id,
            'category_id' => $this->category->id,
            'visibility_id' => $this->visibility->id,
            'origin_id' => $this->origin->id,
        ]);
        
        // La requête devrait réussir même si la ressource reste non validée
        $response->assertSuccessful();
        
        // Vérifier que les modifications ont été enregistrées
        $this->assertDatabaseHas('resources', [
            'id' => $resource->id,
            'name' => 'Ressource modifiée',
            'description' => 'Nouvelle description',
            'published' => true, 
            'validated' => false, 
        ]);
    }
    
    /**
     * Test qu'un citoyen ne peut pas éditer la ressource d'un autre utilisateur
     */
    public function test_citizen_cannot_edit_others_resource()
    {
        // Créer un autre utilisateur
        $otherUser = User::factory()->create();
        
        // Créer une ressource pour cet autre utilisateur
        $resource = Resource::create([
            'name' => 'Ressource d\'un autre utilisateur',
            'description' => 'Description',
            'published' => true,
            'validated' => true,
            'type_id' => $this->type->id,
            'category_id' => $this->category->id,
            'visibility_id' => $this->visibility->id,
            'origin_id' => $this->origin->id,
            'user_id' => $otherUser->id,
            'link' => null,
            'file_path' => null,
        ]);
        
        // Connecter notre citoyen de test
        $this->actingAs($this->citizen);
        
        // Tenter d'éditer la ressource d'un autre utilisateur
        $response = $this->put("/api/resources/{$resource->id}", [
            'name' => 'Tentative de modification',
            'description' => 'Nouvelle description',
            'published' => true,
            'type_id' => $this->type->id,
            'category_id' => $this->category->id,
            'visibility_id' => $this->visibility->id,
            'origin_id' => $this->origin->id,
        ]);
        
        // La requête devrait être refusée
        $response->assertStatus(403); 
        
        // Vérifier que la ressource n'a pas été modifiée
        $this->assertDatabaseHas('resources', [
            'id' => $resource->id,
            'name' => 'Ressource d\'un autre utilisateur',
        ]);
    }
    
    /**
     * Test de validation d'une ressource par un modérateur
     */
    public function test_moderator_can_validate_resource()
    {
        // Créer une ressource non validée
        $resource = Resource::create([
            'name' => 'Ressource à valider',
            'description' => 'Description de la ressource',
            'published' => true, 
            'validated' => false, 
            'type_id' => $this->type->id,
            'category_id' => $this->category->id,
            'visibility_id' => $this->visibility->id,
            'origin_id' => $this->origin->id,
            'user_id' => $this->citizen->id,
            'link' => null,
            'file_path' => null,
        ]);
        
        // Connecter le modérateur
        $this->actingAs($this->moderator);
        
        // Valider la ressource
        $response = $this->put("/api/resources/{$resource->id}", [
            'name' => $resource->name,
            'description' => $resource->description,
            'published' => $resource->published,
            'validated' => true, 
            'type_id' => $resource->type_id,
            'category_id' => $resource->category_id,
            'visibility_id' => $resource->visibility_id,
            'origin_id' => $resource->origin_id,
        ]);
        
        // La requête devrait réussir
        $response->assertSuccessful();
        
        // Vérifier que la ressource a été validée
        $this->assertDatabaseHas('resources', [
            'id' => $resource->id,
            'validated' => true,
        ]);
    }
    
    /**
     * Test qu'un citoyen ne peut pas valider une ressource
     */
    public function test_citizen_cannot_validate_resource()
    {
        // Créer une ressource non validée
        $resource = Resource::create([
            'name' => 'Ressource non validée',
            'description' => 'Description de la ressource',
            'published' => true,
            'validated' => false,
            'type_id' => $this->type->id,
            'category_id' => $this->category->id,
            'visibility_id' => $this->visibility->id,
            'origin_id' => $this->origin->id,
            'user_id' => $this->citizen->id,
            'link' => null,
            'file_path' => null,
        ]);
        
        // Connecter un citoyen
        $this->actingAs($this->citizen);
        
        // Tenter de valider la ressource
        $response = $this->put("/api/resources/{$resource->id}", [
            'name' => $resource->name,
            'description' => $resource->description,
            'published' => $resource->published,
            'validated' => true,
            'type_id' => $resource->type_id,
            'category_id' => $resource->category_id,
            'visibility_id' => $resource->visibility_id,
            'origin_id' => $resource->origin_id,
        ]);
        
        // Vérifier que la ressource n'a pas été validée
        $this->assertDatabaseHas('resources', [
            'id' => $resource->id,
            'validated' => false,
        ]);
    }
    
    /**
     * Test de partage d'une ressource par un citoyen (via invitation)
     */
    public function test_citizen_can_share_resource()
    {
        // Créer une ressource validée pour notre citoyen
        $resource = Resource::create([
            'name' => 'Ressource à partager',
            'description' => 'Description de la ressource',
            'published' => true,
            'validated' => true,
            'type_id' => $this->type->id,
            'category_id' => $this->category->id,
            'visibility_id' => $this->visibility->id,
            'origin_id' => $this->origin->id,
            'user_id' => $this->citizen->id,
            'link' => null,
            'file_path' => null,
        ]);
        
        // Créer un autre utilisateur qui recevra l'invitation
        $recipient = User::factory()->create();
        
        // Connecter notre citoyen
        $this->actingAs($this->citizen);
        
        // Envoyer une invitation pour partager la ressource
        $response = $this->post('/api/invitations', [
            'receiver_id' => $recipient->id,
            'resource_id' => $resource->id,
        ]);
        
        // La requête devrait réussir
        $response->assertSuccessful();
        
        // Vérifier que l'invitation a été créée
        $this->assertDatabaseHas('invitations', [
            'sender_id' => $this->citizen->id,
            'receiver_id' => $recipient->id,
            'resource_id' => $resource->id,
            'status' => 'pending',
        ]);
    }
    
    /**
     * Test d'acceptation d'une invitation par un autre citoyen
     */
    public function test_citizen_can_accept_invitation()
    {
        // Créer un autre utilisateur
        $recipient = User::factory()->create();
        
        // Créer une ressource validée
        $resource = Resource::create([
            'name' => 'Ressource partagée',
            'description' => 'Description de la ressource',
            'published' => true,
            'validated' => true,
            'type_id' => $this->type->id,
            'category_id' => $this->category->id,
            'visibility_id' => $this->visibility->id,
            'origin_id' => $this->origin->id,
            'user_id' => $this->citizen->id,
            'link' => null,
            'file_path' => null,
        ]);
        
        // Créer une invitation
        $invitation = Invitation::create([
            'sender_id' => $this->citizen->id,
            'receiver_id' => $recipient->id,
            'resource_id' => $resource->id,
            'status' => 'pending',
        ]);
        
        // Connecter le destinataire
        $this->actingAs($recipient);
        
        // Accepter l'invitation
        $response = $this->put("/api/invitations/{$invitation->id}", [
            'status' => 'accepted',
        ]);
        
        // La requête devrait réussir
        $response->assertSuccessful();
        
        // Vérifier que l'invitation a été acceptée
        $this->assertDatabaseHas('invitations', [
            'id' => $invitation->id,
            'status' => 'accepted',
        ]);
    }
    
    /**
     * Test que seul le destinataire peut accepter ou refuser une invitation
     */
    public function test_only_receiver_can_update_invitation()
    {
        // Créer un autre utilisateur
        $recipient = User::factory()->create();
        
        // Créer une ressource validée
        $resource = Resource::create([
            'name' => 'Ressource partagée',
            'description' => 'Description de la ressource',
            'published' => true,
            'validated' => true,
            'type_id' => $this->type->id,
            'category_id' => $this->category->id,
            'visibility_id' => $this->visibility->id,
            'origin_id' => $this->origin->id,
            'user_id' => $this->citizen->id,
            'link' => null,
            'file_path' => null,
        ]);
        
        // Créer une invitation
        $invitation = Invitation::create([
            'sender_id' => $this->citizen->id,
            'receiver_id' => $recipient->id,
            'resource_id' => $resource->id,
            'status' => 'pending',
        ]);
        
        // Connecter l'expéditeur (qui ne devrait pas pouvoir modifier le statut)
        $this->actingAs($this->citizen);
        
        // Tenter de modifier le statut de l'invitation
        $response = $this->put("/api/invitations/{$invitation->id}", [
            'status' => 'accepted',
        ]);
        
        // La requête devrait être refusée
        $response->assertStatus(403);
        
        // Vérifier que l'invitation n'a pas été modifiée
        $this->assertDatabaseHas('invitations', [
            'id' => $invitation->id,
            'status' => 'pending',
        ]);
    }
}