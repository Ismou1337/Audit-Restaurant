<?php
/**
 * Enhanced Image Analysis - Better file detection
 * This will properly identify actual image files vs other files
 */

header('Content-Type: application/json');

function getDetailedImageAnalysis() {
    $report = [
        'timestamp' => date('Y-m-d H:i:s'),
        'analysis' => [],
        'file_details' => []
    ];
    
    $auditsPath = 'photos/audits/';
    
    if (is_dir($auditsPath)) {
        $allFiles = glob($auditsPath . '*');
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
        $actualImages = [];
        $otherFiles = [];
        $totalImageSize = 0;
        
        foreach ($allFiles as $file) {
            if (is_file($file)) {
                $filename = basename($file);
                $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                $size = filesize($file);
                $isImage = in_array($extension, $imageExtensions);
                
                $fileInfo = [
                    'name' => $filename,
                    'extension' => $extension,
                    'size_kb' => round($size / 1024, 2),
                    'size_bytes' => $size,
                    'is_image' => $isImage,
                    'date_modified' => date('Y-m-d H:i:s', filemtime($file)),
                    'permissions' => substr(sprintf('%o', fileperms($file)), -4)
                ];
                
                if ($isImage) {
                    $actualImages[] = $fileInfo;
                    $totalImageSize += $size;
                    
                    // Try to get image dimensions if possible
                    if (function_exists('getimagesize')) {
                        $imageInfo = @getimagesize($file);
                        if ($imageInfo) {
                            $fileInfo['width'] = $imageInfo[0];
                            $fileInfo['height'] = $imageInfo[1];
                            $fileInfo['type'] = $imageInfo['mime'];
                        }
                    }
                } else {
                    $otherFiles[] = $fileInfo;
                }
                
                $report['file_details'][] = $fileInfo;
            }
        }
        
        $report['analysis'] = [
            'directory' => $auditsPath,
            'total_files' => count($allFiles),
            'actual_images' => count($actualImages),
            'other_files' => count($otherFiles),
            'total_image_size_mb' => round($totalImageSize / (1024 * 1024), 2),
            'total_image_size_kb' => round($totalImageSize / 1024, 2),
            'image_breakdown' => [],
            'other_files_found' => []
        ];
        
        // Count by extension for images
        foreach ($actualImages as $img) {
            $ext = $img['extension'];
            if (!isset($report['analysis']['image_breakdown'][$ext])) {
                $report['analysis']['image_breakdown'][$ext] = ['count' => 0, 'total_size_kb' => 0];
            }
            $report['analysis']['image_breakdown'][$ext]['count']++;
            $report['analysis']['image_breakdown'][$ext]['total_size_kb'] += $img['size_kb'];
        }
        
        // List other files
        foreach ($otherFiles as $file) {
            $report['analysis']['other_files_found'][] = [
                'name' => $file['name'],
                'extension' => $file['extension'],
                'size_kb' => $file['size_kb'],
                'purpose' => $file['name'] === 'index.php' ? 'Security file (prevents directory listing)' : 'Unknown'
            ];
        }
        
        // Generate recommendations
        $recommendations = [];
        
        if (count($actualImages) > 0) {
            $recommendations[] = "✅ Found " . count($actualImages) . " actual image files";
            $recommendations[] = "📁 Total image size: " . $report['analysis']['total_image_size_mb'] . " MB";
            
            if ($report['analysis']['total_image_size_mb'] > 50) {
                $recommendations[] = "⚠️ Large image collection - consider optimization";
            } else {
                $recommendations[] = "✅ Image collection size is manageable";
            }
        } else {
            $recommendations[] = "ℹ️ No actual image files found in photos/audits/";
        }
        
        if (count($otherFiles) > 0) {
            $recommendations[] = "📄 Found " . count($otherFiles) . " non-image files";
            foreach ($otherFiles as $file) {
                if ($file['name'] === 'index.php') {
                    $recommendations[] = "🔒 index.php found - good security practice";
                }
            }
        }
        
        $recommendations[] = "🚀 Ready for GitHub structure integration";
        
        $report['recommendations'] = $recommendations;
        
    } else {
        $report['analysis'] = ['error' => 'photos/audits/ directory not found'];
        $report['recommendations'] = ['❌ photos/audits/ directory does not exist'];
    }
    
    return $report;
}

// Security check
if (basename($_SERVER['PHP_SELF']) === 'analyze-images-detailed.php') {
    try {
        $analysis = getDetailedImageAnalysis();
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
