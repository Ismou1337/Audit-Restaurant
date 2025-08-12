<?php
/**
 * Email Test Script - Verify SMTP is working
 * Delete after testing!
 */

// Auto-load composer dependencies
if (file_exists('../vendor/autoload.php')) {
    require_once '../vendor/autoload.php';
} elseif (file_exists('vendor/autoload.php')) {
    require_once 'vendor/autoload.php';
}

// Include EmailService
require_once 'EmailService.php';

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Email Test</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .section { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .success { color: green; } .error { color: red; } .info { color: blue; }
        input, button { padding: 10px; margin: 5px; border: 1px solid #ddd; border-radius: 3px; }
        button { background: #007bff; color: white; cursor: pointer; }
    </style>
</head>
<body>
    <h1>📧 Email Test Tool</h1>
    
    <div class="section">
        <h3>📋 Current SMTP Configuration</h3>
        <?php
        if (file_exists('config.php')) {
            require_once 'config.php';
            echo '<p><strong>SMTP Host:</strong> ' . (defined('SMTP_HOST') ? SMTP_HOST : 'Not defined') . '</p>';
            echo '<p><strong>SMTP Port:</strong> ' . (defined('SMTP_PORT') ? SMTP_PORT : 'Not defined') . '</p>';
            echo '<p><strong>SMTP User:</strong> ' . (defined('SMTP_USER') ? SMTP_USER : 'Not defined') . '</p>';
            echo '<p><strong>From Email:</strong> ' . (defined('FROM_EMAIL') ? FROM_EMAIL : 'Not defined') . '</p>';
        } else {
            echo '<p class="error">❌ config.php not found</p>';
        }
        ?>
    </div>
    
    <?php if ($_POST && isset($_POST['test_email'])): ?>
        <div class="section">
            <h3>📤 Test Results</h3>
            <?php
            try {
                $emailService = new EmailService();
                
                $testData = [
                    'subject_prefix' => '[TEST]',
                    'audit_id' => 'TEST-' . date('YmdHis'),
                    'site' => 'Test Restaurant',
                    'date' => date('Y-m-d H:i:s'),
                    'responsable' => 'Test User',
                    'score' => 95,
                    'duree' => '00:05:30',
                    'photos_count' => 3,
                    'sections' => [
                        'Propreté' => ['score' => 18, 'max' => 20],
                        'Service' => ['score' => 19, 'max' => 20],
                        'Qualité' => ['score' => 17, 'max' => 20]
                    ]
                ];
                
                $recipients = [trim($_POST['email'])];
                
                $result = $emailService->sendAuditReport($testData, $recipients);
                
                if ($result) {
                    echo '<p class="success">✅ Email sent successfully to ' . htmlspecialchars($_POST['email']) . '</p>';
                } else {
                    echo '<p class="error">❌ Failed to send email</p>';
                }
                
            } catch (Exception $e) {
                echo '<p class="error">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
            }
            ?>
        </div>
    <?php endif; ?>
    
    <div class="section">
        <h3>🧪 Send Test Email</h3>
        <form method="POST">
            <p>
                <label>Email Address:</label><br>
                <input type="email" name="email" required placeholder="test@example.com" style="width: 300px;">
            </p>
            <button type="submit" name="test_email">📧 Send Test Email</button>
        </form>
    </div>
    
    <div class="section">
        <h3>⚠️ Important</h3>
        <p class="error">Delete this file (email-test.php) after testing for security!</p>
    </div>
</body>
</html>
