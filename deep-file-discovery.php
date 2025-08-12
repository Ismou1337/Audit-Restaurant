<?php
/**
 * Deep File Discovery - Find ALL files in photos/audits/
 * This will show us exactly what those 7 other files are
 */

header('Content-Type: application/json');

function deepFileAnalysis() {
    $report = [
        'timestamp' => date('Y-m-d H:i:s'),
        'deep_scan' => [],
        'raw_listing' => [],
        'mime_analysis' => []
    ];
    
    $auditsPath = 'photos/audits/';
    
    if (is_dir($auditsPath)) {
        // Method 1: Raw directory listing
        $handle = opendir($auditsPath);
        $rawFiles = [];
        while (false !== ($file = readdir($handle))) {
            if ($file != "." && $file != "..") {
                $rawFiles[] = $file;
            }
        }
        closedir($handle);
        
        $report['raw_listing'] = [
            'method' => 'readdir()',
            'total_found' => count($rawFiles),
            'files' => $rawFiles
        ];
        
        // Method 2: Detailed analysis of each file
        foreach ($rawFiles as $filename) {
            $filepath = $auditsPath . $filename;
            
            $fileInfo = [
                'name' => $filename,
                'full_path' => $filepath,
                'is_file' => is_file($filepath),
                'is_dir' => is_dir($filepath),
                'exists' => file_exists($filepath),
                'readable' => is_readable($filepath),
                'size_bytes' => 0,
                'size_kb' => 0,
                'permissions' => '',
                'extension' => '',
                'mime_type' => 'unknown',
                'last_modified' => '',
                'is_hidden' => strpos($filename, '.') === 0
            ];
            
            if (file_exists($filepath)) {
                $fileInfo['size_bytes'] = filesize($filepath);
                $fileInfo['size_kb'] = round($fileInfo['size_bytes'] / 1024, 2);
                $fileInfo['permissions'] = substr(sprintf('%o', fileperms($filepath)), -4);
                $fileInfo['last_modified'] = date('Y-m-d H:i:s', filemtime($filepath));
                
                // Get extension
                $pathInfo = pathinfo($filename);
                $fileInfo['extension'] = isset($pathInfo['extension']) ? strtolower($pathInfo['extension']) : 'none';
                
                // Try to get MIME type
                if (function_exists('mime_content_type')) {
                    $fileInfo['mime_type'] = @mime_content_type($filepath);
                }
                
                // Alternative MIME detection
                if (function_exists('finfo_open')) {
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $fileInfo['mime_type_finfo'] = @finfo_file($finfo, $filepath);
                    finfo_close($finfo);
                }
                
                // Check if it's actually an image by trying to get image info
                if (function_exists('getimagesize')) {
                    $imageInfo = @getimagesize($filepath);
                    if ($imageInfo) {
                        $fileInfo['is_actual_image'] = true;
                        $fileInfo['image_width'] = $imageInfo[0];
                        $fileInfo['image_height'] = $imageInfo[1];
                        $fileInfo['image_type'] = $imageInfo['mime'];
                    } else {
                        $fileInfo['is_actual_image'] = false;
                    }
                }
                
                // File content preview (first 50 characters for text files)
                if ($fileInfo['size_bytes'] < 10000 && $fileInfo['size_bytes'] > 0) {
                    $handle = @fopen($filepath, 'r');
                    if ($handle) {
                        $preview = fread($handle, 50);
                        $fileInfo['content_preview'] = bin2hex($preview); // Hex preview for safety
                        fclose($handle);
                    }
                }
            }
            
            $report['deep_scan'][] = $fileInfo;
        }
        
        // Method 3: Try different glob patterns
        $globPatterns = [
            '*',           // All files
            '*.*',         // Files with extensions
            '.*',          // Hidden files
            '*.jpg',       // JPG images
            '*.jpeg',      // JPEG images
            '*.png',       // PNG images
            '*.gif',       // GIF images
            '*.webp',      // WebP images
            '*.[jJ][pP][gG]', // Case insensitive JPG
            '*.[pP][nN][gG]'  // Case insensitive PNG
        ];
        
        $globResults = [];
        foreach ($globPatterns as $pattern) {
            $matches = glob($auditsPath . $pattern);
            $globResults[$pattern] = [
                'pattern' => $pattern,
                'matches' => count($matches),
                'files' => array_map('basename', $matches)
            ];
        }
        
        $report['glob_patterns'] = $globResults;
        
        // Summary and analysis
        $actualImages = array_filter($report['deep_scan'], function($file) {
            return isset($file['is_actual_image']) && $file['is_actual_image'];
        });
        
        $report['summary'] = [
            'total_files_found' => count($rawFiles),
            'actual_images_detected' => count($actualImages),
            'non_image_files' => count($rawFiles) - count($actualImages),
            'total_size_kb' => array_sum(array_column($report['deep_scan'], 'size_kb')),
            'largest_file' => '',
            'image_files' => array_column($actualImages, 'name')
        ];
        
        // Find largest file
        if (!empty($report['deep_scan'])) {
            $largest = array_reduce($report['deep_scan'], function($carry, $item) {
                return ($item['size_bytes'] > $carry['size_bytes']) ? $item : $carry;
            }, ['size_bytes' => 0, 'name' => 'none']);
            $report['summary']['largest_file'] = $largest['name'] . ' (' . $largest['size_kb'] . ' KB)';
        }
        
    } else {
        $report['error'] = 'Directory photos/audits/ not found';
    }
    
    return $report;
}

// Security check
if (basename($_SERVER['PHP_SELF']) === 'deep-file-discovery.php') {
    try {
        $analysis = deepFileAnalysis();
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
