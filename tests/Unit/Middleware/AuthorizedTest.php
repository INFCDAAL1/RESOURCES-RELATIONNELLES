<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\AuthorizedAdmin; // Changé pour utiliser le bon middleware
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Mockery;
use Tests\TestCase;

class AuthorizedTest extends TestCase
{
    protected $middleware;

    public function setUp(): void
    {
        parent::setUp();
        $this->middleware = new AuthorizedAdmin(); // Changé pour utiliser AuthorizedAdmin
    }

    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_returns_unauthorized_when_not_authenticated()
    {
        // Arrange
        $request = Mockery::mock(Request::class);
        $next = function ($request) {
            return response('OK', 200);
        };
        
        // Mock Auth::check() pour retourner false (non authentifié)
        Auth::shouldReceive('check')->once()->andReturn(false);
        
        // Act & Assert - on s'attend à une exception 401
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('Unauthorized action.');
        $this->middleware->handle($request, $next);
    }

    public function test_returns_forbidden_when_user_is_not_active()
    {
        // Arrange
        $request = Mockery::mock(Request::class);
        $next = function ($request) {
            return response('OK', 200);
        };
        
        // Mock un utilisateur inactif
        $user = Mockery::mock(User::class);
        $user->shouldReceive('isActive')->once()->andReturn(false);
        
        // Mock Auth facade
        Auth::shouldReceive('check')->once()->andReturn(true);
        Auth::shouldReceive('user')->once()->andReturn($user);
        
        // Act & Assert - on s'attend à une exception 403
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('User is not active.');
        $this->middleware->handle($request, $next);
    }

    public function test_returns_forbidden_when_user_is_not_admin()
    {
        // Arrange
        $request = Mockery::mock(Request::class);
        $next = function ($request) {
            return response('OK', 200);
        };
        
        // Mock un utilisateur actif non-admin
        $user = Mockery::mock(User::class);
        $user->shouldReceive('isActive')->once()->andReturn(true);
        $user->shouldReceive('isAdmin')->once()->andReturn(false);
        
        // Mock Auth facade
        Auth::shouldReceive('check')->once()->andReturn(true);
        Auth::shouldReceive('user')->times(2)->andReturn($user);
        
        // Act & Assert - on s'attend à une exception 403
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('Unauthorized action.');
        $this->middleware->handle($request, $next);
    }

    public function test_passes_request_to_next_middleware_for_admin_user()
    {
        // Arrange
        $request = Mockery::mock(Request::class);
        $called = false;
        $next = function ($request) use (&$called) {
            $called = true;
            return response('OK', 200);
        };
        
        // Mock un utilisateur actif admin
        $user = Mockery::mock(User::class);
        $user->shouldReceive('isActive')->once()->andReturn(true);
        $user->shouldReceive('isAdmin')->once()->andReturn(true);
        
        // Mock Auth facade
        Auth::shouldReceive('check')->once()->andReturn(true);
        Auth::shouldReceive('user')->times(2)->andReturn($user);
        
        // Act
        $response = $this->middleware->handle($request, $next);
        
        // Assert
        $this->assertTrue($called);
        $this->assertEquals('OK', $response->getContent());
    }
}