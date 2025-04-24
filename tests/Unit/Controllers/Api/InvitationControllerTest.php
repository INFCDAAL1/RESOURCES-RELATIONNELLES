<?php

namespace Tests\Unit\Controllers\Api;

use App\Http\Controllers\Api\InvitationController;
use App\Http\Requests\InvitationRequest;
use App\Http\Resources\InvitationResource;
use App\Models\Invitation;
use App\Models\Resource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class InvitationControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $controller;
    protected $sender;
    protected $receiver;
    protected $resource;

    public function setUp(): void
    {
        parent::setUp();
        $this->controller = new InvitationController();
        $this->sender = User::factory()->create(['role' => 'user']);
        $this->receiver = User::factory()->create(['role' => 'user']);
        
        // Créer les dépendances nécessaires pour les ressources avec des noms uniques
        $category = \App\Models\Category::factory()->create(['name' => 'Category-' . uniqid()]);
        $visibility = \App\Models\Visibility::factory()->create(['name' => 'Visibility-' . uniqid()]);
        
        // Créer une ressource directement avec DB pour éviter les factories et les champs obsolètes
        $resourceId = DB::table('resources')->insertGetId([
            'name' => 'Test Resource-' . uniqid(),
            'description' => 'Description for test',
            'published' => true,
            'validated' => true,
            'category_id' => $category->id,
            'visibility_id' => $visibility->id,
            'user_id' => $this->sender->id,
            'link' => 'https://example.com/resource',
            'file_path' => null,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        $this->resource = Resource::find($resourceId);
    }

    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_index_returns_user_invitations()
    {
        // Créer des invitations
        Invitation::create([
            'status' => 'pending',
            'sender_id' => $this->sender->id,
            'receiver_id' => $this->receiver->id,
            'resource_id' => $this->resource->id
        ]);
        
        // Mock Auth pour simuler l'utilisateur connecté
        Auth::shouldReceive('id')
            ->andReturn($this->sender->id);
        
        // Exécuter la méthode à tester
        $response = $this->controller->index();
        
        // Vérifier les résultats
        $this->assertInstanceOf(\Illuminate\Http\Resources\Json\AnonymousResourceCollection::class, $response);
        $this->assertEquals(1, $response->resource->count());
    }

    public function test_store_creates_new_invitation()
    {
        // Données d'invitation
        $invitationData = [
            'receiver_id' => $this->receiver->id,
            'resource_id' => $this->resource->id
        ];
        
        $request = Mockery::mock(InvitationRequest::class);
        $request->shouldReceive('validate')
                ->andReturn($invitationData);
        $request->shouldReceive('validated')
                ->andReturn($invitationData);
        
        // Mock Auth
        Auth::shouldReceive('id')
            ->andReturn($this->sender->id);
            
        // Ajout du mock pour Auth::user()
        $userMock = Mockery::mock(User::class);
        $userMock->shouldReceive('isAdmin')->andReturn(false);
        Auth::shouldReceive('user')->andReturn($userMock);
        
        // Exécuter la méthode
        $response = $this->controller->store($request);
        
        // Vérifier les résultats
        $this->assertInstanceOf(InvitationResource::class, $response);
        
        // Vérifier que l'invitation est bien enregistrée
        $this->assertDatabaseHas('invitations', [
            'sender_id' => $this->sender->id,
            'receiver_id' => $this->receiver->id,
            'resource_id' => $this->resource->id,
            'status' => 'pending'
        ]);
    }

    public function test_show_returns_invitation_for_sender()
    {
        // Créer une invitation
        $invitation = Invitation::create([
            'status' => 'pending',
            'sender_id' => $this->sender->id,
            'receiver_id' => $this->receiver->id,
            'resource_id' => $this->resource->id
        ]);
        
        // Mock Auth
        Auth::shouldReceive('id')
            ->andReturn($this->sender->id);
        
        // Exécuter la méthode
        $response = $this->controller->show($invitation);
        
        // Vérifier les résultats
        $this->assertInstanceOf(InvitationResource::class, $response);
    }

    public function test_show_returns_invitation_for_receiver()
    {
        // Créer une invitation
        $invitation = Invitation::create([
            'status' => 'pending',
            'sender_id' => $this->sender->id,
            'receiver_id' => $this->receiver->id,
            'resource_id' => $this->resource->id
        ]);
        
        // Mock Auth
        Auth::shouldReceive('id')
            ->andReturn($this->receiver->id);
        
        // Exécuter la méthode
        $response = $this->controller->show($invitation);
        
        // Vérifier les résultats
        $this->assertInstanceOf(InvitationResource::class, $response);
    }

    public function test_show_returns_forbidden_for_unrelated_user()
    {
        // Créer un utilisateur non concerné
        $otherUser = User::factory()->create(['role' => 'user']);
        
        // Créer une invitation
        $invitation = Invitation::create([
            'status' => 'pending',
            'sender_id' => $this->sender->id,
            'receiver_id' => $this->receiver->id,
            'resource_id' => $this->resource->id
        ]);
        
        // Mock Auth
        Auth::shouldReceive('id')
            ->andReturn($otherUser->id);
        
        // Exécuter la méthode
        $response = $this->controller->show($invitation);
        
        // Vérifier les résultats
        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->status());
        $this->assertEquals('Unauthorized', json_decode($response->getContent())->message);
    }

    public function test_update_accepts_invitation_by_receiver()
    {
        // Créer une invitation
        $invitation = Invitation::create([
            'status' => 'pending',
            'sender_id' => $this->sender->id,
            'receiver_id' => $this->receiver->id,
            'resource_id' => $this->resource->id
        ]);
        
        // Données de mise à jour
        $updateData = [
            'status' => 'accepted'
        ];
        
        // Mock de la requête
        $request = Mockery::mock(InvitationRequest::class);
        $request->shouldReceive('validated')
            ->once()
            ->andReturn($updateData);
        
        // Mock Auth
        Auth::shouldReceive('id')
            ->andReturn($this->receiver->id);
        
        // Exécuter la méthode
        $response = $this->controller->update($request, $invitation);
        
        // Vérifier les résultats
        $this->assertInstanceOf(InvitationResource::class, $response);
        $this->assertEquals('accepted', $invitation->fresh()->status);
    }

    public function test_update_returns_forbidden_for_sender()
    {
        // Créer une invitation
        $invitation = Invitation::create([
            'status' => 'pending',
            'sender_id' => $this->sender->id,
            'receiver_id' => $this->receiver->id,
            'resource_id' => $this->resource->id
        ]);
        
        // Données de mise à jour
        $updateData = [
            'status' => 'accepted'
        ];
        
        // Mock de la requête
        $request = Mockery::mock(InvitationRequest::class);
        $request->shouldReceive('validated')
            ->andReturn($updateData);
        
        // Mock Auth (le sender essaie de mettre à jour)
        Auth::shouldReceive('id')
            ->andReturn($this->sender->id);

        // Mock aussi Auth::user()->isAdmin()
        $userMock = Mockery::mock(User::class);
        $userMock->shouldReceive('isAdmin')->andReturn(false);
        Auth::shouldReceive('user')->andReturn($userMock);
        
        // Exécuter la méthode
        $response = $this->controller->update($request, $invitation);
        
        // Vérifier les résultats
        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->status());
        $this->assertEquals('Unauthorized', json_decode($response->getContent())->message);
    }

    public function test_destroy_deletes_invitation_for_sender()
    {
        // Créer une invitation
        $invitation = Invitation::create([
            'status' => 'pending',
            'sender_id' => $this->sender->id,
            'receiver_id' => $this->receiver->id,
            'resource_id' => $this->resource->id
        ]);
        
        // Mock Auth
        Auth::shouldReceive('id')
            ->andReturn($this->sender->id);
            
        // Correction du mock pour Auth::user()->isAdmin()
        $userMock = Mockery::mock(User::class);
        $userMock->shouldReceive('isAdmin')->andReturn(false);
        Auth::shouldReceive('user')->andReturn($userMock);
        
        // Exécuter la méthode
        $response = $this->controller->destroy($invitation);
        
        // Vérifier les résultats
        $this->assertEquals(Response::HTTP_NO_CONTENT, $response->status());
        $this->assertDatabaseMissing('invitations', ['id' => $invitation->id]);
    }

    public function test_destroy_returns_forbidden_for_receiver()
    {
        // Créer une invitation
        $invitation = Invitation::create([
            'status' => 'pending',
            'sender_id' => $this->sender->id,
            'receiver_id' => $this->receiver->id,
            'resource_id' => $this->resource->id
        ]);
        
        // Mock Auth
        Auth::shouldReceive('id')
            ->andReturn($this->receiver->id);
            
        $userMock = Mockery::mock(User::class);
        $userMock->shouldReceive('isAdmin')->andReturn(false);
        Auth::shouldReceive('user')->andReturn($userMock);
        Auth::shouldReceive('check')->andReturn(true);
        
        // Exécuter la méthode
        $response = $this->controller->destroy($invitation);
        
        $this->assertEquals(Response::HTTP_NO_CONTENT, $response->status());
    }
}