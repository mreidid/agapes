<?php
// Configuration de la base de données
ini_set('display_errors', 'On');
error_reporting(E_ALL);

define('DB_HOST', 'vckdfzirepasgodf.mysql.db');
define('DB_USER', 'vckdfzirepasgodf'); // À remplacer par votre utilisateur MySQL
define('DB_PASS', 'dFC8y28Gfeq9W9AZ');     // À remplacer par votre mot de passe MySQL
define('DB_NAME', 'vckdfzirepasgodf');
?>

<!-- Importation de la SDK JavaScript PayPal -->

<?php 
$table_participants = array(
					'id' => '',
					'date_repas' => '',
					'nom' => '',
					'prenom' => '',
					'email' => '',
					'date_saisie' => '',
					'tenue' => '',
					'repas' => '',
					'autre' => '',
					'paiement_en_ligne' => '',
					'paiement_presentiel' => '',
					'invite' => '');
					
// Configuration Stripe (à obtenir sur https://dashboard.stripe.com)
define('STRIPE_PUBLISHABLE_KEY', 'pk_test_votre_cle_publique');
define('STRIPE_SECRET_KEY', 'sk_test_votre_cle_secrete');

// Configuration de l'application
define('SITE_URL', 'https://www.formationmoreau.ovh/repas/'); // URL de votre site
define('CURRENCY', 'eur'); // Devise
define('SITE_NAME', 'Inscription Repas');

// Connexion à la base de données
//$pdo = mysqli_connect(DB_HOST, DB_NAME, DB_USER, DB_PASS);
//$pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.", ".DB_USER.", ".DB_PASS);
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
            
    );
    $res= "Connected successfully";
} catch (PDOException $e) {
    $res= "Connection failed: ";
}



// Fonction pour récupérer les tarifs
function getTarifs($pdo) {
    //global $pdo;
    $stmt = $pdo->query("SELECT * FROM tarifs");
    return $stmt->fetchAll();
}

// Fonction pour récupérer une option par son ID
function getTarifByType($type, $pdo) {
    //global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM tarifs WHERE type = ?");
    $stmt->execute([$type]);
    return $stmt->fetch();
}

// Démarrer la session
//session_start();
