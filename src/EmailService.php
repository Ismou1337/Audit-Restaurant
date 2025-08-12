<?php

// Import PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailService {
    private $mailer;

    public function __construct() {
        // Load configuration
        if (file_exists(__DIR__ . '/config.php')) {
            require_once __DIR__ . '/config.php';
        }
        
        // Initialize PHPMailer
        $this->mailer = new PHPMailer(true);
        $this->mailer->isSMTP();
        $this->mailer->Host = defined('SMTP_HOST') ? SMTP_HOST : 'localhost';
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = defined('SMTP_USER') ? SMTP_USER : '';
        $this->mailer->Password = defined('SMTP_PASS') ? SMTP_PASS : '';
        $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mailer->Port = defined('SMTP_PORT') ? SMTP_PORT : 587;
        $this->mailer->CharSet = 'UTF-8';
    }

    public function sendAuditReport($data, $recipients) {
        try {
            // Use your working email settings
            $fromEmail = defined('FROM_EMAIL') ? FROM_EMAIL : 'webmaster@restaurail.ma';
            $fromName = defined('FROM_NAME') ? FROM_NAME : 'Audit Restaurant System';
            
            $this->mailer->setFrom($fromEmail, $fromName);
            
            foreach ($recipients as $recipient) {
                $this->mailer->addAddress($recipient);
            }

            $this->mailer->isHTML(true);
            $this->mailer->Subject = $data['subject_prefix'] . ' Rapport d\'Audit - ID: ' . $data['audit_id'];
            $this->mailer->Body = $this->generateEmailBody($data);
            $this->mailer->AltBody = strip_tags($this->generateEmailBody($data));

            return $this->mailer->send();
        } catch (\Exception $e) {
            error_log("Erreur lors de l'envoi de l'email: " . $this->mailer->ErrorInfo);
            throw new \Exception("Erreur lors de l'envoi de l'email: " . $e->getMessage());
        }
    }

    private function generateEmailBody($data) {
        // Generate the HTML body for the email
        ob_start();
        ?>
        <h1><?= htmlspecialchars($data['subject_prefix']) ?> Rapport d'Audit</h1>
        <p>Site: <?= htmlspecialchars($data['site']) ?></p>
        <p>Date: <?= htmlspecialchars($data['date']) ?></p>
        <p>Responsable: <?= htmlspecialchars($data['responsable']) ?></p>
        <p>Score: <?= htmlspecialchars($data['score']) ?>%</p>
        <p>Durée: <?= htmlspecialchars($data['duree']) ?></p>
        <p>Audit ID: <?= htmlspecialchars($data['audit_id']) ?></p>
        <p>Photos: <?= htmlspecialchars($data['photos_count']) ?></p>
        <h2>Détails des Sections</h2>
        <ul>
            <?php foreach ($data['sections'] as $section => $items): ?>
                <li><strong><?= htmlspecialchars($section) ?></strong>
                    <ul>
                        <?php foreach ($items as $item_name => $item_data): ?>
                            <li>
                                <?= htmlspecialchars($item_name) ?>: <?= htmlspecialchars($item_data['value']) ?>
                                <?php if (!empty($item_data['photo'])): ?>
                                    <br><img src="<?= htmlspecialchars($item_data['photo']['path']) ?>" alt="<?= htmlspecialchars($item_name) ?>" width="100">
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </li>
            <?php endforeach; ?>
        </ul>
        <?php
        return ob_get_clean();
    }
}

?>