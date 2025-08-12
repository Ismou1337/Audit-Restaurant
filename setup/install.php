<?php
// This file handles the installation process, including database setup and initial configuration.

require_once '../src/config.php';

// Function to create the database and tables
function setupDatabase($pdo) {
    // Create the audits table
    $sql_audits = "
    CREATE TABLE IF NOT EXISTS audits (
        id INT AUTO_INCREMENT PRIMARY KEY,
        site VARCHAR(255) NOT NULL,
        responsable VARCHAR(255) NOT NULL,
        date_audit DATETIME NOT NULL,
        score DECIMAL(5,2) NOT NULL,
        ca_n1 DECIMAL(10,2) NOT NULL,
        ca_realise DECIMAL(10,2) NOT NULL,
        variation VARCHAR(50),
        conformes INT NOT NULL,
        total_elements INT NOT NULL,
        heure_debut TIME,
        heure_fin TIME,
        duree TIME,
        photos_count INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";

    // Create the audit_details table
    $sql_details = "
    CREATE TABLE IF NOT EXISTS audit_details (
        id INT AUTO_INCREMENT PRIMARY KEY,
        audit_id INT NOT NULL,
        section VARCHAR(255) NOT NULL,
        item_name VARCHAR(255) NOT NULL,
        item_value VARCHAR(255) NOT NULL,
        details TEXT,
        photo_filename VARCHAR(255),
        photo_path VARCHAR(255),
        thumb_filename VARCHAR(255),
        thumb_path VARCHAR(255),
        FOREIGN KEY (audit_id) REFERENCES audits(id) ON DELETE CASCADE
    )";

    // Execute the SQL statements
    $pdo->exec($sql_audits);
    $pdo->exec($sql_details);
}

// Main installation process
try {
    $pdo = getDBConnection();
    setupDatabase($pdo);
    echo "Installation réussie : Base de données et tables créées.";
} catch (Exception $e) {
    echo "Erreur lors de l'installation : " . $e->getMessage();
}
?>