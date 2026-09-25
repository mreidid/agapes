<?php
require_once 'config.php';
//require_once 'vendor/autoload.php'; // Nécessite l'installation de Stripe PHP SDK

// Vérification que les données de session existent
if (!isset($_SESSION['registration_data'])) {
    header('Location: index.php?error=' . urlencode('Aucune donnée d\'inscription trouvée. Veuillez recommencer.'));
    exit;
}

$registrationData = $_SESSION['registration_data'];

// Configuration Stripe
\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

try {
    // Création de la session de paiement Stripe
    $session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'line_items' => [[
            'price_data' => [
                'currency' => CURRENCY,
                'product_data' => [
                    'name' => 'Inscription au repas - ' . htmlspecialchars($registrationData['prenom'] . ' ' . $registrationData['nom']),
                ],
                'unit_amount' => round($registrationData['montant_total'] * 100), // Stripe utilise des centimes
            ],
            'quantity' => 1,
        ]],
        'mode' => 'payment',
        'success_url' => SITE_URL . '/success.php?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => SITE_URL . '/cancel.php',
        'customer_email' => $registrationData['email'],
        'metadata' => [
            'nom' => $registrationData['nom'],
            'prenom' => $registrationData['prenom'],
            'email' => $registrationData['email'],
            'telephone' => $registrationData['telephone'],
            'participations' => $registrationData['participations'],
            'options' => $registrationData['options'],
            'montant_total' => $registrationData['montant_total']
        ]
    ]);

    // Sauvegarde de l'ID de session dans la session utilisateur
    $_SESSION['stripe_session_id'] = $session->id;

    // Redirection vers Stripe Checkout
    header("Location: " . $session->url);
    exit;

} catch (Exception $e) {
    error_log("Erreur Stripe: " . $e->getMessage());
    header('Location: index.php?error=' . urlencode('Erreur lors de la création du paiement: ' . $e->getMessage()));
    exit;
}
