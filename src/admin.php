<?php

session_start();

// Check if config.php exists
if (!file_exists('config.php')) {
    die('
    <div style="font-family: Arial; padding: 20px; background: #fee; color: #c33; border-radius: 5px; margin: 20px;">
        <h3>⚠️ Configuration Missing</h3>
        <p><strong>config.php</strong> file not found.</p>
        <p>Please copy <strong>config.example.php</strong> to <strong>config.php</strong> and update with your database settings.</p>
        <p><a href="login-admin.php">← Back to Login</a></p>
    </div>
    ');
}

require_once 'config.php';

// Vérifier l'authentification
if (!isset($_SESSION['admin_connecte']) || $_SESSION['admin_connecte'] !== true) {
    header('Location: login-admin.php');
    exit;
}

$type_utilisateur = $_SESSION['type_utilisateur'] ?? 'directeur';
$est_admin = ($type_utilisateur === 'admin');

$message_succes = '';
$message_erreur = '';

// Gestion de suppression avec photos
if ($_POST && $est_admin && $_POST['action'] === 'supprimer') {
    try {
        $pdo = getDBConnection();
        
        // Récupérer les photos avant suppression
        $stmt_photos = $pdo->prepare("SELECT photo_path, thumb_path FROM audit_details WHERE audit_id = ? AND (photo_path IS NOT NULL OR thumb_path IS NOT NULL)");
        $stmt_photos->execute([$_POST['id']]);
        $photos = $stmt_photos->fetchAll(PDO::FETCH_ASSOC);
        
        // Supprimer d'abord les détails de l'audit
        $stmt_details = $pdo->prepare("DELETE FROM audit_details WHERE audit_id = ?");
        $stmt_details->execute([$_POST['id']]);
        
        // Puis supprimer l'audit principal
        $stmt = $pdo->prepare("DELETE FROM audits WHERE id = ?");
        $result = $stmt->execute([$_POST['id']]);
        
        if ($result && $stmt->rowCount() > 0) {
            // Supprimer les fichiers photos du serveur
            $photos_supprimees = 0;
            foreach ($photos as $photo) {
                if (!empty($photo['photo_path']) && file_exists($photo['photo_path'])) {
                    unlink($photo['photo_path']);
                    $photos_supprimees++;
                }
                if (!empty($photo['thumb_path']) && file_exists($photo['thumb_path'])) {
                    unlink($photo['thumb_path']);
                    $photos_supprimees++;
                }
            }
            $message_succes = "Audit supprimé avec succès (incluant $photos_supprimees photo(s))";
        } else {
            $message_erreur = "Aucun audit trouvé avec cet ID";
        }
    } catch (Exception $e) {
        $message_erreur = "Erreur : " . $e->getMessage();
    }
}

// Charger les audits avec filtres
$pdo = getDBConnection();

$where_conditions = [];
$params = [];

if (isset($_GET['site']) && $_GET['site'] !== '') {
    $where_conditions[] = "site = ?";
    $params[] = $_GET['site'];
}

if (isset($_GET['score']) && $_GET['score'] !== '') {
    switch ($_GET['score']) {
        case 'excellent':
            $where_conditions[] = "score >= 80";
            break;
        case 'bon':
            $where_conditions[] = "score >= 60 AND score < 80";
            break;
        case 'faible':
            $where_conditions[] = "score < 60";
            break;
    }
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

$sql = "SELECT DISTINCT * FROM audits $where_clause ORDER BY id DESC, date_audit DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$audits = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Charger les détails pour chaque audit
$audits_uniques = [];
foreach ($audits as $audit) {
    if (isset($audits_uniques[$audit['id']])) {
        continue;
    }
    
    try {
        $stmt_details = $pdo->prepare("
            SELECT 
                section, item_name, item_value, details,
                photo_filename, photo_path, thumb_filename, thumb_path
            FROM audit_details 
            WHERE audit_id = ? 
            ORDER BY section, item_name
        ");
        $stmt_details->execute([$audit['id']]);
        $details = $stmt_details->fetchAll();
        
        $audit['sections'] = [];
        $audit['photos_count'] = 0;
        $audit['photos_available'] = 0; // Photos réellement disponibles
        
        foreach ($details as $detail) {
            $audit['sections'][$detail['section']][$detail['item_name']] = [
                'value' => $detail['item_value'],
                'details' => $detail['details'],
                'photo_filename' => $detail['photo_filename'],
                'photo_path' => $detail['photo_path'],
                'thumb_filename' => $detail['thumb_filename'],
                'thumb_path' => $detail['thumb_path']
            ];
            
            if (!empty($detail['photo_filename'])) {
                $audit['photos_count']++;
                
                if (!empty($detail['photo_path']) && file_exists($detail['photo_path'])) {
                    $audit['photos_available']++;
                }
            }
        }
        
    } catch (Exception $e) {
        error_log("Erreur lors du chargement des détails pour l'audit {$audit['id']}: " . $e->getMessage());
        $audit['sections'] = [];
        $audit['photos_count'] = 0;
        $audit['photos_available'] = 0;
    }
    
    $audits_uniques[$audit['id']] = $audit;
}

$audits = array_values($audits_uniques);
usort($audits, function($a, $b) {
    return $b['id'] - $a['id'];
});

// Statistiques
$stats = $pdo->query("
    SELECT 
        COUNT(DISTINCT audits.id) as total_audits,
        COUNT(DISTINCT site) as sites_uniques,
        AVG(score) as score_moyen,
        SUM(CASE WHEN score >= 80 THEN 1 ELSE 0 END) as audits_excellents,
        (SELECT COUNT(*) FROM audit_details WHERE photo_filename IS NOT NULL) as total_photos
    FROM audits
")->fetch();

$sites_uniques = $pdo->query("SELECT DISTINCT site FROM audits ORDER BY site")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $est_admin ? 'Administration' : 'Direction' ?> - Historique des Audits</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="conteneur">
        <div class="entete">
            <a href="logout-admin.php" class="bouton-deconnexion">Déconnexion</a>
            <h1><?= $est_admin ? '🛠️ Administration' : '📊 Direction' ?> - Historique des Audits</h1>
            <p>Connecté en tant que : <strong><?= htmlspecialchars($_SESSION['username']) ?></strong></p>
        </div>

        <?php if ($message_succes): ?>
            <div class="message-succes"><?= htmlspecialchars($message_succes) ?></div>
        <?php endif; ?>

        <?php if ($message_erreur): ?>
            <div class="message-erreur"><?= htmlspecialchars($message_erreur) ?></div>
        <?php endif; ?>

        <!-- Statistiques -->
        <div class="stats-grille">
            <div class="carte-stat">
                <div class="nombre-stat"><?= $stats['total_audits'] ?></div>
                <div class="libelle-stat">Total Audits</div>
            </div>
            <div class="carte-stat">
                <div class="nombre-stat"><?= $stats['sites_uniques'] ?></div>
                <div class="libelle-stat">Sites Audités</div>
            </div>
            <div class="carte-stat">
                <div class="nombre-stat"><?= round($stats['score_moyen'], 2) ?>%</div>
                <div class="libelle-stat">Score Moyen</div>
            </div>
            <div class="carte-stat">
                <div class="nombre-stat"><?= $stats['audits_excellents'] ?></div>
                <div class="libelle-stat">Audits Excellents</div>
            </div>
            <div class="carte-stat">
                <div class="nombre-stat"><?= $stats['total_photos'] ?></div>
                <div class="libelle-stat">Total Photos</div>
            </div>
        </div>

        <!-- Filtres -->
        <div class="filtres">
            <form method="GET">
                <select name="site">
                    <option value="">Tous les sites</option>
                    <?php foreach ($sites_uniques as $site): ?>
                        <option value="<?= htmlspecialchars($site) ?>"><?= htmlspecialchars($site) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="score">
                    <option value="">Tous les scores</option>
                    <option value="excellent">Excellent (≥ 80)</option>
                    <option value="bon">Bon (60 - 79)</option>
                    <option value="faible">Faible (< 60)</option>
                </select>
                <button type="submit">Filtrer</button>
            </form>
        </div>

        <!-- Actions principales -->
        <div class="actions-principales">
            <a href="index.php" class="bouton bouton-consulter">➕ Nouvel Audit</a>
            <a href="export-excel.php<?= !empty($_GET) ? '?' . http_build_query($_GET) : '' ?>" class="bouton bouton-excel">📊 Exporter Excel</a>
        </div>

        <!-- Affichage des audits -->
        <?php if (empty($audits)): ?>
            <div style="text-align: center; padding: 50px; background: white; border-radius: 8px;">
                <p>Aucun audit trouvé.</p>
            </div>
        <?php else: ?>
            <?php foreach ($audits as $audit): ?>
                <div class="carte-audit">
                    <div class="entete-audit">
                        <h2 class="titre-audit">Audit ID: <?= htmlspecialchars($audit['id']) ?></h2>
                        <span class="badge-score <?= $audit['score'] >= 80 ? 'score-excellent' : ($audit['score'] >= 60 ? 'score-bon' : 'score-faible') ?>">
                            <?= htmlspecialchars($audit['score']) ?>%
                        </span>
                    </div>
                    <div class="details-audit">
                        <?php foreach ($audit['sections'] as $section => $items): ?>
                            <div class="section-details">
                                <h4><?= htmlspecialchars(ucfirst($section)) ?></h4>
                                <?php foreach ($items as $item_name => $item_data): ?>
                                    <div class="item-audit">
                                        <span><?= htmlspecialchars($item_name) ?></span>
                                        <span class="<?= $item_data['value'] === 'oui' ? 'status-oui' : 'status-non' ?>">
                                            <?= htmlspecialchars($item_data['value']) ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="actions-audit">
                        <form method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet audit ?');">
                            <input type="hidden" name="id" value="<?= htmlspecialchars($audit['id']) ?>">
                            <input type="hidden" name="action" value="supprimer">
                            <button type="submit" class="bouton bouton-supprimer">🗑️ Supprimer</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script src="js/image-compress.js"></script>
    <script src="js/audit-form.js"></script>
</body>
</html>