<?php
session_start();
require_once 'db.php';

// Gestion de la Déconnexion
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: administrateur.php");
    exit();
}

// Authentification Administrateur[cite: 1]
$error_login = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_action'])) {
    $login = trim($_POST['login']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE Code_utilisateur = ?");
    $stmt->execute([$login]);
    $user = $stmt->fetch();

    // Vérification mot de passe haché ou compatibilité ancienne valeur brute[cite: 1]
    if ($user && (password_verify($password, $user['Mot_de_passe_utilisateur']) || $password === $user['Mot_de_passe_utilisateur'])) {
        $_SESSION['admin_logged'] = true;
        $_SESSION['admin_user'] = $login;
    } else {
        $error_login = "Identifiant ou mot de passe incorrect.";
    }
}

// Interdire l'accès si non authentifié
if (!isset($_SESSION['admin_logged'])) :
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Administration - Connexion</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 flex items-center justify-center min-h-screen p-4">
    <div class="bg-white p-8 rounded-2xl shadow-md max-w-md w-full border border-slate-200">
        <h2 class="text-2xl font-bold text-center text-slate-800 mb-6">Accès Administration</h2>
        <?php if ($error_login): ?>
            <div class="mb-4 p-3 bg-red-100 text-red-700 text-sm rounded-lg"><?= $error_login ?></div>
        <?php endif; ?>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="login_action" value="1">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Identifiant</label>
                <input type="text" name="login" required class="w-full px-4 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Mot de passe</label>
                <input type="password" name="password" required class="w-full px-4 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>
            <button type="submit" class="w-full py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl shadow transition">Se connecter</button>
        </form>
    </div>
</body>
</html>
<?php exit(); endif; ?>

<?php
// TRAITEMENTS APRES CONNEXION

// 1. Changement de mot de passe[cite: 1]
$msg_pwd = "";
if (isset($_POST['change_password'])) {
    $new_pwd = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE utilisateurs SET Mot_de_passe_utilisateur = ? WHERE Code_utilisateur = ?");
    $stmt->execute([$new_pwd, $_SESSION['admin_user']]);
    $msg_pwd = "Mot de passe mis à jour avec succès.";
}

// 2. Création d'un repas[cite: 1]
$msg_repas = "";
if (isset($_POST['create_repas'])) {
    $r_date = $_POST['repas_date'];
    $r_intitule = $_POST['Repas_intitule'];
    $r_ordre = $_POST['Repas_ordre_du_jour'];
    $r_tarif = $_POST['Repas_tarif'];
    $r_com = $_POST['Repas_commentaire'];
    $r_autre = $_POST['Autre_action'];
    $r_vis = isset($_POST['Liste_visible']) ? 1 : 0;

    $stmt = $pdo->prepare("INSERT INTO repas (repas_date, Repas_intitule, Repas_ordre_du_jour, Repas_tarif, Repas_commentaire, Autre_action, Liste_visible) VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE Repas_intitule=?, Repas_ordre_du_jour=?, Repas_tarif=?, Repas_commentaire=?, Autre_action=?, Liste_visible=?");
    $stmt->execute([$r_date, $r_intitule, $r_ordre, $r_tarif, $r_com, $r_autre, $r_vis, $r_intitule, $r_ordre, $r_tarif, $r_com, $r_autre, $r_vis]);
    $msg_repas = "Événement créé ou mis à jour !";
}

// 3. Modification ponctuelle des soldes clients (Table liste_client)[cite: 1]
if (isset($_POST['update_client_solde'])) {
    $c_id = $_POST['client_id'];
    $c_du = $_POST['total_montant_du'];
    $c_regle = $_POST['total_montant_regle'];
    $stmt = $pdo->prepare("UPDATE liste_client SET Total_montant_du = ?, Total_montant_regle = ? WHERE id = ?");
    $stmt->execute([$c_du, $c_regle, $c_id]);
}

// Données consultables
$annee_filtre = $_GET['annee'] ?? date('Y');
$clients = $pdo->query("SELECT * FROM liste_client ORDER BY nom, prenom")->fetchAll();

// Clients en retard de paiement[cite: 1]
$retards = $pdo->query("SELECT * FROM liste_client WHERE Total_montant_du > Total_montant_regle ORDER BY nom")->fetchAll();

