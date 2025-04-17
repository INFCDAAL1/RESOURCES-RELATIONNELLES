<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Resource extends Model
{
    use HasFactory;

    /**
     * Attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'published',
        'validated',
        'link',
        'type_id',
        'category_id',
        'visibility_id',
        'user_id',
        'origin_id'
    ];

    /**
     * The attributes to be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'published' => 'boolean',
        'validated' => 'boolean',
        'created_at' => 'datetime',
    ];

    /**
     * Get the type that owns the resource.
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(Type::class);
    }

    /**
     * Get the category that owns the resource.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the visibility that owns the resource.
     */
    public function visibility(): BelongsTo
    {
        return $this->belongsTo(Visibility::class);
    }

    /**
     * Get the user that owns the resource.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the comments for the resource.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * Get the invitations for this resource.
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
    }

     /**
     * Get the origin of this resource.
     */
    public function origin(): BelongsTo
    {
        return $this->belongsTo(Origin::class);
    }
}