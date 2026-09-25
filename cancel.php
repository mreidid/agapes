<?php
require_once 'config.php';

$error = $_GET['error'] ?? null;

// Nettoyage de la session
unset($_SESSION['registration_data']);
unset($_SESSION['stripe_session_id']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= SITE_NAME ?> - Paiement annulé</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .cancel-container {
            max-width: 600px;
            margin: 100px auto;
            padding: 40px;
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 0 30px rgba(0,0,0,0.1);
            text-align: center;
        }
        .cancel-icon {
            font-size: 5em;
            color: #dc3545;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="cancel-container">
            <div class="cancel-icon">❌</div>
            <h1>Paiement annulé</h1>
            <p class="lead text-muted">Votre inscription n'a pas été finalisée.</p>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars(urldecode($error)) ?>
                </div>
            <?php endif; ?>
            
            <p>Vous pouvez réessayer en cliquant sur le bouton ci-dessous.</p>
            <a href="index.php" class="btn btn-primary mt-3">Réessayer</a>
        </div>
    </div>
</body>
</html>
