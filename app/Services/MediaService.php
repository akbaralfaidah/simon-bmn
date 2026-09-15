<?php
namespace App\Services;

use App\Models\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class MediaService
{
    protected $manager;

    public function __construct()
    {
        $this->manager = new ImageManager(new Driver());
    }

    public function processAndSaveImage(UploadedFile $file, $mediable, string $context = 'general'): Media
    {
        $dateFolder = date('Y/m');
        $fileName = uniqid('media_') . '.webp';
        $thumbName = 'thumb_' . $fileName;
        
        $mainPath = "media/{$dateFolder}/{$fileName}";
        $thumbPath = "media/{$dateFolder}/{$thumbName}";
        
        $image = $this->manager->read($file->getRealPath());
        
        // Process Main Image (Max 1920px)
        $mainImage = clone $image;
        $mainImage->scaleDown(width: 1920);
        
        // Process Thumb Image (Max 480px)
        $thumbImage = clone $image;
        $thumbImage->scaleDown(width: 480);
        
        // Save to public disk
        Storage::disk('public')->put($mainPath, $mainImage->toWebp(82)->toString());
        Storage::disk('public')->put($thumbPath, $thumbImage->toWebp(78)->toString());
        
        // Create DB record
        return $mediable->media()->create([
            'file_path' => '/storage/' . $mainPath,
            'thumbnail_path' => '/storage/' . $thumbPath,
            'mime_type' => 'image/webp',
            'size' => Storage::disk('public')->size($mainPath),
            'context' => $context,
        ]);
    }
}
