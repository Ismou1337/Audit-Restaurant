<?php
/**
 * Session Debug Tool - Temporary file to troubleshoot login issues
 * Remove this file after debugging
 */

session_start();

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Session Debug</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .debug-section { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .success { color: green; } .error { color: red; } .info { color: blue; }
        pre { background: #f8f8f8; padding: 10px; border-radius: 3px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🔍 Session Debug Tool</h1>
    
    <div class="debug-section">
        <h3>📊 Session Information</h3>
        <p><strong>Session ID:</strong> <?php echo session_id(); ?></p>
        <p><strong>Session Status:</strong> 
            <?php 
            $status = session_status();
            switch($status) {
                case PHP_SESSION_DISABLED: echo '<span class="error">DISABLED</span>'; break;
                case PHP_SESSION_NONE: echo '<span class="error">NONE</span>'; break;
                case PHP_SESSION_ACTIVE: echo '<span class="success">ACTIVE</span>'; break;
                default: echo '<span class="error">UNKNOWN</span>';
            }
            ?>
        </p>
        <p><strong>Session Save Path:</strong> <?php echo session_save_path(); ?></p>
        <p><strong>Session Cookie Domain:</strong> <?php echo ini_get('session.cookie_domain'); ?></p>
        <p><strong>Session Cookie Path:</strong> <?php echo ini_get('session.cookie_path'); ?></p>
    </div>
    
    <div class="debug-section">
        <h3>🗂️ Session Data</h3>
        <?php if (empty($_SESSION)): ?>
            <p class="error">❌ No session data found</p>
        <?php else: ?>
            <p class="success">✅ Session data exists</p>
            <pre><?php print_r($_SESSION); ?></pre>
        <?php endif; ?>
    </div>
    
    <div class="debug-section">
        <h3>🌐 Server Information</h3>
        <p><strong>Server:</strong> <?php echo $_SERVER['HTTP_HOST'] ?? 'Unknown'; ?></p>
        <p><strong>Request URI:</strong> <?php echo $_SERVER['REQUEST_URI'] ?? 'Unknown'; ?></p>
        <p><strong>HTTPS:</strong> <?php echo isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? '✅ Yes' : '❌ No'; ?></p>
        <p><strong>Domain Changed:</strong> Check if domain change affected cookies</p>
    </div>
    
    <div class="debug-section">
        <h3>🍪 Cookies</h3>
        <?php if (empty($_COOKIE)): ?>
            <p class="error">❌ No cookies found</p>
        <?php else: ?>
            <p class="success">✅ Cookies exist</p>
            <pre><?php print_r($_COOKIE); ?></pre>
        <?php endif; ?>
    </div>
    
    <div class="debug-section">
        <h3>🔧 Quick Fixes</h3>
        <p><strong>If login still doesn't work:</strong></p>
        <ol>
            <li><a href="login-admin.php?clear_session=1">Clear Session & Try Login</a></li>
            <li>Clear browser cookies for this domain</li>
            <li>Try incognito/private browsing mode</li>
            <li>Check if config.php database connection works</li>
        </ol>
        
        <?php if (isset($_GET['clear_session'])): ?>
            <?php 
            $_SESSION = array();
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
            }
            ?>
            <p class="success">✅ Session cleared! <a href="login-admin.php">Try logging in now</a></p>
        <?php endif; ?>
    </div>
    
    <div class="debug-section">
        <h3>🔗 Navigation</h3>
        <p>
            <a href="login-admin.php">🔐 Go to Login</a> | 
            <a href="admin.php">👤 Go to Admin</a> | 
            <a href="index.php">🏠 Go to Home</a>
        </p>
    </div>
    
    <div class="debug-section">
        <h3>⚠️ Important</h3>
        <p class="error">Delete this debug file (session-debug.php) after fixing the issue for security!</p>
    </div>
</body>
</html>
