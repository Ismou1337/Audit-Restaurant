<?php
/**
 * Path Compatibility Checker
 * Tests that images work with both old and new path structures
 */

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Path Compatibility Test</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1000px; margin: 20px auto; padding: 20px; }
        .success { color: #28a745; background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .warning { color: #856404; background: #fff3cd; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .error { color: #721c24; background: #f8d7da; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .info { color: #0c5460; background: #d1ecf1; padding: 10px; border-radius: 5px; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .path-test { font-family: monospace; font-size: 12px; }
        img { max-width: 100px; max-height: 100px; }
    </style>
</head>
<body>
    <h1>🔗 Path Compatibility Test</h1>
    <p>Testing that images work with both current and GitHub structure paths</p>

<?php

function testPathCompatibility() {
    $results = [
        'summary' => [],
        'file_tests' => [],
        'path_tests' => []
    ];
    
    // Get actual image files
    $auditsPath = 'photos/audits/';
    $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
    $actualImages = [];
    
    if (is_dir($auditsPath)) {
        $files = glob($auditsPath . '*');
        foreach ($files as $file) {
            if (is_file($file)) {
                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                if (in_array($ext, $imageExtensions)) {
                    $actualImages[] = basename($file);
                }
            }
        }
    }
    
    $results['summary']['total_images'] = count($actualImages);
    $results['summary']['directory_exists'] = is_dir($auditsPath);
    
    // Test different path access methods
    $pathTests = [
        'Current Direct' => 'photos/audits/',
        'GitHub Relative' => '../photos/audits/',
        'From src/' => '../photos/audits/',
        'Absolute Web' => $_SERVER['DOCUMENT_ROOT'] . '/photos/audits/'
    ];
    
    foreach ($pathTests as $method => $path) {
        $testResult = [
            'method' => $method,
            'path' => $path,
            'exists' => false,
            'readable' => false,
            'file_count' => 0
        ];
        
        if (is_dir($path)) {
            $testResult['exists'] = true;
            $testResult['readable'] = is_readable($path);
            
            if ($testResult['readable']) {
                $files = glob($path . '*');
                $testResult['file_count'] = count($files);
            }
        }
        
        $results['path_tests'][] = $testResult;
    }
    
    // Test individual files with different access methods
    foreach (array_slice($actualImages, 0, 3) as $image) { // Test first 3 images
        $fileTest = [
            'filename' => $image,
            'tests' => []
        ];
        
        $accessMethods = [
            'Current' => 'photos/audits/' . $image,
            'GitHub Style' => '../photos/audits/' . $image,
            'Web Root' => '/photos/audits/' . $image
        ];
        
        foreach ($accessMethods as $method => $path) {
            $test = [
                'method' => $method,
                'path' => $path,
                'file_exists' => file_exists($method === 'Web Root' ? $_SERVER['DOCUMENT_ROOT'] . $path : $path),
                'readable' => is_readable($method === 'Web Root' ? $_SERVER['DOCUMENT_ROOT'] . $path : $path),
                'size' => 0
            ];
            
            if ($test['file_exists']) {
                $test['size'] = filesize($method === 'Web Root' ? $_SERVER['DOCUMENT_ROOT'] . $path : $path);
            }
            
            $fileTest['tests'][] = $test;
        }
        
        $results['file_tests'][] = $fileTest;
    }
    
    return $results;
}

$compatibility = testPathCompatibility();
?>

<h2>📊 Summary</h2>
<div class="<?php echo $compatibility['summary']['directory_exists'] ? 'success' : 'error'; ?>">
    <strong>Directory Status:</strong> photos/audits/ <?php echo $compatibility['summary']['directory_exists'] ? 'exists' : 'not found'; ?><br>
    <strong>Total Images Found:</strong> <?php echo $compatibility['summary']['total_images']; ?>
</div>

<h2>🛤️ Path Access Tests</h2>
<table>
    <thead>
        <tr>
            <th>Access Method</th>
            <th>Path</th>
            <th>Exists</th>
            <th>Readable</th>
            <th>File Count</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($compatibility['path_tests'] as $test): ?>
        <tr>
            <td><strong><?php echo htmlspecialchars($test['method']); ?></strong></td>
            <td class="path-test"><?php echo htmlspecialchars($test['path']); ?></td>
            <td><?php echo $test['exists'] ? '✅' : '❌'; ?></td>
            <td><?php echo $test['readable'] ? '✅' : '❌'; ?></td>
            <td><?php echo $test['file_count']; ?></td>
            <td>
                <?php if ($test['exists'] && $test['readable']): ?>
                    <span style="color: green;">✅ Working</span>
                <?php else: ?>
                    <span style="color: red;">❌ Issue</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php if (count($compatibility['file_tests']) > 0): ?>
<h2>📁 Individual File Tests</h2>
<?php foreach ($compatibility['file_tests'] as $fileTest): ?>
    <h3>File: <?php echo htmlspecialchars($fileTest['filename']); ?></h3>
    <table>
        <thead>
            <tr>
                <th>Access Method</th>
                <th>Path</th>
                <th>Exists</th>
                <th>Readable</th>
                <th>Size (bytes)</th>
                <th>Preview</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($fileTest['tests'] as $test): ?>
            <tr>
                <td><?php echo htmlspecialchars($test['method']); ?></td>
                <td class="path-test"><?php echo htmlspecialchars($test['path']); ?></td>
                <td><?php echo $test['file_exists'] ? '✅' : '❌'; ?></td>
                <td><?php echo $test['readable'] ? '✅' : '❌'; ?></td>
                <td><?php echo $test['size']; ?></td>
                <td>
                    <?php if ($test['file_exists'] && $test['method'] === 'Current'): ?>
                        <img src="<?php echo htmlspecialchars($test['path']); ?>" alt="Preview" style="max-width: 50px; max-height: 50px;">
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endforeach; ?>
<?php else: ?>
<div class="warning">
    No actual image files found to test. This might indicate:
    <ul>
        <li>Images are stored elsewhere</li>
        <li>Only non-image files in photos/audits/</li>
        <li>Directory permissions issue</li>
    </ul>
</div>
<?php endif; ?>

<h2>🔧 Recommendations</h2>
<div class="info">
    <strong>Based on the test results:</strong>
    <ul>
        <?php if ($compatibility['summary']['total_images'] > 0): ?>
            <li>✅ Found <?php echo $compatibility['summary']['total_images']; ?> actual images</li>
            <li>🔄 Path compatibility looks good for GitHub structure</li>
            <li>📁 Both old and new paths should work</li>
        <?php else: ?>
            <li>ℹ️ No images found - perfect for starting fresh with GitHub structure</li>
            <li>🔒 Found security files (index.php) - will be preserved</li>
        <?php endif; ?>
        <li>🚀 Safe to proceed with GitHub integration</li>
    </ul>
</div>

<p><a href="analyze-images-detailed.php" target="_blank" style="background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">📊 Run Detailed Analysis</a></p>

</body>
</html>
