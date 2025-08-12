<?php

class ImageOptimizer {
    private $maxWidth;
    private $maxHeight;

    public function __construct($maxWidth = 800, $maxHeight = 800) {
        $this->maxWidth = $maxWidth;
        $this->maxHeight = $maxHeight;
    }

    public function optimize($filePath) {
        list($width, $height, $type) = getimagesize($filePath);
        
        $ratio = $width / $height;

        if ($width > $this->maxWidth || $height > $this->maxHeight) {
            if ($ratio > 1) {
                $newWidth = $this->maxWidth;
                $newHeight = $this->maxWidth / $ratio;
            } else {
                $newHeight = $this->maxHeight;
                $newWidth = $this->maxHeight * $ratio;
            }
        } else {
            return $filePath; // No need to optimize
        }

        $srcImage = $this->createImageFromType($filePath, $type);
        $optimizedImage = imagecreatetruecolor($newWidth, $newHeight);
        
        imagecopyresampled($optimizedImage, $srcImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        return $this->saveImageToFile($optimizedImage, $filePath, $type);
    }

    private function createImageFromType($filePath, $type) {
        switch ($type) {
            case IMAGETYPE_JPEG:
                return imagecreatefromjpeg($filePath);
            case IMAGETYPE_PNG:
                return imagecreatefrompng($filePath);
            case IMAGETYPE_GIF:
                return imagecreatefromgif($filePath);
            default:
                throw new Exception("Unsupported image type: " . $type);
        }
    }

    private function saveImageToFile($image, $filePath, $type) {
        switch ($type) {
            case IMAGETYPE_JPEG:
                imagejpeg($image, $filePath, 85); // Quality set to 85
                break;
            case IMAGETYPE_PNG:
                imagepng($image, $filePath, 6); // Compression level set to 6
                break;
            case IMAGETYPE_GIF:
                imagegif($image, $filePath);
                break;
        }
        imagedestroy($image);
        return $filePath; // Return the path of the optimized image
    }
}

?>