// Inscrits pour impression[cite: 1]
$inscrits_impression = $pdo->query("SELECT * FROM inscription_repas ORDER BY date_repas DESC, nom ASC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Espace Administrateur</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @media print {
            body * { visibility: hidden; }
            #section-impression, #section-impression * { visibility: visible; }
            #section-impression { position: absolute; left: 0; top: 0; width: 100%; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body class="bg-slate-100 font-sans text-slate-800">

<nav class="bg-slate-900 text-white px-6 py-4 flex justify-between items-center no-print">
    <h1 class="font-bold text-lg"><i class="fa-solid fa-user-gear text-indigo-400 mr-2"></i> Panneau Administrateur</h1>
    <a href="?logout=1" class="text-sm bg-red-600 hover:bg-red-700 px-3 py-1.5 rounded-lg transition"><i class="fa-solid fa-power-off mr-1"></i> Déconnexion</a>
</nav>

<div class="max-w-6xl mx-auto p-6 space-y-8 no-print">

    <!-- Section 1 : Création / Gestion d'un Repas -->
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200">
        <h2 class="text-xl font-bold mb-4 text-slate-900 flex items-center"><i class="fa-solid fa-calendar-plus text-indigo-600 mr-2"></i> Créer / Modifier un Repas</h2>
        <?php if ($msg_repas): ?><p class="text-emerald-600 font-bold mb-3"><?= $msg_repas ?></p><?php endif; ?>
        <form method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <input type="hidden" name="create_repas" value="1">
            <div>
                <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Date</label>
                <input type="date" name="repas_date" required class="w-full px-3 py-2 border rounded-xl">
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Intitulé</label>
                <input type="text" name="Repas_intitule" required class="w-full px-3 py-2 border rounded-xl">
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Tarif (€)</label>
                <input type="number" step="0.01" name="Repas_tarif" required class="w-full px-3 py-2 border rounded-xl">
            </div>
            <div class="md:col-span-3">
                <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Ordre du jour</label>
                <textarea name="Repas_ordre_du_jour" rows="2" class="w-full px-3 py-2 border rounded-xl"></textarea>
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Commentaire</label>
                <input type="text" name="Repas_commentaire" class="w-full px-3 py-2 border rounded-xl">
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Autre Action (Optionnel)</label>
                <input type="text" name="Autre_action" placeholder="Ex: Apéritif" class="w-full px-3 py-2 border rounded-xl">
            </div>
            <div class="md:col-span-3 flex items-center justify-between">
                <label class="flex items-center text-sm font-medium">
                    <input type="checkbox" name="Liste_visible" value="1" checked class="mr-2 rounded text-indigo-600">
                    Rendre la liste des participants visible aux clients
                </label>
                <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl transition">Enregistrer</button>
            </div>
        </form>
    </div>

    <!-- Section 2 : Clients en retard de paiement -->
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200">
        <h2 class="text-xl font-bold mb-4 text-red-600 flex items-center"><i class="fa-solid fa-triangle-exclamation mr-2"></i> Clients en Retard de Paiement</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b text-slate-400">
                        <th class="py-2">Client</th>
                        <th class="py-2">Total Dû</th>
                        <th class="py-2">Total Réglé</th>
                        <th class="py-2">Reste à Payer</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <?php foreach ($retards as $r): ?>
                        <tr>
                            <td class="py-2 font-semibold"><?= htmlspecialchars($r['nom']) ?> <?= htmlspecialchars($r['prenom']) ?></td>
                            <td class="py-2"><?= $r['Total_montant_du'] ?> €</td>
                            <td class="py-2 text-emerald-600"><?= $r['Total_montant_regle'] ?> €</td>
                            <td class="py-2 font-bold text-red-600"><?= $r['Total_montant_du'] - $r['Total_montant_regle'] ?> €</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Section 3 : Modification des Comptes Clients -->
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200">
        <h2 class="text-xl font-bold mb-4 text-slate-900 flex items-center"><i class="fa-solid fa-users-gear mr-2"></i> Récapitulatif des Clients & Solde</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b text-slate-400">
                        <th class="py-2">Client</th>
                        <th class="py-2">Montant Dû (€)</th>
                        <th class="py-2">Montant Réglé (€)</th>
                        <th class="py-2">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <?php foreach ($clients as $c): ?>
                        <tr>
                            <form method="POST">
                                <input type="hidden" name="client_id" value="<?= $c['id'] ?>">
                                <td class="py-2 font-semibold"><?= htmlspecialchars($c['nom']) ?> <?= htmlspecialchars($c['prenom']) ?></td>
                                <td class="py-2"><input type="number" step="0.01" name="total_montant_du" value="<?= $c['Total_montant_du'] ?>" class="w-24 px-2 py-1 border rounded"></td>
                                <td class="py-2"><input type="number" step="0.01" name="total_montant_regle" value="<?= $c['Total_montant_regle'] ?>" class="w-24 px-2 py-1 border rounded"></td>
                                <td class="py-2"><button type="submit" name="update_client_solde" class="px-3 py-1 bg-slate-800 text-white rounded-lg text-xs">Mettre à jour</button></td>
                            </form>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Section 4 : Changement de Mot de Passe Admin -->
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200">
        <h2 class="text-xl font-bold mb-4 text-slate-900 flex items-center"><i class="fa-solid fa-key mr-2"></i> Modifier le Mot de Passe Administrateur</h2>
        <?php if ($msg_pwd): ?><p class="text-emerald-600 font-bold mb-3"><?= $msg_pwd ?></p><?php endif; ?>
        <form method="POST" class="flex gap-4 items-end">
            <div class="flex-1">
                <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Nouveau mot de passe</label>
                <input type="password" name="new_password" required class="w-full px-3 py-2 border rounded-xl">
            </div>
            <button type="submit" name="change_password" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl transition">Mettre à jour</button>
        </form>
    </div>

    <!-- Bouton pour déclencher l'impression -->
    <div class="text-center pt-4">
        <button onclick="window.print()" class="px-8 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl shadow-lg transition">
            <i class="fa-solid fa-print mr-2"></i> Imprimer la liste des inscrits
        </button>
    </div>
</div>

<!-- SECTION CONÇUE SPÉCIFIQUEMENT POUR L'IMPRESSION -->
<div id="section-impression" class="p-8">
    <h1 class="text-2xl font-bold mb-2">Liste des Clients Inscrits - Émargement et Paiements</h1>
    <p class="text-sm text-slate-500 mb-6">Document généré le <?= date('d/m/Y') ?></p>
    
    <table class="w-full text-left border-collapse border border-slate-300 text-sm">
        <thead>
            <tr class="bg-slate-100 border-b border-slate-300">
                <th class="p-2 border border-slate-300">Date Repas</th>
                <th class="p-2 border border-slate-300">Nom & Prénom</th>
                <th class="p-2 border border-slate-300">Réunion</th>
                <th class="p-2 border border-slate-300">Repas</th>
                <th class="p-2 border border-slate-300">Mode Règlement</th>
                <th class="p-2 border border-slate-300">Non Réglé en Ligne</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($inscrits_impression as $i): ?>
                <tr class="border-b border-slate-200">
                    <td class="p-2 border border-slate-300"><?= date('d/m/Y', strtotime($i['date_repas'])) ?></td>
                    <td class="p-2 border border-slate-300 font-bold"><?= htmlspecialchars($i['nom']) ?> <?= htmlspecialchars($i['prenom']) ?></td>
                    <td class="p-2 border border-slate-300 text-center"><?= $i['participation_reunion'] ?></td>
                    <td class="p-2 border border-slate-300 text-center"><?= $i['participation_repas'] ?></td>
                    <td class="p-2 border border-slate-300 uppercase text-xs font-semibold"><?= $i['reglement_sur_place_ou_en_ligne'] ?></td>
                    <td class="p-2 border border-slate-300 text-center">
                        <!-- Case à cocher pour les clients n'ayant pas réglé en ligne -->
                        <?php if ($i['reglement_sur_place_ou_en_ligne'] === 'place'): ?>
                            <input type="checkbox" class="w-4 h-4">
                        <?php else: ?>
                            <span class="text-xs text-emerald-600 font-bold">Réglé en ligne</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

</body>
</html>
