<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Determines if the user is an administrator.
     *
     * @return bool
     */
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    /**
     * Determines if the user is active.
     *
     * @return bool
     */
    public function isActive()
    {
        return $this->is_active;
    }

    /**
     * Get the resources created by the user.
     */
    public function resources(): HasMany
    {
        return $this->hasMany(Resource::class);
    }

    /**
     * Get the comments created by the user.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * Get the sent messages.
     */
    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    /**
     * Get the received messages.
     */
    public function receivedMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'receiver_id');
    }

    /**
     * Get the sent invitations.
     */
    public function sentInvitations(): HasMany
    {
        return $this->hasMany(Invitation::class, 'sender_id');
    }

    /**
     * Get the received invitations.
     */
    public function receivedInvitations(): HasMany
    {
        return $this->hasMany(Invitation::class, 'receiver_id');
    }

    /**
     * Get the resource interactions associated with the user.
     */
    public function resourceInteractions(): HasMany
    {
        return $this->hasMany(ResourceInteraction::class);
    }

    /**
     * Get the user's favorite resources.
     */
    public function favoriteResources()
    {
        return $this->hasMany(ResourceInteraction::class)
            ->where('type', 'favorite')
            ->with('resource');
    }
    
    /**
     * Get the user's saved resources.
     */
    public function savedResources()
    {
        return $this->hasMany(ResourceInteraction::class)
            ->where('type', 'saved')
            ->with('resource');
    }
    
    /**
     * Get the user's exploited resources.
     */
    public function exploitedResources()
    {
        return $this->hasMany(ResourceInteraction::class)
            ->where('type', 'exploited')
            ->with('resource');
    }
}