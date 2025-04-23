<?php

namespace Tests\Unit\Models;

use App\Models\User;
use Tests\TestCase;

class UserTest extends TestCase
{
    public function test_is_admin_returns_true_for_admin_user()
    {
        // Arrange
        $user = new User();
        $user->role = 'admin';

        // Act & Assert
        $this->assertTrue($user->isAdmin());
    }

    public function test_is_admin_returns_false_for_non_admin_user()
    {
        // Arrange
        $user = new User();
        $user->role = 'user';

        // Act & Assert
        $this->assertFalse($user->isAdmin());
    }

    public function test_is_active_returns_true_for_active_user()
    {
        // Arrange
        $user = new User();
        $user->is_active = true;

        // Act & Assert
        $this->assertTrue($user->isActive());
    }

    public function test_is_active_returns_false_for_inactive_user()
    {
        // Arrange
        $user = new User();
        $user->is_active = false;

        // Act & Assert
        $this->assertFalse($user->isActive());
    }
}