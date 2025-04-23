<?php

namespace Tests\Unit\Controllers\Api;

use App\Http\Controllers\Api\MessageController;
use App\Http\Requests\MessageRequest;
use App\Http\Resources\MessageResource;
use App\Http\Resources\UserResource;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class MessageControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $controller;
    protected $user;
    protected $otherUser;

    public function setUp(): void
    {
        parent::setUp();
        $this->controller = new MessageController();
        $this->user = User::factory()->create(['role' => 'admin']);
        $this->otherUser = User::factory()->create(['role' => 'user']);
    }

    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_index_filters_messages_by_user_id()
    {
        // Créer des messages dans la base de données
        Message::create([
            'content' => 'Test message 1',
            'sender_id' => $this->user->id,
            'receiver_id' => $this->otherUser->id,
            'read' => false
        ]);
        
        Message::create([
            'content' => 'Test message 2',
            'sender_id' => $this->otherUser->id,
            'receiver_id' => $this->user->id,
            'read' => false
        ]);
        
        // Créer la requête mock
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('has')
            ->with('user_id')
            ->andReturn(true);
        $request->shouldReceive('input')
            ->with('user_id')
            ->andReturn($this->otherUser->id);
        
        // Mock Auth facade
        Auth::shouldReceive('id')
            ->andReturn($this->user->id);
        
        // Exécuter la méthode à tester
        $response = $this->controller->index($request);
        
        // Vérifier les résultats
        $this->assertInstanceOf(\Illuminate\Http\Resources\Json\AnonymousResourceCollection::class, $response);
        $this->assertEquals(2, $response->resource->count());
    }

    public function test_store_creates_new_message()
    {
        // Créer les données du message
        $messageData = [
            'content' => 'Test message content',
            'receiver_id' => $this->otherUser->id
        ];
        
        // Mock de la requête
        $request = Mockery::mock(MessageRequest::class);
        $request->shouldReceive('validated')
            ->once()
            ->andReturn($messageData);
        
        // Mock Auth
        Auth::shouldReceive('id')
            ->andReturn($this->user->id);
        
        // Exécuter la méthode
        $response = $this->controller->store($request);
        
        // Vérifier les résultats
        $this->assertInstanceOf(MessageResource::class, $response);
        
        // Vérifier que le message est bien enregistré en base de données
        $this->assertDatabaseHas('messages', [
            'content' => 'Test message content',
            'sender_id' => $this->user->id,
            'receiver_id' => $this->otherUser->id,
            'read' => 0
        ]);
    }

    public function test_show_marks_message_as_read_when_viewed_by_receiver()
    {
        // Créer un message
        $message = Message::create([
            'content' => 'Test message',
            'sender_id' => $this->otherUser->id,
            'receiver_id' => $this->user->id,
            'read' => false
        ]);
        
        // Mock Auth
        Auth::shouldReceive('id')
            ->andReturn($this->user->id);
        
        // Exécuter la méthode
        $response = $this->controller->show($message);
        
        // Vérifier les résultats
        $this->assertInstanceOf(MessageResource::class, $response);
        
        // Vérifier que le message est marqué comme lu
        $this->assertTrue($message->fresh()->read);
    }

    public function test_show_returns_forbidden_for_unauthorized_user()
    {
        // Créer un message entre deux autres utilisateurs
        $anotherUser = User::factory()->create();
        $message = Message::create([
            'content' => 'Test message',
            'sender_id' => $this->otherUser->id,
            'receiver_id' => $anotherUser->id,
            'read' => false
        ]);
        
        // Mock Auth (utilisateur actuel n'est ni l'expéditeur ni le destinataire)
        Auth::shouldReceive('id')
            ->andReturn($this->user->id);
        
        // Exécuter la méthode
        $response = $this->controller->show($message);
        
        // Vérifier les résultats
        $this->assertEquals(403, $response->status());
        $this->assertEquals('Unauthorized', json_decode($response->getContent())->message);
    }

    public function test_mark_all_as_read_updates_multiple_messages()
    {
    // Nettoyer les anciens messages d'abord
    Message::query()->delete();
    
    // Créer plusieurs messages non lus
    for ($i = 0; $i < 3; $i++) {
        Message::create([
            'content' => "Message $i",
            'sender_id' => $this->otherUser->id,
            'receiver_id' => $this->user->id,
            'read' => false
        ]);
    }
    
    // Mock de la requête
    $request = Mockery::mock(Request::class);
    $request->shouldReceive('validate')
        ->with(['sender_id' => 'required|exists:users,id'])
        ->andReturn(['sender_id' => $this->otherUser->id]);
    
    // Mock Auth
    Auth::shouldReceive('id')
        ->andReturn($this->user->id);
    
    // Exécuter la méthode
    $response = $this->controller->markAllAsRead($request);
    
    // Vérifier les résultats
    $responseData = json_decode($response->getContent(), true);
    $this->assertEquals('3 messages marked as read', $responseData['message']);
    $this->assertEquals(3, $responseData['updated_count']);
    
    // Vérifier que tous les messages de cet expéditeur à cet utilisateur sont marqués comme lus
    $this->assertEquals(0, Message::where('sender_id', $this->otherUser->id)
                          ->where('receiver_id', $this->user->id)
                          ->where('read', false)
                          ->count());
    }
}