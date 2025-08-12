<?php
/**
 * Image Optimization Tool
 * Reduces 3MB images to web-friendly sizes for GitHub integration
 */

class ImageOptimizer {
    private $maxOptimizedWidth = 1200;
    private $maxOptimizedHeight = 900;
    private $optimizedQuality = 75;
    
    private $maxThumbnailWidth = 300;
    private $maxThumbnailHeight = 225;
    private $thumbnailQuality = 70;
    
    public function optimizeImage($sourcePath, $destinationPath, $maxWidth, $maxHeight, $quality) {
        // Get image info
        $imageInfo = getimagesize($sourcePath);
        if (!$imageInfo) {
            return false;
        }
        
        $sourceWidth = $imageInfo[0];
        $sourceHeight = $imageInfo[1];
        $sourceType = $imageInfo[2];
        
        // Calculate new dimensions
        $ratio = min($maxWidth / $sourceWidth, $maxHeight / $sourceHeight);
        $newWidth = intval($sourceWidth * $ratio);
        $newHeight = intval($sourceHeight * $ratio);
        
        // Create source image resource
        switch ($sourceType) {
            case IMAGETYPE_JPEG:
                $sourceImage = imagecreatefromjpeg($sourcePath);
                break;
            case IMAGETYPE_PNG:
                $sourceImage = imagecreatefrompng($sourcePath);
                break;
            case IMAGETYPE_GIF:
                $sourceImage = imagecreatefromgif($sourcePath);
                break;
            default:
                return false;
        }
        
        if (!$sourceImage) {
            return false;
        }
        
        // Create destination image
        $destImage = imagecreatetruecolor($newWidth, $newHeight);
        
        // Preserve transparency for PNG and GIF
        if ($sourceType == IMAGETYPE_PNG || $sourceType == IMAGETYPE_GIF) {
            imagealphablending($destImage, false);
            imagesavealpha($destImage, true);
            $transparent = imagecolorallocatealpha($destImage, 255, 255, 255, 127);
            imagefilledrectangle($destImage, 0, 0, $newWidth, $newHeight, $transparent);
        }
        
        // Resize image
        imagecopyresampled(
            $destImage, $sourceImage,
            0, 0, 0, 0,
            $newWidth, $newHeight,
            $sourceWidth, $sourceHeight
        );
        
        // Ensure destination directory exists
        $destDir = dirname($destinationPath);
        if (!is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }
        
        // Save optimized image
        $success = false;
        switch ($sourceType) {
            case IMAGETYPE_JPEG:
                $success = imagejpeg($destImage, $destinationPath, $quality);
                break;
            case IMAGETYPE_PNG:
                // PNG quality is 0-9, convert from 0-100
                $pngQuality = intval(9 - ($quality / 100) * 9);
                $success = imagepng($destImage, $destinationPath, $pngQuality);
                break;
            case IMAGETYPE_GIF:
                $success = imagegif($destImage, $destinationPath);
                break;
        }
        
        // Clean up memory
        imagedestroy($sourceImage);
        imagedestroy($destImage);
        
        return $success;
    }
    
