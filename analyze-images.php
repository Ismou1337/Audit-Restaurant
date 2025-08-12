<?php
/**
 * Image Analysis Tool - Safe to run, only reads information
 * Upload this to your Cloudways public_html and run it once
 * URL: https://your-domain.com/analyze-images.php
 */

header('Content-Type: application/json');

function analyzeImageStructure() {
    $report = [
        'timestamp' => date('Y-m-d H:i:s'),
        'analysis' => [],
        'recommendations' => []
    ];
    
    // Check photos/audits directory
    $auditsPath = 'photos/audits/';
    if (is_dir($auditsPath)) {
        $images = glob($auditsPath . '*');
        $report['analysis']['current_structure'] = [
            'path' => $auditsPath,
            'exists' => true,
            'total_images' => count($images),
            'total_size_mb' => 0,
            'image_types' => [],
            'recent_images' => [],
            'permissions' => substr(sprintf('%o', fileperms($auditsPath)), -4)
        ];
        
        $totalSize = 0;
        $imageTypes = [];
        $recentImages = [];
        
        foreach ($images as $image) {
            if (is_file($image)) {
                $size = filesize($image);
                $totalSize += $size;
                
                $ext = strtolower(pathinfo($image, PATHINFO_EXTENSION));
                $imageTypes[$ext] = ($imageTypes[$ext] ?? 0) + 1;
                
                // Get recent images (last 7 days)
                if (filemtime($image) > (time() - 7 * 24 * 3600)) {
                    $recentImages[] = [
                        'name' => basename($image),
                        'size_kb' => round($size / 1024, 2),
                        'date' => date('Y-m-d H:i:s', filemtime($image))
                    ];
                }
            }
        }
        
        $report['analysis']['current_structure']['total_size_mb'] = round($totalSize / (1024 * 1024), 2);
        $report['analysis']['current_structure']['image_types'] = $imageTypes;
        $report['analysis']['current_structure']['recent_images'] = $recentImages;
    } else {
        $report['analysis']['current_structure'] = [
            'path' => $auditsPath,
            'exists' => false,
            'error' => 'Directory not found'
        ];
    }
    
    // Check if new structure directories exist
    $newDirs = ['photos/thumbs/', 'uploads/', 'uploads/temp/'];
    foreach ($newDirs as $dir) {
        $report['analysis']['new_structure'][$dir] = [
            'exists' => is_dir($dir),
            'writable' => is_dir($dir) ? is_writable($dir) : false,
            'permissions' => is_dir($dir) ? substr(sprintf('%o', fileperms($dir)), -4) : null
        ];
    }
    
    // Generate recommendations
    if ($report['analysis']['current_structure']['exists']) {
        $imageCount = $report['analysis']['current_structure']['total_images'];
        $sizeMB = $report['analysis']['current_structure']['total_size_mb'];
        
        if ($imageCount > 0) {
            $report['recommendations'][] = "✅ Found {$imageCount} images ({$sizeMB} MB) in current structure";
            $report['recommendations'][] = "🔄 Safe to proceed with GitHub integration";
            
            if ($sizeMB > 100) {
                $report['recommendations'][] = "⚠️ Large image directory ({$sizeMB} MB) - consider gradual migration";
            } else {
                $report['recommendations'][] = "✅ Image directory size is manageable ({$sizeMB} MB)";
            }
            
            if (count($report['analysis']['current_structure']['recent_images']) > 0) {
                $report['recommendations'][] = "📸 Recent activity detected - ensure new uploads work with GitHub structure";
            }
        } else {
            $report['recommendations'][] = "ℹ️ No images found - perfect for starting with clean GitHub structure";
        }
    }
    
    // Check for potential issues
    $issues = [];
    if (!is_writable('photos/')) {
        $issues[] = "❌ photos/ directory not writable";
    }
    if (!is_dir('uploads/') && !mkdir('uploads/', 0755, true)) {
        $issues[] = "❌ Cannot create uploads/ directory";
    }
    if (!is_dir('photos/thumbs/') && !mkdir('photos/thumbs/', 0755, true)) {
        $issues[] = "❌ Cannot create photos/thumbs/ directory";
    }
    
    $report['analysis']['potential_issues'] = $issues;
    
    return $report;
}

// Security check - only run if accessed directly
if (basename($_SERVER['PHP_SELF']) === 'analyze-images.php') {
    try {
        $analysis = analyzeImageStructure();
        echo json_encode($analysis, JSON_PRETTY_PRINT);
    } catch (Exception $e) {
        echo json_encode([
            'error' => true,
            'message' => $e->getMessage(),
            'timestamp' => date('Y-m-d H:i:s')
        ], JSON_PRETTY_PRINT);
    }
} else {
    echo json_encode(['error' => 'This script must be accessed directly'], JSON_PRETTY_PRINT);
}
?>
