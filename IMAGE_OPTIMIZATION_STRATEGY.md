# Image Optimization Strategy for GitHub Integration

## Current Problem
- ✅ Images stored in `photos/audits/[date]/[images]`
- ❌ 3MB per image = too large for Git
- ❌ Large images slow down deployments
- ❌ Repository becomes bloated quickly

## Solution: Smart Image Optimization

### Phase 1: Optimize Existing Images
```php
// Image optimization for existing files
function optimizeExistingImages() {
    $auditsPath = 'photos/audits/';
    $optimizedPath = 'photos/optimized/';
    $thumbsPath = 'photos/thumbs/';
    
    // Create directories
    if (!is_dir($optimizedPath)) mkdir($optimizedPath, 0755, true);
    if (!is_dir($thumbsPath)) mkdir($thumbsPath, 0755, true);
    
    // Find all date directories
    $dateDirs = glob($auditsPath . '*', GLOB_ONLYDIR);
    
    foreach ($dateDirs as $dateDir) {
        $images = glob($dateDir . '/*.{jpg,jpeg,png,gif}', GLOB_BRACE);
        
        foreach ($images as $imagePath) {
            $filename = basename($imagePath);
            $dateFolder = basename($dateDir);
            
            // Create optimized version (max 800x600, 85% quality)
            $optimizedFile = $optimizedPath . $dateFolder . '_' . $filename;
            optimizeImage($imagePath, $optimizedFile, 800, 600, 85);
            
            // Create thumbnail (200x150)
            $thumbFile = $thumbsPath . $dateFolder . '_' . $filename;
            optimizeImage($imagePath, $thumbFile, 200, 150, 80);
        }
    }
}
```

### Phase 2: New Upload Optimization
```php
// Optimize new uploads automatically
function processNewUpload($tempFile, $auditDate) {
    $originalPath = "photos/audits/{$auditDate}/";
    $optimizedPath = "photos/optimized/";
    $thumbsPath = "photos/thumbs/";
    
    // Ensure directories exist
    if (!is_dir($originalPath)) mkdir($originalPath, 0755, true);
    
    $filename = generateUniqueFilename();
    
    // Save original (for backup)
    $originalFile = $originalPath . $filename;
    move_uploaded_file($tempFile, $originalFile);
    
    // Create optimized version for web display (500KB max)
    $optimizedFile = $optimizedPath . $auditDate . '_' . $filename;
    optimizeImage($originalFile, $optimizedFile, 1200, 900, 75);
    
    // Create thumbnail (50KB max)
    $thumbFile = $thumbsPath . $auditDate . '_' . $filename;
    optimizeImage($originalFile, $thumbFile, 300, 225, 70);
    
    return [
        'original' => $originalFile,      // Keep on server only
        'optimized' => $optimizedFile,    // Use for display
        'thumbnail' => $thumbFile         // Use for lists
    ];
}
```

### Phase 3: GitHub Integration Strategy
```
GitHub Repository (Small):
├── src/                    # Code only
├── photos/
│   ├── optimized/         # Optimized images (300-500KB each)
│   └── thumbs/           # Thumbnails (50KB each)
└── uploads/temp/         # Temporary uploads

Server Only (Large):
└── photos/audits/        # Original 3MB images (NOT in Git)
    ├── 2025-08-12/
    ├── 2025-08-11/
    └── ...
```

## Benefits
- ✅ Git repo stays small (optimized images only)
- ✅ Fast web display (optimized versions)
- ✅ Quick loading (thumbnails)
- ✅ Originals preserved on server
- ✅ Easy deployment
- ✅ Better user experience
