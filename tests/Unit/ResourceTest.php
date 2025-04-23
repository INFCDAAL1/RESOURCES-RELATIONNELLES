<?php

namespace Tests\Unit\Models;

use App\Models\Resource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ResourceTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_get_download_url_returns_url_when_file_path_exists()
    {
        // Arrange
        $resource = new Resource();
        $resource->id = 1;
        $resource->file_path = 'resources/test-file.pdf';

        // On doit remplacer la route par un mock car on ne peut pas créer
        // une vraie route dans un test unitaire
        app()->bind('url', function () use ($resource) {
            $url = $this->getMockBuilder('Illuminate\Routing\UrlGenerator')
                ->disableOriginalConstructor()
                ->getMock();
            $url->method('route')
                ->with('resources.download', $resource->id)
                ->willReturn("http://example.com/resources/{$resource->id}/download");
            return $url;
        });

        // Act
        $downloadUrl = $resource->getDownloadUrlAttribute();

        // Assert
        $this->assertEquals("http://example.com/resources/1/download", $downloadUrl);
    }

    public function test_get_download_url_returns_null_when_no_file_path()
    {
        // Arrange
        $resource = new Resource();
        $resource->file_path = null;

        // Act
        $downloadUrl = $resource->getDownloadUrlAttribute();

        // Assert
        $this->assertNull($downloadUrl);
    }

    public function test_upload_file_stores_file_and_updates_attributes()
    {
        // Arrange
        $resource = new Resource();
        $file = UploadedFile::fake()->create('document.pdf', 500);

        // Mock save method to avoid persistence issues
        $resource = $this->getMockBuilder(Resource::class)
            ->onlyMethods(['save'])
            ->getMock();
        $resource->expects($this->once())
            ->method('save');

        // Act
        $path = $resource->uploadFile($file);

        // Assert
        Storage::disk('local')->assertExists($path);
        $this->assertEquals($path, $resource->file_path);
        $this->assertEquals($file->getClientMimeType(), $resource->file_type);
        $this->assertEquals($file->getSize(), $resource->file_size);
    }

    public function test_delete_file_removes_file_and_clears_attributes()
    {
        // Arrange
        $file = UploadedFile::fake()->create('document.pdf', 500);
        $path = Storage::putFile('resources', $file);
        
        $resource = new Resource();
        $resource->file_path = $path;
        $resource->file_type = 'application/pdf';
        $resource->file_size = 500;

        // Mock save method to avoid persistence issues
        $resource = $this->getMockBuilder(Resource::class)
            ->onlyMethods(['save'])
            ->disableOriginalConstructor()
            ->getMock();
        $resource->expects($this->once())
            ->method('save');
        $resource->file_path = $path;

        // Act
        $result = $resource->deleteFile();

        // Assert
        $this->assertTrue($result);
        Storage::assertMissing($path);
        $this->assertNull($resource->file_path);
        $this->assertNull($resource->file_type);
        $this->assertNull($resource->file_size);
    }
}