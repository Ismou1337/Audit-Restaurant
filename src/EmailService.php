<?php

class EmailService {
    private $mailer;

    public function __construct() {
        // Initialize the mailer (e.g., PHPMailer, SwiftMailer, etc.)
        $this->mailer = new PHPMailer(true); // Assuming PHPMailer is used
        $this->mailer->isSMTP();
        $this->mailer->Host = 'smtp.example.com'; // Set the SMTP server
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = 'your_email@example.com'; // SMTP username
        $this->mailer->Password = 'your_password'; // SMTP password
        $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mailer->Port = 587; // TCP port to connect to
    }

    public function sendAuditReport($data, $recipients) {
        try {
            $this->mailer->setFrom('from@example.com', 'Audit System');
            foreach ($recipients as $recipient) {
                $this->mailer->addAddress($recipient);
            }

            $this->mailer->isHTML(true);
            $this->mailer->Subject = $data['subject_prefix'] . ' Rapport d\'Audit - ID: ' . $data['audit_id'];
            $this->mailer->Body = $this->generateEmailBody($data);
            $this->mailer->AltBody = strip_tags($this->generateEmailBody($data));

            return $this->mailer->send();
        } catch (Exception $e) {
            error_log("Erreur lors de l'envoi de l'email: " . $this->mailer->ErrorInfo);
            throw new Exception("Erreur lors de l'envoi de l'email: " . $e->getMessage());
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