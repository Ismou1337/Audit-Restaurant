<?php
/**
 * Image Display Analysis - How does your admin show the images?
 * This will help us understand the current image loading mechanism
 */

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Image Display Analysis</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 20px auto; padding: 20px; }
        .success { color: #28a745; background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .warning { color: #856404; background: #fff3cd; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .error { color: #721c24; background: #f8d7da; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .info { color: #0c5460; background: #d1ecf1; padding: 10px; border-radius: 5px; margin: 10px 0; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; font-size: 12px; }
        .file-preview { border: 1px solid #ddd; padding: 10px; margin: 10px 0; border-radius: 5px; }
        .code-block { background: #2d3748; color: #e2e8f0; padding: 15px; border-radius: 5px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>🖼️ Image Display Analysis</h1>
    <p>Understanding how your application currently loads and displays images</p>

<?php

function analyzeImageDisplayMechanism() {
    $analysis = [
        'admin_file_check' => [],
        'config_analysis' => [],
        'possible_image_sources' => [],
        'database_connection_test' => []
    ];
    
    // Check if admin.php exists and analyze it
    if (file_exists('admin.php')) {
        $adminContent = file_get_contents('admin.php');
        
        $analysis['admin_file_check'] = [
            'exists' => true,
            'size_kb' => round(filesize('admin.php') / 1024, 2),
            'contains_image_logic' => false,
            'image_references' => [],
            'photo_references' => [],
            'database_queries' => []
        ];
        
        // Look for image-related code
        $imagePatterns = [
            '/photos?\/audits?/i' => 'Photo/audit path references',
            '/\.(jpg|jpeg|png|gif|webp)/i' => 'Image file extensions',
            '/img\s+src/i' => 'HTML img tags',
            '/image/i' => 'Image references',
            '/photo/i' => 'Photo references',
            '/upload/i' => 'Upload references',
            '/SELECT.*photo/i' => 'Database photo queries',
            '/SELECT.*image/i' => 'Database image queries'
        ];
        
        foreach ($imagePatterns as $pattern => $description) {
            if (preg_match_all($pattern, $adminContent, $matches)) {
                $analysis['admin_file_check']['image_references'][] = [
                    'pattern' => $description,
                    'matches' => count($matches[0]),
                    'examples' => array_slice($matches[0], 0, 3)
                ];
            }
        }
        
        $analysis['admin_file_check']['contains_image_logic'] = !empty($analysis['admin_file_check']['image_references']);
        
    } else {
        $analysis['admin_file_check'] = ['exists' => false];
    }
    
    // Check config.php for path definitions
    if (file_exists('config.php')) {
        $configContent = file_get_contents('config.php');
        
        $analysis['config_analysis'] = [
            'exists' => true,
            'path_definitions' => []
        ];
        
        // Look for path definitions
        $pathPatterns = [
            '/define\s*\(\s*[\'"].*PATH.*[\'"]\s*,\s*[\'"]([^\'"]*)[\'\"]/i' => 'PATH definitions',
            '/\$.*path.*=.*[\'"]([^\'"]*)[\'\"]/i' => 'Path variables',
            '/photos?\/audits?/i' => 'Photo/audit paths'
        ];
        
        foreach ($pathPatterns as $pattern => $description) {
            if (preg_match_all($pattern, $configContent, $matches)) {
                $analysis['config_analysis']['path_definitions'][] = [
                    'pattern' => $description,
                    'matches' => $matches[1] ?? $matches[0]
                ];
            }
        }
        
    } else {
        $analysis['config_analysis'] = ['exists' => false];
    }
    
    // Check for database connection and audit data
    try {
        if (file_exists('config.php')) {
            include_once 'config.php';
            
            if (defined('DB_HOST') && defined('DB_NAME') && defined('DB_USER') && defined('DB_PASS')) {
                $pdo = new PDO(
                    "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
                    DB_USER,
                    DB_PASS,
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                );
                
                $analysis['database_connection_test'] = [
                    'connected' => true,
                    'tables' => [],
                    'audit_data' => []
                ];
                
                // Get table list
                $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
                $analysis['database_connection_test']['tables'] = $tables;
                
                // Look for audit-related tables
                $auditTables = array_filter($tables, function($table) {
                    return stripos($table, 'audit') !== false;
                });
                
                foreach ($auditTables as $table) {
                    $stmt = $pdo->query("SELECT * FROM `$table` LIMIT 3");
                    $sampleData = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    $analysis['database_connection_test']['audit_data'][$table] = [
                        'row_count' => $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn(),
                        'columns' => array_keys($sampleData[0] ?? []),
                        'sample_data' => $sampleData
                    ];
                }
            }
        }
    } catch (Exception $e) {
        $analysis['database_connection_test'] = [
            'connected' => false,
            'error' => $e->getMessage()
        ];
    }
    
    // Check for possible image storage locations
    $possibleLocations = [
        'photos/audits/',
        'uploads/',
        'uploads/temp/',
        'images/',
        'assets/images/',
        'public/images/',
        'storage/images/'
    ];
    
    foreach ($possibleLocations as $location) {
        if (is_dir($location)) {
            $files = glob($location . '*');
            $analysis['possible_image_sources'][$location] = [
                'exists' => true,
                'file_count' => count($files),
                'files' => array_slice(array_map('basename', $files), 0, 10)
            ];
        } else {
            $analysis['possible_image_sources'][$location] = ['exists' => false];
        }
    }
    
    return $analysis;
}

$displayAnalysis = analyzeImageDisplayMechanism();
?>

<h2>📁 Admin File Analysis</h2>
<?php if ($displayAnalysis['admin_file_check']['exists']): ?>
    <div class="success">
        <strong>admin.php found</strong> (<?php echo $displayAnalysis['admin_file_check']['size_kb']; ?> KB)
        <?php if ($displayAnalysis['admin_file_check']['contains_image_logic']): ?>
            - Contains image handling logic ✅
        <?php endif; ?>
    </div>
    
    <?php if (!empty($displayAnalysis['admin_file_check']['image_references'])): ?>
        <h3>Image References Found:</h3>
        <table>
            <thead>
                <tr><th>Pattern Type</th><th>Matches</th><th>Examples</th></tr>
            </thead>
            <tbody>
                <?php foreach ($displayAnalysis['admin_file_check']['image_references'] as $ref): ?>
                <tr>
                    <td><?php echo htmlspecialchars($ref['pattern']); ?></td>
                    <td><?php echo $ref['matches']; ?></td>
                    <td><code><?php echo htmlspecialchars(implode(', ', $ref['examples'])); ?></code></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
<?php else: ?>
    <div class="error">admin.php not found in current directory</div>
<?php endif; ?>

<h2>⚙️ Configuration Analysis</h2>
<?php if ($displayAnalysis['config_analysis']['exists']): ?>
    <div class="success">config.php found</div>
    
    <?php if (!empty($displayAnalysis['config_analysis']['path_definitions'])): ?>
        <h3>Path Definitions Found:</h3>
        <?php foreach ($displayAnalysis['config_analysis']['path_definitions'] as $pathDef): ?>
            <div class="info">
                <strong><?php echo htmlspecialchars($pathDef['pattern']); ?>:</strong><br>
                <code><?php echo htmlspecialchars(implode(', ', $pathDef['matches'])); ?></code>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
<?php else: ?>
    <div class="error">config.php not found</div>
<?php endif; ?>

<h2>🗄️ Database Analysis</h2>
<?php if ($displayAnalysis['database_connection_test']['connected']): ?>
    <div class="success">Database connection successful</div>
    
    <h3>Tables Found:</h3>
    <div class="info">
        <?php echo implode(', ', $displayAnalysis['database_connection_test']['tables']); ?>
    </div>
    
    <?php if (!empty($displayAnalysis['database_connection_test']['audit_data'])): ?>
        <h3>Audit Data Analysis:</h3>
        <?php foreach ($displayAnalysis['database_connection_test']['audit_data'] as $table => $data): ?>
            <div class="file-preview">
                <h4>Table: <?php echo htmlspecialchars($table); ?></h4>
                <p><strong>Rows:</strong> <?php echo $data['row_count']; ?></p>
                <p><strong>Columns:</strong> <?php echo implode(', ', $data['columns']); ?></p>
                
                <?php if (!empty($data['sample_data'])): ?>
                    <p><strong>Sample Data:</strong></p>
                    <pre><?php echo htmlspecialchars(json_encode($data['sample_data'], JSON_PRETTY_PRINT)); ?></pre>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
    
<?php else: ?>
    <div class="error">
        Database connection failed: <?php echo htmlspecialchars($displayAnalysis['database_connection_test']['error'] ?? 'Unknown error'); ?>
    </div>
<?php endif; ?>

<h2>📂 Possible Image Locations</h2>
<table>
    <thead>
        <tr><th>Location</th><th>Exists</th><th>File Count</th><th>Sample Files</th></tr>
    </thead>
    <tbody>
        <?php foreach ($displayAnalysis['possible_image_sources'] as $location => $data): ?>
        <tr>
            <td><code><?php echo htmlspecialchars($location); ?></code></td>
            <td><?php echo $data['exists'] ? '✅' : '❌'; ?></td>
            <td><?php echo $data['exists'] ? $data['file_count'] : 'N/A'; ?></td>
            <td>
                <?php if ($data['exists'] && !empty($data['files'])): ?>
                    <code><?php echo htmlspecialchars(implode(', ', array_slice($data['files'], 0, 5))); ?></code>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<h2>🔧 Next Steps</h2>
<div class="info">
    <strong>Recommendations based on analysis:</strong>
    <ul>
        <li>🔍 Run the deep file discovery to find your actual image files</li>
        <li>📊 Check the database for image path information</li>
        <li>🔗 Analyze how admin.php loads images</li>
        <li>🎯 Implement dual-path support for GitHub integration</li>
    </ul>
</div>

<p>
    <a href="deep-file-discovery.php" target="_blank" style="background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;">🔍 Deep File Discovery</a>
    <a href="admin.php" target="_blank" style="background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">👤 View Admin Interface</a>
</p>

</body>
</html>
