<?php
/**
 * Safe Directory Setup for GitHub Structure
 * This script only ADDS new directories, never touches existing files
 * Upload to Cloudways and run once: https://your-domain.com/setup-directories.php
 */

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>GitHub Structure Setup</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 20px; }
        .success { color: #28a745; background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .warning { color: #856404; background: #fff3cd; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .error { color: #721c24; background: #f8d7da; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .info { color: #0c5460; background: #d1ecf1; padding: 10px; border-radius: 5px; margin: 10px 0; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
        .btn { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
    </style>
</head>
<body>
    <h1>🚀 GitHub Structure Setup</h1>
    <p><strong>Safe Setup:</strong> This script only creates new directories, never modifies existing files.</p>

<?php
function setupDirectories() {
    $results = [];
    $directories = [
        'photos/thumbs' => 0755,
        'uploads' => 0755,
        'uploads/temp' => 0777
    ];
    
    foreach ($directories as $dir => $permissions) {
        $result = ['dir' => $dir, 'permissions' => $permissions];
        
        if (is_dir($dir)) {
            $result['status'] = 'exists';
            $result['message'] = "Directory already exists";
            $result['current_permissions'] = substr(sprintf('%o', fileperms($dir)), -4);
        } else {
            if (mkdir($dir, $permissions, true)) {
                $result['status'] = 'created';
                $result['message'] = "Directory created successfully";
                $result['current_permissions'] = substr(sprintf('%o', fileperms($dir)), -4);
            } else {
                $result['status'] = 'error';
                $result['message'] = "Failed to create directory";
                $result['current_permissions'] = null;
            }
        }
        
        $results[] = $result;
    }
    
    return $results;
}

function createGitKeepFiles() {
    $gitkeepFiles = [
        'photos/thumbs/.gitkeep' => "# Thumbnail images directory\n# This file keeps the directory in Git",
        'uploads/temp/.gitkeep' => "# Temporary upload directory\n# This file keeps the directory in Git"
    ];
    
    $results = [];
    foreach ($gitkeepFiles as $file => $content) {
        $result = ['file' => $file];
        
        if (file_exists($file)) {
            $result['status'] = 'exists';
            $result['message'] = "File already exists";
        } else {
            if (file_put_contents($file, $content)) {
                $result['status'] = 'created';
                $result['message'] = "File created successfully";
            } else {
                $result['status'] = 'error';
                $result['message'] = "Failed to create file";
            }
        }
        
        $results[] = $result;
    }
    
    return $results;
}

if (isset($_POST['setup'])) {
    echo "<h2>📁 Setting up directories...</h2>";
    
    $dirResults = setupDirectories();
    foreach ($dirResults as $result) {
        $class = $result['status'] === 'error' ? 'error' : ($result['status'] === 'created' ? 'success' : 'info');
        echo "<div class='{$class}'>";
        echo "<strong>{$result['dir']}</strong>: {$result['message']}";
        if ($result['current_permissions']) {
            echo " (Permissions: {$result['current_permissions']})";
        }
        echo "</div>";
    }
    
    echo "<h2>📄 Creating .gitkeep files...</h2>";
    
    $fileResults = createGitKeepFiles();
    foreach ($fileResults as $result) {
        $class = $result['status'] === 'error' ? 'error' : ($result['status'] === 'created' ? 'success' : 'info');
        echo "<div class='{$class}'>";
        echo "<strong>{$result['file']}</strong>: {$result['message']}";
        echo "</div>";
    }
    
    echo "<h2>✅ Setup Complete!</h2>";
    echo "<div class='success'>";
    echo "<strong>Next Steps:</strong><br>";
    echo "1. Your existing images in <code>photos/audits/</code> are untouched<br>";
    echo "2. New directory structure is ready for GitHub<br>";
    echo "3. You can now test image uploads with the new structure<br>";
    echo "4. Run the image analysis to see your current setup";
    echo "</div>";
    
    echo '<p><a href="analyze-images.php" target="_blank" class="btn">🔍 Analyze Current Images</a></p>';
} else {
?>
    <h2>🛡️ Pre-Setup Check</h2>
    
    <div class="info">
        <strong>What this will do:</strong>
        <ul>
            <li>✅ Create <code>photos/thumbs/</code> directory</li>
            <li>✅ Create <code>uploads/</code> directory</li>
            <li>✅ Create <code>uploads/temp/</code> directory</li>
            <li>✅ Add <code>.gitkeep</code> files to preserve directories in Git</li>
            <li>🔒 <strong>Will NOT touch any existing files or images</strong></li>
        </ul>
    </div>
    
    <div class="warning">
        <strong>Current Status Check:</strong>
    </div>
    
    <?php
    $currentCheck = [
        'photos/audits' => is_dir('photos/audits'),
        'photos/thumbs' => is_dir('photos/thumbs'),
        'uploads' => is_dir('uploads'),
        'uploads/temp' => is_dir('uploads/temp')
    ];
    
    foreach ($currentCheck as $dir => $exists) {
        $class = $exists ? 'success' : 'info';
        $status = $exists ? '✅ Exists' : '➕ Will be created';
        echo "<div class='{$class}'><code>{$dir}/</code>: {$status}</div>";
    }
    ?>
    
    <form method="post">
        <p><button type="submit" name="setup" class="btn">🚀 Setup GitHub Structure</button></p>
    </form>
    
    <div class="info">
        <strong>Safe to run:</strong> This setup only adds new directories and never modifies existing files.
    </div>
<?php } ?>

</body>
</html>
