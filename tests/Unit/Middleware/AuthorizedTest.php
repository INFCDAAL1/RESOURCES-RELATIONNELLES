<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\Authorized;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Mockery;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class AuthorizedTest extends TestCase
{
    protected $middleware;

    public function setUp(): void
    {
        parent::setUp();
        $this->middleware = new Authorized();
    }

    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_redirects_to_login_when_not_authenticated()
    {
        // Arrange
        $request = Mockery::mock(Request::class);
        $next = function ($request) {
            return response('OK', 200);
        };
        
        // Mock Auth to return false for check
        Auth::shouldReceive('check')->once()->andReturn(false);
        
        // Mock the redirect to route helper
        $this->app->bind('redirect', function () {
            $redirectMock = Mockery::mock('Illuminate\Routing\Redirector');
            $redirectMock->shouldReceive('route')
                ->with('login')
                ->andReturn(new Response('Redirecting to login'));
            return $redirectMock;
        });
        
        // Act
        $response = $this->middleware->handle($request, $next);
        
        // Assert
        $this->assertEquals('Redirecting to login', $response->getContent());
    }

    public function test_returns_forbidden_when_user_is_not_active()
    {
        // Arrange
        $request = Mockery::mock(Request::class);
        $next = function ($request) {
            return response('OK', 200);
        };
        
        // Mock inactive user
        $user = Mockery::mock(User::class);
        $user->shouldReceive('isActive')->once()->andReturn(false);
        
        // Mock Auth to return true for check and our mock user
        Auth::shouldReceive('check')->once()->andReturn(true);
        Auth::shouldReceive('user')->once()->andReturn($user);
        
        // Act & Assert
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->middleware->handle($request, $next);
    }

    public function test_returns_forbidden_when_user_is_not_admin()
    {
        // Arrange
        $request = Mockery::mock(Request::class);
        $next = function ($request) {
            return response('OK', 200);
        };
        
        // Mock active user who is not admin
        $user = Mockery::mock(User::class);
        $user->shouldReceive('isActive')->once()->andReturn(true);
        $user->shouldReceive('isAdmin')->once()->andReturn(false);
        
        // Mock Auth facade
        Auth::shouldReceive('check')->once()->andReturn(true);
        Auth::shouldReceive('user')->twice()->andReturn($user);
        
        // Act & Assert
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
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
        
        // Mock active admin user
        $user = Mockery::mock(User::class);
        $user->shouldReceive('isActive')->once()->andReturn(true);
        $user->shouldReceive('isAdmin')->once()->andReturn(true);
        
        // Mock Auth facade
        Auth::shouldReceive('check')->once()->andReturn(true);
        Auth::shouldReceive('user')->twice()->andReturn($user);
        
        // Act
        $response = $this->middleware->handle($request, $next);
        
        // Assert
        $this->assertTrue($called);
        $this->assertEquals('OK', $response->getContent());
    }
}