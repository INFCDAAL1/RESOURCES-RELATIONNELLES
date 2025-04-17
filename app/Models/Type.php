<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Type extends Model
{
    use HasFactory;

    /**
     * Attributes that are mass assignable.
     * 
     * @var array<int, string>
     */
    protected $fillable = [
        'name'
    ];

    /**
     * Get the resources that belong to this type.
     */
    public function resources(): HasMany
    {
        return $this->hasMany(Resource::class);
    }
}