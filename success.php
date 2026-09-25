<?php
require_once 'config.php';
require_once 'vendor/autoload.php';

// Récupération de l'ID de session depuis l'URL
$sessionId = $_GET['session_id'] ?? null;

if (!$sessionId) {
    header('Location: index.php?error=' . urlencode('Aucun identifiant de session de paiement'));
    exit;
}

try {
    \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
    
    // Récupération de la session de paiement
    $session = \Stripe\Checkout\Session::retrieve($sessionId);
    
    // Vérification que le paiement est réussi
    if ($session->payment_status !== 'paid') {
        header('Location: cancel.php?error=' . urlencode('Paiement non validé'));
        exit;
    }
    
    // Récupération des métadonnées
    $metadata = $session->metadata;
    
    // Sauvegarde dans la base de données
    $stmt = $pdo->prepare("
        INSERT INTO participants 
        (nom, prenom, email, telephone, participations, montant_total, statut_paiement, stripe_session_id, stripe_payment_intent_id)
        VALUES (?, ?, ?, ?, ?, ?, 'paye', ?, ?)
    ");
    
    $stmt->execute([
        $metadata['nom'] ?? 'Inconnu',
        $metadata['prenom'] ?? 'Inconnu',
        $metadata['email'] ?? '',
        $metadata['telephone'] ?? '',
        $metadata['participations'] ?? '{}',
        $metadata['montant_total'] ?? 0,
        $sessionId,
        $session->payment_intent ?? null
    ]);
    
    // Nettoyage de la session
    unset($_SESSION['registration_data']);
    unset($_SESSION['stripe_session_id']);
    
} catch (Exception $e) {
    error_log("Erreur lors de la confirmation: " . $e->getMessage());
    header('Location: index.php?error=' . urlencode('Erreur lors de la confirmation du paiement'));
    exit;
}

// Données pour l'affichage
$participations = json_decode($metadata['participations'] ?? '{}', true);
$montant = $metadata['montant_total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= SITE_NAME ?> - Paiement réussi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .success-container {
            max-width: 600px;
            margin: 100px auto;
            padding: 40px;
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 0 30px rgba(0,0,0,0.1);
            text-align: center;
        }
        .success-icon {
            font-size: 5em;
            color: #28a745;
            margin-bottom: 20px;
        }
        .details-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            text-align: left;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="success-container">
            <div class="success-icon">✅</div>
            <h1>Paiement réussi !</h1>
            <p class="lead text-muted">Votre inscription a été enregistrée avec succès.</p>
            
            <div class="details-card">
                <h4>📋 Détails de votre inscription</h4>
                <hr>
                <p><strong>Nom :</strong> <?= htmlspecialchars($metadata['prenom'] . ' ' . $metadata['nom']) ?></p>
                <p><strong>Email :</strong> <?= htmlspecialchars($metadata['email']) ?></p>
                <?php if (!empty($metadata['telephone'])): ?>
                <p><strong>Téléphone :</strong> <?= htmlspecialchars($metadata['telephone']) ?></p>
                <?php endif; ?>
                
                <h5 class="mt-3">Participation(s) :</h5>
                <ul>
                    <?php if (($participations['aperitif'] ?? false)): ?>
                        <li>✓ Apéritif</li>
                    <?php endif; ?>
                    <?php if (($participations['repas'] ?? false)): ?>
                        <li>✓ Repas</li>
                    <?php endif; ?>
                </ul>
                
                <?php 
                $options = json_decode($metadata['options'] ?? '[]', true);
                if (!empty($options)):
                ?>
                <h5 class="mt-3">Options supplémentaires :</h5>
                <ul>
                    <?php foreach ($options as $option): ?>
                        <li>✓ <?= htmlspecialchars($option['nom']) ?> (+ <?= number_format($option['prix'], 2) ?> €)</li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
                
                <hr>
                <h4 class="text-end">Total payé : <strong><?= number_format($montant, 2) ?> €</strong></h4>
            </div>
            
            <p>Un email de confirmation vous a été envoyé à l'adresse indiquée.</p>
            <a href="index.php" class="btn btn-primary mt-3">Retour à l'accueil</a>
        </div>
    </div>
</body>
</html>
