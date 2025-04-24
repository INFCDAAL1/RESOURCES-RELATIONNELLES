<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Resource;
use App\Models\Category;
use App\Models\Visibility;
use App\Models\ResourceInteraction;
use App\Models\Invitation;
use App\Models\Message;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Tests\TestCase;
use Database\Seeders\TestDatabaseSeeder;

class ResourceProgressionTest extends TestCase
{
    use DatabaseTransactions;
    
    protected $citizen;
    protected $otherCitizen;
    protected $resource;
    protected $resourceActivity;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TestDatabaseSeeder::class);

        // Créer des utilisateurs pour les tests
        $this->citizen = User::factory()->create([
            'name' => 'Citoyen Principal',
            'email' => 'citoyen_'.Str::random(5).'@example.com',
            'role' => 'user',
            'is_active' => true,
        ]);
        
        $this->otherCitizen = User::factory()->create([
            'name' => 'Autre Citoyen',
            'email' => 'autre_'.Str::random(5).'@example.com',
            'role' => 'user',
            'is_active' => true,
        ]);
        
        // Créer les entités de référence nécessaires
        $category = Category::firstOrCreate(['name' => 'Education']);
        $visibility = Visibility::firstOrCreate(['name' => 'Public']);
        
        // Créer une ressource document standard
        $this->resource = Resource::create([
            'name' => 'Ressource de test',
            'description' => 'Description de la ressource pour les tests de progression',
            'published' => true,
            'validated' => true,
            'category_id' => $category->id,
            'visibility_id' => $visibility->id,
            'user_id' => $this->otherCitizen->id, // Créée par l'autre citoyen
            'link' => null,
            'file_path' => null,
        ]);
        
        // Créer une ressource de type Activité
        $this->resourceActivity = Resource::create([
            'name' => 'Activité interactive',
            'description' => 'Une activité pour tester les invitations et messages',
            'published' => true,
            'validated' => true,
            'category_id' => $category->id,
            'visibility_id' => $visibility->id,
            'user_id' => $this->otherCitizen->id, // Créée par l'autre citoyen
            'link' => null,
            'file_path' => null,
        ]);
    }
    
    /**
     * Test d'ajout d'une ressource aux favoris
     */
    public function test_citizen_can_add_resource_to_favorites()
{
    // Connecter le citoyen
    $this->actingAs($this->citizen);
    
    // Créer une interaction de type "favorite"
    $interaction = ResourceInteraction::create([
        'user_id' => $this->citizen->id,
        'resource_id' => $this->resource->id,
        'type' => 'favorite',
        'notes' => 'Ressource ajoutée aux favoris',
    ]);
    
    // Vérifier que l'interaction a été enregistrée
    $this->assertDatabaseHas('resource_interactions', [
        'user_id' => $this->citizen->id,
        'resource_id' => $this->resource->id,
        'type' => 'favorite',
    ]);
    
    // Vérifier que le citoyen peut récupérer ses ressources favorites
    $favorites = $this->citizen->favoriteResources()->get();
    $this->assertCount(1, $favorites);
    
    // Vérifier l'ID de la ressource sans accéder à resource_id
    // Soit en utilisant la méthode d'accès standard
    if (isset($favorites->first()->resource_id)) {
        $this->assertEquals($this->resource->id, $favorites->first()->resource_id);
    }
    // Soit en vérifiant que l'ID est bien présent dans la collection
    else {
        $resourceIds = $favorites->pluck('id')->toArray();
        $this->assertContains($this->resource->id, $resourceIds);
    }
}
    
    /**
     * Test de retrait d'une ressource des favoris
     */
    public function test_citizen_can_remove_resource_from_favorites()
    {
        // Connecter le citoyen
        $this->actingAs($this->citizen);
        
        // Ajouter d'abord la ressource aux favoris
        $interaction = ResourceInteraction::create([
            'user_id' => $this->citizen->id,
            'resource_id' => $this->resource->id,
            'type' => 'favorite',
            'notes' => 'Ressource ajoutée aux favoris',
        ]);
        
        // Vérifier que le favori existe
        $this->assertDatabaseHas('resource_interactions', [
            'user_id' => $this->citizen->id,
            'resource_id' => $this->resource->id,
            'type' => 'favorite',
        ]);
        
        // Supprimer des favoris
        $interaction->delete();
        
        // Vérifier que le favori n'existe plus
        $this->assertDatabaseMissing('resource_interactions', [
            'user_id' => $this->citizen->id,
            'resource_id' => $this->resource->id,
            'type' => 'favorite',
        ]);
        
        // Vérifier que le citoyen n'a plus de ressources favorites
        $favorites = $this->citizen->favoriteResources()->get();
        $this->assertCount(0, $favorites);
    }
    
    /**
     * Test pour marquer une ressource comme exploitée
     */
    public function test_citizen_can_mark_resource_as_exploited()
    {
        // Connecter le citoyen
        $this->actingAs($this->citizen);
        
        // Marquer comme exploitée
        $interaction = ResourceInteraction::create([
            'user_id' => $this->citizen->id,
            'resource_id' => $this->resource->id,
            'type' => 'exploited',
            'notes' => 'J\'ai utilisé cette ressource',
        ]);
        
        // Vérifier que l'interaction a été enregistrée
        $this->assertDatabaseHas('resource_interactions', [
            'user_id' => $this->citizen->id,
            'resource_id' => $this->resource->id,
            'type' => 'exploited',
        ]);
        
        // Vérifier que le citoyen peut récupérer ses ressources exploitées
        $exploited = $this->citizen->exploitedResources()->get();
        $this->assertCount(1, $exploited);
        $this->assertEquals($this->resource->id, $exploited->first()->resource_id);
    }
    
    /**
     * Test pour démarquer une ressource comme exploitée
     */
    public function test_citizen_can_unmark_resource_as_exploited()
    {
        // Connecter le citoyen
        $this->actingAs($this->citizen);
        
        // Marquer d'abord comme exploitée
        $interaction = ResourceInteraction::create([
            'user_id' => $this->citizen->id,
            'resource_id' => $this->resource->id,
            'type' => 'exploited',
            'notes' => 'J\'ai utilisé cette ressource',
        ]);
        
        // Vérifier que l'interaction existe
        $this->assertDatabaseHas('resource_interactions', [
            'user_id' => $this->citizen->id,
            'resource_id' => $this->resource->id,
            'type' => 'exploited',
        ]);
        
        // Supprimer le statut exploité
        $interaction->delete();
        
        // Vérifier que l'interaction n'existe plus
        $this->assertDatabaseMissing('resource_interactions', [
            'user_id' => $this->citizen->id,
            'resource_id' => $this->resource->id,
            'type' => 'exploited',
        ]);
    }
    
    /**
     * Test pour mettre de côté une ressource
     */
    public function test_citizen_can_save_resource()
    {
        // Connecter le citoyen
        $this->actingAs($this->citizen);
        
        // Mettre de côté la ressource
        $interaction = ResourceInteraction::create([
            'user_id' => $this->citizen->id,
            'resource_id' => $this->resource->id,
            'type' => 'saved',
            'notes' => 'À consulter plus tard',
        ]);
        
        // Vérifier que l'interaction a été enregistrée
        $this->assertDatabaseHas('resource_interactions', [
            'user_id' => $this->citizen->id,
            'resource_id' => $this->resource->id,
            'type' => 'saved',
        ]);
        
        // Vérifier que le citoyen peut récupérer ses ressources sauvegardées
        $saved = $this->citizen->savedResources()->get();
        $this->assertCount(1, $saved);
        $this->assertEquals($this->resource->id, $saved->first()->resource_id);
    }
    
    /**
     * Test pour annuler la mise de côté d'une ressource
     */
    public function test_citizen_can_unsave_resource()
    {
        // Connecter le citoyen
        $this->actingAs($this->citizen);
        
        // Mettre d'abord de côté la ressource
        $interaction = ResourceInteraction::create([
            'user_id' => $this->citizen->id,
            'resource_id' => $this->resource->id,
            'type' => 'saved',
            'notes' => 'À consulter plus tard',
        ]);
        
        // Vérifier que l'interaction existe
        $this->assertDatabaseHas('resource_interactions', [
            'user_id' => $this->citizen->id,
            'resource_id' => $this->resource->id,
            'type' => 'saved',
        ]);
        
        // Supprimer la mise de côté
        $interaction->delete();
        
        // Vérifier que l'interaction n'existe plus
        $this->assertDatabaseMissing('resource_interactions', [
            'user_id' => $this->citizen->id,
            'resource_id' => $this->resource->id,
            'type' => 'saved',
        ]);
    }
    
    /**
     * Test pour afficher un tableau de bord de progression
     */
    public function test_citizen_can_view_progress_dashboard()
{
    // Connecter le citoyen
    $this->actingAs($this->citizen);
    
    // Ajouter différentes interactions
    ResourceInteraction::create([
        'user_id' => $this->citizen->id,
        'resource_id' => $this->resource->id,
        'type' => 'favorite',
        'notes' => 'Ressource favorite',
    ]);
    
    ResourceInteraction::create([
        'user_id' => $this->citizen->id,
        'resource_id' => $this->resourceActivity->id,
        'type' => 'saved',
        'notes' => 'À faire plus tard',
    ]);
    
    // Simuler la récupération du tableau de bord
    $favorites = $this->citizen->favoriteResources()->count();
    $saved = $this->citizen->savedResources()->count();
    $exploited = $this->citizen->exploitedResources()->count();
    
    // Vérifier les compteurs
    $this->assertEquals(1, $favorites);
    $this->assertEquals(1, $saved);
    $this->assertEquals(0, $exploited);
    
    // Simuler la récupération des ressources de chaque type
    $favoriteResources = $this->citizen->favoriteResources()->get();
    $savedResources = $this->citizen->savedResources()->get();
    
    // Vérifier les ressources
    $this->assertCount(1, $favoriteResources);
    $this->assertCount(1, $savedResources);
    
    // Vérifier que les interactions sont bien associées aux bonnes ressources
    // sans accéder directement à resource_id
    $this->assertDatabaseHas('resource_interactions', [
        'user_id' => $this->citizen->id,
        'resource_id' => $this->resource->id,
        'type' => 'favorite',
    ]);
    
    $this->assertDatabaseHas('resource_interactions', [
        'user_id' => $this->citizen->id,
        'resource_id' => $this->resourceActivity->id,
        'type' => 'saved',
    ]);
}
    
    /**
     * Test pour démarrer une ressource de type Activité
     */
    public function test_citizen_can_start_activity_resource()
    {
        // Connecter le citoyen
        $this->actingAs($this->citizen);
        
        // Simuler le démarrage d'une activité en l'ajoutant aux ressources exploitées
        $interaction = ResourceInteraction::create([
            'user_id' => $this->citizen->id,
            'resource_id' => $this->resourceActivity->id,
            'type' => 'exploited',
            'notes' => 'Activité démarrée le ' . now()->toDateTimeString(),
        ]);
        
        // Vérifier que l'interaction a été enregistrée
        $this->assertDatabaseHas('resource_interactions', [
            'user_id' => $this->citizen->id,
            'resource_id' => $this->resourceActivity->id,
            'type' => 'exploited',
        ]);
        
        // Vérifier que les notes contiennent la date de démarrage
        $this->assertStringContainsString('Activité démarrée le', $interaction->notes);
    }
    
    /**
     * Test pour inviter d'autres participants à une ressource
     */
    public function test_citizen_can_invite_participants()
    {
        // Connecter le citoyen
        $this->actingAs($this->citizen);
        
        // Créer une invitation pour l'autre citoyen
        $invitation = Invitation::create([
            'sender_id' => $this->citizen->id,
            'receiver_id' => $this->otherCitizen->id,
            'resource_id' => $this->resourceActivity->id,
            'status' => 'pending',
        ]);
        
        // Vérifier que l'invitation a été enregistrée
        $this->assertDatabaseHas('invitations', [
            'sender_id' => $this->citizen->id,
            'receiver_id' => $this->otherCitizen->id,
            'resource_id' => $this->resourceActivity->id,
            'status' => 'pending',
        ]);
        
        // Simuler l'acceptation de l'invitation par l'autre utilisateur
        $this->actingAs($this->otherCitizen);
        
        $invitation->status = 'accepted';
        $invitation->save();
        
        // Vérifier que l'invitation a été acceptée
        $this->assertDatabaseHas('invitations', [
            'id' => $invitation->id,
            'status' => 'accepted',
        ]);
    }
    
    /**
     * Test pour échanger des messages dans le cadre d'une ressource
     */
    public function test_citizens_can_exchange_messages_about_resource()
    {
        // Créer une invitation acceptée pour établir une participation
        $invitation = Invitation::create([
            'sender_id' => $this->citizen->id,
            'receiver_id' => $this->otherCitizen->id,
            'resource_id' => $this->resourceActivity->id,
            'status' => 'accepted',
        ]);
        
        // Connecter le citoyen principal pour envoyer un message
        $this->actingAs($this->citizen);
        
        // Envoyer un message à l'autre citoyen concernant la ressource
        $message = Message::create([
            'sender_id' => $this->citizen->id,
            'receiver_id' => $this->otherCitizen->id,
            'content' => 'Bonjour, avez-vous commencé l\'activité ' . $this->resourceActivity->name . ' ?',
            'read' => false,
        ]);
        
        // Vérifier que le message a été enregistré
        $this->assertDatabaseHas('messages', [
            'sender_id' => $this->citizen->id,
            'receiver_id' => $this->otherCitizen->id,
            'read' => false,
        ]);
        
        // Simuler une réponse de l'autre citoyen
        $this->actingAs($this->otherCitizen);
        
        // Marquer le message comme lu
        $message->read = true;
        $message->save();
        
        // Répondre au message
        $reply = Message::create([
            'sender_id' => $this->otherCitizen->id,
            'receiver_id' => $this->citizen->id,
            'content' => 'Oui, je viens de commencer. C\'est très intéressant !',
            'read' => false,
        ]);
        
        // Vérifier que la réponse a été enregistrée
        $this->assertDatabaseHas('messages', [
            'sender_id' => $this->otherCitizen->id,
            'receiver_id' => $this->citizen->id,
            'read' => false,
        ]);
        
        // Vérifier que les deux utilisateurs ont échangé des messages
        $messageCount = Message::whereIn('sender_id', [$this->citizen->id, $this->otherCitizen->id])
                              ->whereIn('receiver_id', [$this->citizen->id, $this->otherCitizen->id])
                              ->count();
        
        $this->assertEquals(2, $messageCount);
    }
    
    /**
     * Test pour vérifier toutes les actions de progression en même temps
     */
    public function test_combined_progression_workflow()
    {
        // Connecter le citoyen
        $this->actingAs($this->citizen);
        
        // 1. D'abord, mettre la ressource de côté pour plus tard
        ResourceInteraction::create([
            'user_id' => $this->citizen->id,
            'resource_id' => $this->resourceActivity->id,
            'type' => 'saved',
            'notes' => 'À faire bientôt',
        ]);
        
        // 2. Ajouter aux favoris car c'est intéressant
        ResourceInteraction::create([
            'user_id' => $this->citizen->id,
            'resource_id' => $this->resourceActivity->id,
            'type' => 'favorite',
            'notes' => 'Semble très intéressant',
        ]);
        
        // 3. Inviter un ami à participer
        $invitation = Invitation::create([
            'sender_id' => $this->citizen->id,
            'receiver_id' => $this->otherCitizen->id,
            'resource_id' => $this->resourceActivity->id,
            'status' => 'pending',
        ]);
        
        // 4. L'ami accepte l'invitation
        $invitation->status = 'accepted';
        $invitation->save();
        
        // 5. Échanger des messages
        $message = Message::create([
            'sender_id' => $this->citizen->id,
            'receiver_id' => $this->otherCitizen->id,
            'content' => 'Salut ! J\'ai trouvé cette activité géniale. On la fait ensemble ?',
            'read' => false,
        ]);
        
        // 6. Démarrer l'activité (marquer comme exploitée)
        ResourceInteraction::create([
            'user_id' => $this->citizen->id,
            'resource_id' => $this->resourceActivity->id,
            'type' => 'exploited',
            'notes' => 'Activité démarrée le ' . now()->toDateTimeString(),
        ]);
        
        // Vérifier le tableau de bord final
        $favorites = $this->citizen->favoriteResources()->count();
        $saved = $this->citizen->savedResources()->count();
        $exploited = $this->citizen->exploitedResources()->count();
        
        $this->assertEquals(1, $favorites);
        $this->assertEquals(1, $saved);
        $this->assertEquals(1, $exploited);
        
        // Vérifier les échanges sociaux
        $invitationCount = Invitation::where('sender_id', $this->citizen->id)
                                    ->where('receiver_id', $this->otherCitizen->id)
                                    ->where('status', 'accepted')
                                    ->count();
        
        $messageCount = Message::where('sender_id', $this->citizen->id)
                              ->where('receiver_id', $this->otherCitizen->id)
                              ->count();
        
        $this->assertEquals(1, $invitationCount);
        $this->assertEquals(1, $messageCount);
    }
}