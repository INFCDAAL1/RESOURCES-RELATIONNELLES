<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

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
        'file_path',
        'file_type',
        'file_size',
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
        'file_size' => 'integer',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'download_url',
    ];

    /**
     * Get the download URL for the resource file.
     */
    public function getDownloadUrlAttribute()
    {
        if ($this->file_path) {
            return route('resources.download', $this->id);
        }
        return null;
    }

    // Existing relationships...

    /**
     * Handle file upload for the resource.
     */
    public function uploadFile($file)
    {
        // Delete existing file if it exists
        if ($this->file_path && Storage::exists($this->file_path)) {
            Storage::delete($this->file_path);
        }

        // Store the new file
        $path = $file->store('resources');
        
        // Update resource attributes
        $this->file_path = $path;
        $this->file_type = $file->getClientMimeType();
        $this->file_size = $file->getSize();
        $this->save();
        
        return $path;
    }

    /**
     * Delete the file associated with this resource.
     */
    public function deleteFile()
    {
        if ($this->file_path && Storage::exists($this->file_path)) {
            Storage::delete($this->file_path);
            $this->file_path = null;
            $this->file_type = null;
            $this->file_size = null;
            $this->save();
            return true;
        }
        return false;
    }
}