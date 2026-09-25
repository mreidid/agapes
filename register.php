<?php
require_once 'config.php';

// Vérification de la méthode POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: repas.php?error=' . urlencode('Méthode non autorisée'));
    exit;
}

// Validation des données
$errors = [];

// Validation des champs obligatoires
//if (empty($_POST['nom'])) $errors[] = 'Le nom est obligatoire';
//if (empty($_POST['prenom'])) $errors[] = 'Le prénom est obligatoire';
if (empty($_POST['email']) || !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Email invalide';
}

// Validation de la participation
if (empty($_POST['participations'])) {
    $errors[] = 'Veuillez sélectionner au moins une option de participation';
}

// Vérification que les participations sont valides
$validParticipations = ['aperitif', 'repas', 'les_deux'];
foreach ($_POST['participations'] as $p) {
    if (!in_array($p, $validParticipations)) {
        $errors[] = 'Option de participation invalide';
        break;
    }
}

// Calcul du montant total
$montantTotal = 0;
$participations = $_POST['participations'] ?? [];

// Récupérer les tarifs
$tarifs = getTarifs();
$tarifMap = [];
foreach ($tarifs as $t) {
    $tarifMap[$t['type']] = $t['prix'];
}

// Calcul pour les participations
foreach ($participations as $p) {
    $montantTotal += $tarifMap[$p] ?? 0;
}

// Calcul pour les options supplémentaires
if (!empty($_POST['options'])) {
    $stmt = $pdo->prepare("SELECT prix FROM options WHERE id = ?");
    foreach ($_POST['options'] as $optionId) {
        $stmt->execute([$optionId]);
        $option = $stmt->fetch();
        if ($option) {
            $montantTotal += $option['prix'];
        }
    }
}

// Vérification du montant
if ($montantTotal <= 0) {
    $errors[] = 'Le montant total doit être supérieur à 0';
}

// Si des erreurs, rediriger vers le formulaire
if (!empty($errors)) {
    header('Location: repas.php?error=' . urlencode(implode(' - ', $errors)));
    exit;
}

// Préparation des données pour la base
$participationsJson = json_encode([
    'aperitif' => in_array('aperitif', $participations) || in_array('les_deux', $participations),
    'repas' => in_array('repas', $participations) || in_array('les_deux', $participations)
]);

$optionsJson = [];
if (!empty($_POST['options'])) {
    $stmt = $pdo->prepare("SELECT id, nom, prix FROM options WHERE id = ?");
    foreach ($_POST['options'] as $optionId) {
        $stmt->execute([$optionId]);
        $option = $stmt->fetch();
        if ($option) {
            $optionsJson[] = $option;
        }
    }
}

// Sauvegarde en session pour le paiement
$_SESSION['registration_data'] = [
    'nom' => trim($_POST['nom']),
    'prenom' => trim($_POST['prenom']),
    'email' => strtolower(trim($_POST['email'])),
    'telephone' => trim($_POST['telephone'] ?? ''),
    'participations' => $participationsJson,
    'options' => json_encode($optionsJson),
    'montant_total' => $montantTotal
];

// Redirection vers le paiement Stripe
//header('Location: payment.php');
header('https://www.paypal.com/ncp/payment/S95KTNPS69L44');
exit;
