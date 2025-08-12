<?php

require_once 'config.php';
require_once 'EmailService.php';
require_once 'classes/ImageOptimizer.php';

// Démarrer la session une seule fois
session_start();

if ($_POST) {
    try {
        // Validation basique
        if (empty($_POST['site']) || empty($_POST['responsable'])) {
            throw new Exception("Site et responsable requis");
        }
        
        // Récupérer le token unique
        $audit_token = $_POST['audit_token'] ?? '';
        if (empty($audit_token)) {
            throw new Exception("Token d'audit manquant");
        }
        
        $pdo = getDBConnection();
        
        // Vérifier si la colonne audit_token existe
        try {
            $stmt_check_token = $pdo->prepare("SELECT id FROM audits WHERE audit_token = ?");
            $stmt_check_token->execute([$audit_token]);
            if ($existing_audit = $stmt_check_token->fetch()) {
                // Audit déjà traité avec ce token
                ?>
                <!DOCTYPE html>
                <html lang="fr">
                <head>
                    <meta charset="UTF-8">
                    <title>Audit déjà enregistré</title>
                    <style>
                        body { font-family: Arial, sans-serif; background: #f8fafc; padding: 20px; }
                        .warning { max-width: 600px; margin: 0 auto; background: #fff3cd; color: #856404; padding: 30px; border-radius: 12px; text-align: center; border: 2px solid #ffeaa7; }
                        .bouton { background: #667eea; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; display: inline-block; margin: 10px; }
                        .audit-id { font-size: 24px; font-weight: bold; color: #d69e2e; margin: 20px 0; }
                    </style>
                </head>
                <body>
                    <div class="warning">
                        <h1>⚠️ Audit déjà enregistré</h1>
                        <div class="audit-id">ID: #<?= $existing_audit['id'] ?></div>
                        <p>Cet audit a déjà été enregistré avec succès dans la base de données.</p>
                        <p><strong>Aucune action nécessaire.</strong></p>
                        <a href="index.php" class="bouton">Nouvel Audit</a>
                        <a href="admin.php" class="bouton">Voir Historique</a>
                    </div>
                </body>
                </html>
                <?php
                exit;
            }
        } catch (Exception $token_error) {
            error_log("Colonne audit_token non trouvée : " . $token_error->getMessage());
        }
        
        // Double vérification par contenu
        $stmt_check_duplicate = $pdo->prepare("
            SELECT id FROM audits 
            WHERE site = ? AND responsable = ? AND DATE(date_audit) = ? 
            AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
            ORDER BY created_at DESC LIMIT 1
        ");
        $stmt_check_duplicate->execute([
            $_POST['site'], 
            $_POST['responsable'], 
            $_POST['date']
        ]);
        
        if ($recent_audit = $stmt_check_duplicate->fetch() && !isset($_POST['force_create'])) {
            // Audit similaire trouvé dans les 5 dernières minutes
            ?>
            <!DOCTYPE html>
            <html lang="fr">
            <head>
                <meta charset="UTF-8">
                <title>Audit récent détecté</title>
                <style>
                    body { font-family: Arial, sans-serif; background: #f8fafc; padding: 20px; }
                    .warning { max-width: 600px; margin: 0 auto; background: #fff3cd; color: #856404; padding: 30px; border-radius: 12px; text-align: center; border: 2px solid #ffeaa7; }
                    .bouton { background: #667eea; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; display: inline-block; margin: 10px; }
                    .bouton-force { background: #e53e3e; }
                    .audit-id { font-size: 24px; font-weight: bold; color: #d69e2e; margin: 20px 0; }
                </style>
            </head>
            <body>
                <div class="warning">
                    <h1>⚠️ Audit récent détecté</h1>
                    <div class="audit-id">Audit existant ID: #<?= $recent_audit['id'] ?></div>
                    <p>Un audit similaire pour <strong><?= htmlspecialchars($_POST['site']) ?></strong> par <strong><?= htmlspecialchars($_POST['responsable']) ?></strong> a été enregistré il y a moins de 5 minutes.</p>
                    
                    <h3>🤔 Que souhaitez-vous faire ?</h3>
                    
                    <div style="margin: 20px 0;">
                        <a href="admin.php" class="bouton">✅ Voir l'audit existant</a>
                        <a href="index.php" class="bouton">🆕 Créer un nouvel audit</a>
                    </div>
                    
                    <form method="POST" style="margin-top: 30px; border-top: 2px solid #ffeaa7; padding-top: 20px;">
                        <?php foreach($_POST as $key => $value): ?>
                            <?php if (is_array($value)): ?>
                                <?php foreach($value as $subvalue): ?>
                                    <input type="hidden" name="<?= htmlspecialchars($key) ?>[]" value="<?= htmlspecialchars($subvalue) ?>">
                                <?php endforeach; ?>
                            <?php else: ?>
                                <input type="hidden" name="<?= htmlspecialchars($key) ?>" value="<?= htmlspecialchars($value) ?>">
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <input type="hidden" name="force_create" value="1">
                        <button type="submit" class="bouton bouton-force" onclick="return confirm('Êtes-vous sûr de vouloir créer un deuxième audit pour le même site/responsable/date ?')">
                            🚫 Forcer la création (déconseillé)
                        </button>
                    </form>
                </div>
            </body>
            </html>
            <?php
            exit;
        }
        
        // Variables pour les photos
        $uploaded_photos = [];
        $photos_dir = 'photos/audits/' . date('Y-m-d') . '/';
        
        // Créer le dossier photos si nécessaire
        if (!is_dir($photos_dir)) {
            mkdir($photos_dir, 0755, true);
        }
        
        // Traitement des photos (simple)
        if (!empty($_FILES)) {
            foreach ($_FILES as $field_name => $file) {
                if ($file['error'] === UPLOAD_ERR_OK && $file['size'] > 0) {
                    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
                    
                    if (in_array($extension, $allowed_extensions)) {
                        // Optimiser l'image avant de la sauvegarder
                        $imageOptimizer = new ImageOptimizer();
                        $optimized_image = $imageOptimizer->optimize($file['tmp_name']);
                        
                        $unique_name = 'audit_' . uniqid() . '_' . time() . '.' . $extension;
                        $photo_path = $photos_dir . $unique_name;
                        
                        if (move_uploaded_file($optimized_image, $photo_path)) {
                            $uploaded_photos[$field_name] = [
                                'filename' => $unique_name,
                                'path' => $photo_path
                            ];
                        }
                    }
                }
            }
        }
        
        // Calcul du score
        $total_items = 0;
        $conformes = 0;
        $sections = [];
        
        foreach ($_POST as $key => $value) {
            if (strpos($key, '_details') === false && 
                (strpos($key, 'cuisine_') === 0 || 
                 strpos($key, 'economat_') === 0 || 
                 strpos($key, 'front_') === 0 || 
                 strpos($key, 'service_') === 0 || 
                 strpos($key, 'rh_') === 0)) {
                
                $total_items++;
                if ($value === 'oui') {
                    $conformes++;
                }
                
                // Organiser les sections
                $section_name = '';
                if (strpos($key, 'cuisine_') === 0) $section_name = 'cuisine';
                elseif (strpos($key, 'economat_') === 0) $section_name = 'economat';
                elseif (strpos($key, 'front_') === 0) $section_name = 'front';
                elseif (strpos($key, 'service_') === 0) $section_name = 'service';
                elseif (strpos($key, 'rh_') === 0) $section_name = 'rh';
                
                $item_name = str_replace($section_name . '_', '', $key);
                $details_key = $key . '_details';
                $photo_key = 'photo_' . $key;
                
                $sections[$section_name][$item_name] = [
                    'value' => $value,
                    'details' => $_POST[$details_key] ?? '',
                    'photo' => isset($uploaded_photos[$photo_key]) ? $uploaded_photos[$photo_key] : null
                ];
            }
        }
        
        if ($total_items === 0) {
            throw new Exception("Aucune donnée d'audit trouvée");
        }
        
        $score = round(($conformes / $total_items) * 100, 2);
        
        // Données financières
        $ca_n1 = (float)($_POST['ca_n1'] ?? 0);
        $ca_realise = (float)($_POST['ca_realise'] ?? 0);
        
        // Calculer la durée
        $heure_debut = $_POST['heure_debut'] ?? '';
        $heure_fin = $_POST['heure_fin'] ?? '';
        $duree = '';
        
        if ($heure_debut && $heure_fin) {
            try {
                $debut = new DateTime($heure_debut);
                $fin = new DateTime($heure_fin);
                $interval = $debut->diff($fin);
                $duree = $interval->format('%H:%I:%S');
            } catch (Exception $e) {
                $duree = '';
            }
        }
        
        // Transaction avec double vérification
        $pdo->beginTransaction();
        
        // Insérer SANS audit_token d'abord (pour éviter l'erreur de colonne)
        $stmt = $pdo->prepare("
            INSERT INTO audits (site, responsable, date_audit, score, ca_n1, ca_realise, variation, conformes, total_elements, heure_debut, heure_fin, duree, photos_count) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([
            $_POST['site'],
            $_POST['responsable'],
            $_POST['date'] . ' ' . date('H:i:s'),
            $score,
            $ca_n1,
            $ca_realise,
            $_POST['variation'] ?? '0%',
            $conformes,
            $total_items,
            $heure_debut ?: null,
            $heure_fin ?: null,
            $duree ?: null,
            count($uploaded_photos)
        ]);
        
        if (!$result) {
            throw new Exception("Erreur lors de l'insertion de l'audit");
        }
        
        $audit_id = $pdo->lastInsertId();
        
        // Insérer les détails
        if (!empty($sections)) {
            $stmt_details = $pdo->prepare("
                INSERT INTO audit_details (audit_id, section, item_name, item_value, details, photo_filename, photo_path) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            foreach ($sections as $section_name => $section_data) {
                foreach ($section_data as $item_name => $item_data) {
                    $photo = $item_data['photo'];
                    
                    $stmt_details->execute([
                        $audit_id,
                        $section_name,
                        $item_name,
                        $item_data['value'],
                        $item_data['details'],
                        $photo ? $photo['filename'] : null,
                        $photo ? $photo['path'] : null
                    ]);
                }
            }
        }
        
        $pdo->commit();
        
        // Marquer le token comme utilisé en session
        $_SESSION['used_tokens'][] = $audit_token;
        
        // ENVOI D'EMAILS
        $email_result = ['success' => false, 'message' => 'Email désactivé'];
        
        try {
            $emailService = new EmailService();
            
            // Définir les destinataires selon le score
            $admin_emails = [];
            $subject_prefix = '';
            
            // TOUJOURS inclure le responsable principal
            $admin_emails[] = "o.ismail@restaurail.ma";
            
            // LOGIQUE SELON LE SCORE
            if ($score >= 90) {
                $admin_emails[] = "b.amamou@restaurail.ma";
                $admin_emails[] = "saidi@restaurail.ma";
                $subject_prefix = "🎉 EXCELLENT";
            } elseif ($score >= 80) {
                $admin_emails[] = "b.amamou@restaurail.ma";
                $admin_emails[] = "saidi@restaurail.ma";
                $subject_prefix = "✅ BON RÉSULTAT";
            } elseif ($score >= 70) {
                $admin_emails[] = "b.amamou@restaurail.ma";
                $admin_emails[] = "saidi@restaurail.ma";
                $subject_prefix = "⚠️ ATTENTION REQUISE";
            } elseif ($score >= 60) {
                $admin_emails[] = "b.amamou@restaurail.ma";
                $admin_emails[] = "saidi@restaurail.ma";
                $subject_prefix = "🔶 ACTION CORRECTIVE";
            } else {
                $admin_emails[] = "b.amamou@restaurail.ma";
                $admin_emails[] = "saidi@restaurail.ma";
                $subject_prefix = "🚨 INTERVENTION URGENTE";
            }
            
            // Managers par site
            $site_managers = [
                'LGV' => 'manager.lgv@restaurail.ma',
                'OASIS' => 'manager.oasis@restaurail.ma',
                'MARRAKECH' => 'z.hicham@restaurail.ma',
                'TANGER' => 'm.zineb@restaurail.ma'
            ];
            
            if (isset($site_managers[$_POST['site']])) {
                $admin_emails[] = $site_managers[$_POST['site']];
            }
            
            // Nettoyer et dédoublonner
            $admin_emails = array_unique(array_filter($admin_emails));
            
            // Préparer les données email
            $email_audit_data = [
                'site' => $_POST['site'],
                'date' => $_POST['date'],
                'responsable' => $_POST['responsable'],
                'ca_n1' => $ca_n1,
                'ca_realise' => $ca_realise,
                'variation' => $_POST['variation'] ?? '0%',
                'score' => $score,
                'duree' => $duree,
                'audit_id' => $audit_id,
                'photos_count' => count($uploaded_photos),
                'subject_prefix' => $subject_prefix,
                'stats' => [
                    'total' => $total_items,
                    'conformes' => $conformes,
                    'non_conformes' => $total_items - $conformes,
                    'nc' => 0
                ],
                'sections' => $sections
            ];
            
            // Envoyer l'email
            $email_result = $emailService->sendAuditReport($email_audit_data, $admin_emails);
            
        } catch (Exception $email_error) {
            error_log("Erreur envoi email: " . $email_error->getMessage());
            $email_result = [
                'success' => false,
                'message' => 'Erreur envoi email: ' . $email_error->getMessage()
            ];
        }
        
        // Affichage du résultat
        ?>
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Audit Enregistré avec Succès</title>
            <style>
                body { font-family: Arial, sans-serif; background: #f8fafc; margin: 0; padding: 20px; }
            </style>
        </head>
        <body>
            <h1>✅ Audit Enregistré avec Succès</h1>
            <p>Votre audit a été enregistré avec succès. Un rapport a été envoyé aux responsables.</p>
            <a href="index.php" class="bouton">Retour à l'accueil</a>
        </body>
        </html>
        <?php
    } catch (Exception $e) {
        // Gestion des erreurs
        ?>
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <title>Erreur</title>
            <style>
                body { font-family: Arial, sans-serif; background: #f8d7da; padding: 20px; }
                .error { color: #721c24; }
            </style>
        </head>
        <body>
            <h1 class="error">❌ Une erreur est survenue</h1>
            <p><?= htmlspecialchars($e->getMessage()) ?></p>
            <a href="index.php" class="bouton">Retour à l'accueil</a>
        </body>
        </html>
        <?php
    }
}
?>