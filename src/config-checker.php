<?php
/**
 * Configuration Checker Tool
 * Helps diagnose configuration issues
 */

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Configuration Checker</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .section { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .success { color: green; } .error { color: red; } .warning { color: orange; }
        .code { background: #f8f8f8; padding: 10px; border-radius: 3px; font-family: monospace; }
        .btn { background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>⚙️ Configuration Checker</h1>
    
    <div class="section">
        <h3>📁 File Checks</h3>
        
        <p><strong>config.php:</strong> 
        <?php if (file_exists('config.php')): ?>
            <span class="success">✅ EXISTS</span>
        <?php else: ?>
            <span class="error">❌ MISSING</span>
            <br><small>Copy config.example.php to config.php</small>
        <?php endif; ?>
        </p>
        
        <p><strong>config.example.php:</strong>
        <?php if (file_exists('config.example.php')): ?>
            <span class="success">✅ EXISTS</span>
        <?php else: ?>
            <span class="error">❌ MISSING</span>
        <?php endif; ?>
        </p>
        
        <p><strong>login-admin.php:</strong>
        <?php if (file_exists('login-admin.php')): ?>
            <span class="success">✅ EXISTS</span>
        <?php else: ?>
            <span class="error">❌ MISSING</span>
        <?php endif; ?>
        </p>
        
        <p><strong>admin.php:</strong>
        <?php if (file_exists('admin.php')): ?>
            <span class="success">✅ EXISTS</span>
        <?php else: ?>
            <span class="error">❌ MISSING</span>
        <?php endif; ?>
        </p>
    </div>
    
    <div class="section">
        <h3>🔧 Database Connection Test</h3>
        <?php
        if (file_exists('config.php')) {
            try {
                require_once 'config.php';
                
                echo '<p><strong>Config loaded:</strong> <span class="success">✅ SUCCESS</span></p>';
                
                // Check if constants are defined
                $required_constants = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS'];
                foreach ($required_constants as $const) {
                    if (defined($const)) {
                        echo "<p><strong>$const:</strong> <span class='success'>✅ DEFINED</span></p>";
                    } else {
                        echo "<p><strong>$const:</strong> <span class='error'>❌ MISSING</span></p>";
                    }
                }
                
                // Test database connection
                if (function_exists('getDBConnection')) {
                    echo '<p><strong>getDBConnection function:</strong> <span class="success">✅ EXISTS</span></p>';
                    
                    try {
                        $pdo = getDBConnection();
                        echo '<p><strong>Database connection:</strong> <span class="success">✅ SUCCESS</span></p>';
                        
                        // Test basic query
                        $stmt = $pdo->query('SELECT 1');
                        echo '<p><strong>Database query test:</strong> <span class="success">✅ SUCCESS</span></p>';
                        
                    } catch (Exception $e) {
                        echo '<p><strong>Database connection:</strong> <span class="error">❌ FAILED</span></p>';
                        echo '<p><small>Error: ' . htmlspecialchars($e->getMessage()) . '</small></p>';
                    }
                } else {
                    echo '<p><strong>getDBConnection function:</strong> <span class="error">❌ MISSING</span></p>';
                }
                
            } catch (Exception $e) {
                echo '<p><strong>Config loading:</strong> <span class="error">❌ FAILED</span></p>';
                echo '<p><small>Error: ' . htmlspecialchars($e->getMessage()) . '</small></p>';
            }
        } else {
            echo '<p><span class="error">❌ config.php not found</span></p>';
        }
        ?>
    </div>
    
    <div class="section">
        <h3>🚀 Quick Fixes</h3>
        
        <?php if (!file_exists('config.php')): ?>
            <div class="code">
                <strong>Step 1: Create config.php</strong><br>
                Copy the example file and update database settings:<br>
                <code>cp config.example.php config.php</code>
            </div>
        <?php endif; ?>
        
        <div class="code">
            <strong>Step 2: Update Database Settings</strong><br>
            Edit config.php and update these values:<br>
            - DB_HOST (your database host)<br>
            - DB_NAME (your database name)<br>
            - DB_USER (your database username)<br>
            - DB_PASS (your database password)
        </div>
        
        <p>
            <a href="login-admin.php" class="btn">🔐 Try Login Again</a>
            <a href="session-debug.php" class="btn">🔍 Session Debug</a>
        </p>
    </div>
    
    <div class="section">
        <h3>⚠️ Security Note</h3>
        <p class="error">Delete this file (config-checker.php) after fixing the issues!</p>
    </div>
</body>
</html>