    public function processDateFolder($dateFolder) {
        $auditsPath = 'photos/audits/' . $dateFolder . '/';
        $optimizedPath = 'photos/optimized/';
        $thumbsPath = 'photos/thumbs/';
        
        if (!is_dir($auditsPath)) {
            return ['error' => 'Date folder not found: ' . $dateFolder];
        }
        
        // Create output directories
        if (!is_dir($optimizedPath)) mkdir($optimizedPath, 0755, true);
        if (!is_dir($thumbsPath)) mkdir($thumbsPath, 0755, true);
        
        $results = [
            'date_folder' => $dateFolder,
            'processed' => [],
            'errors' => [],
            'stats' => [
                'total_images' => 0,
                'successful' => 0,
                'failed' => 0,
                'original_size_mb' => 0,
                'optimized_size_mb' => 0,
                'thumbnail_size_mb' => 0,
                'space_saved_mb' => 0
            ]
        ];
        
        // Find all images
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        $images = [];
        
        foreach ($imageExtensions as $ext) {
            $images = array_merge($images, glob($auditsPath . '*.' . $ext));
            $images = array_merge($images, glob($auditsPath . '*.' . strtoupper($ext)));
        }
        
        $results['stats']['total_images'] = count($images);
        
        foreach ($images as $imagePath) {
            $filename = basename($imagePath);
            $originalSize = filesize($imagePath);
            $results['stats']['original_size_mb'] += $originalSize / (1024 * 1024);
            
            // Generate optimized filename
            $optimizedFilename = $dateFolder . '_' . $filename;
            $optimizedPath_full = $optimizedPath . $optimizedFilename;
            $thumbnailPath_full = $thumbsPath . $optimizedFilename;
            
            $imageResult = [
                'filename' => $filename,
                'original_size_kb' => round($originalSize / 1024, 2),
                'optimized_success' => false,
                'thumbnail_success' => false,
                'optimized_size_kb' => 0,
                'thumbnail_size_kb' => 0
            ];
            
            // Create optimized version
            if ($this->optimizeImage($imagePath, $optimizedPath_full, $this->maxOptimizedWidth, $this->maxOptimizedHeight, $this->optimizedQuality)) {
                $imageResult['optimized_success'] = true;
                $optimizedSize = filesize($optimizedPath_full);
                $imageResult['optimized_size_kb'] = round($optimizedSize / 1024, 2);
                $results['stats']['optimized_size_mb'] += $optimizedSize / (1024 * 1024);
            }
            
            // Create thumbnail
            if ($this->optimizeImage($imagePath, $thumbnailPath_full, $this->maxThumbnailWidth, $this->maxThumbnailHeight, $this->thumbnailQuality)) {
                $imageResult['thumbnail_success'] = true;
                $thumbnailSize = filesize($thumbnailPath_full);
                $imageResult['thumbnail_size_kb'] = round($thumbnailSize / 1024, 2);
                $results['stats']['thumbnail_size_mb'] += $thumbnailSize / (1024 * 1024);
            }
            
            if ($imageResult['optimized_success'] && $imageResult['thumbnail_success']) {
                $results['stats']['successful']++;
            } else {
                $results['stats']['failed']++;
                $results['errors'][] = 'Failed to process: ' . $filename;
            }
            
            $results['processed'][] = $imageResult;
        }
        
        $results['stats']['space_saved_mb'] = $results['stats']['original_size_mb'] - $results['stats']['optimized_size_mb'] - $results['stats']['thumbnail_size_mb'];
        
        // Round stats
        foreach (['original_size_mb', 'optimized_size_mb', 'thumbnail_size_mb', 'space_saved_mb'] as $key) {
            $results['stats'][$key] = round($results['stats'][$key], 2);
        }
        
        return $results;
    }
    
    public function getAllDateFolders() {
        $auditsPath = 'photos/audits/';
        $dateFolders = [];
        
        if (is_dir($auditsPath)) {
            $folders = glob($auditsPath . '*', GLOB_ONLYDIR);
            foreach ($folders as $folder) {
                $dateFolders[] = basename($folder);
            }
        }
        
        return $dateFolders;
    }
}

// Usage example (when called directly)
if (basename($_SERVER['PHP_SELF']) === 'image-optimizer.php') {
    header('Content-Type: application/json');
    
    $optimizer = new ImageOptimizer();
    
    if (isset($_GET['action'])) {
        switch ($_GET['action']) {
            case 'list_dates':
                echo json_encode(['date_folders' => $optimizer->getAllDateFolders()]);
                break;
                
            case 'process_date':
                if (isset($_GET['date'])) {
                    $result = $optimizer->processDateFolder($_GET['date']);
                    echo json_encode($result, JSON_PRETTY_PRINT);
                } else {
                    echo json_encode(['error' => 'Date parameter required']);
                }
                break;
                
            default:
                echo json_encode(['error' => 'Invalid action']);
        }
    } else {
        echo json_encode([
            'message' => 'Image Optimizer API',
            'usage' => [
                'list_dates' => '?action=list_dates',
                'process_date' => '?action=process_date&date=2025-08-12'
            ]
        ]);
    }
}
?>
