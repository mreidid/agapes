<?php
require_once 'db.php';

// Récupération des repas futurs ou du dernier repas actif[cite: 1]
$stmt = $pdo->query("SELECT * FROM repas WHERE repas_date >= CURDATE() ORDER BY repas_date ASC LIMIT 1");
$repas = $stmt->fetch();

// Traitement de l'inscription via POST
$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['inscrire'])) {
    $nom = trim($_POST['nom']);
    $prenom = trim($_POST['prenom']);
    $repas_date = $_POST['date_repas'];
    $p_repas = isset($_POST['participation_repas']) ? 'oui' : 'non';
    $p_reunion = isset($_POST['participation_reunion']) ? 'oui' : 'non';
    $p_autre = isset($_POST['participation_autre']) ? 'oui' : 'non';
    $commentaire = trim($_POST['commentaire']);
    $mode_reglement = $_POST['reglement'] ?? 'place';
    
    // Calcul du montant dû
    $tarif = $_POST['tarif_unitaire'];
    $montant_du = ($p_repas === 'oui') ? $tarif : 0.00;
    $montant_regle = ($mode_reglement === 'ligne') ? $montant_du : 0.00;

    $sql = "INSERT INTO inscription_repas 
            (date_repas, nom, prenom, participation_repas, participation_reunion, participation_autre, commentaire, reglement_sur_place_ou_en_ligne, montant_du, montant_regle, date_inscription)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt = $pdo->prepare($sql);
    if ($stmt->execute([$repas_date, $nom, $prenom, $p_repas, $p_reunion, $p_autre, $commentaire, $mode_reglement, $montant_du, $montant_regle])) {
        
        // Mise à jour ou création dans la table liste_client[cite: 1]
        $stmt_client = $pdo->prepare("
            INSERT INTO liste_client (nom, prenom, Total_montant_du, Total_montant_regle)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                Total_montant_du = Total_montant_du + VALUES(Total_montant_du),
                Total_montant_regle = Total_montant_regle + VALUES(Total_montant_regle)
        ");
        $stmt_client->execute([$nom, $prenom, $montant_du, $montant_regle]);

        // Envoi d'un e-mail d'alerte à l'administrateur[cite: 1]
        $to = "sreidid@free.fr";
        $subject = "Nouvelle inscription : $nom $prenom";
        $body = "Nouvelle inscription enregistrée :\n\nClient: $nom $prenom\nDate repas: $repas_date\nParticipation Repas: $p_repas\nParticipation Réunion: $p_reunion\nMontant dû: $montant_du €\nRèglement: $mode_reglement";
        $headers = "From: no-reply@" . $_SERVER['HTTP_HOST'];
        @mail($to, $subject, $body, $headers);

        $message = "Inscription validée avec succès !";
    }
}

// Récupération de la liste des inscrits pour le repas courant[cite: 1]
$inscrits = [];
if ($repas) {
    $stmt_inscrits = $pdo->prepare("SELECT * FROM inscription_repas WHERE date_repas = ? ORDER BY nom, prenom");
    $stmt_inscrits->execute([$repas['repas_date']]);
    $inscrits = $stmt_inscrits->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription au Repas & Réunion</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-slate-50 font-sans text-slate-800 antialiased">

<div class="max-w-4xl mx-auto p-4 md:p-6">
    <header class="mb-8 text-center">
        <h1 class="text-3xl font-extrabold text-indigo-600 mb-2">Inscription à l mevenement</h1>
        <p class="text-slate-500">Réservez votre participation en quelques clics</p>
    </header>

    <?php if ($message): ?>
        <div class="mb-6 p-4 bg-emerald-100 border-l-4 border-emerald-500 text-emerald-800 rounded-r shadow-sm">
            <i class="fa-solid fa-circle-check mr-2"></i><?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <?php if ($repas): ?>
        <!-- Card Informations Repas -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 p-6 mb-8">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <span class="inline-block px-3 py-1 bg-indigo-50 text-indigo-700 text-xs font-semibold rounded-full uppercase tracking-wider mb-2">
                        <?= date('d/m/Y', strtotime($repas['repas_date'])) ?>
                    </span>
                    <h2 class="text-2xl font-bold text-slate-900"><?= htmlspecialchars($repas['Repas_intitule']) ?></h2>
                </div>
                <div class="text-right">
                    <span class="text-3xl font-extrabold text-indigo-600"><?= number_format($repas['Repas_tarif'], 2, ',', ' ') ?> €</span>
                    <p class="text-xs text-slate-400">par repas</p>
                </div>
            </div>

            <?php if (!empty($repas['Repas_ordre_du_jour'])): ?>
                <div class="mb-4">
                    <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Ordre du jour</h4>
                    <p class="text-slate-700 text-sm mt-1"><?= nl2br(htmlspecialchars($repas['Repas_ordre_du_jour'])) ?></p>
                </div>
            <?php endif; ?>

            <?php if (!empty($repas['Repas_commentaire'])): ?>
                <div class="bg-amber-50 rounded-xl p-4 text-amber-900 text-sm border border-amber-200/60">
                    <i class="fa-solid fa-triangle-exclamation mr-1 text-amber-600"></i>
                    <strong>Information :</strong> <?= htmlspecialchars($repas['Repas_commentaire']) ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Formulaire de réservation -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 p-6 mb-8">
            <h3 class="text-xl font-bold text-slate-900 mb-6 flex items-center">
                <i class="fa-solid fa-pen-to-square text-indigo-600 mr-2"></i> Formulaire d'inscription
            </h3>

            <form method="POST" action="" class="space-y-6">
                <input type="hidden" name="date_repas" value="<?= $repas['repas_date'] ?>">
                <input type="hidden" name="tarif_unitaire" value="<?= $repas['Repas_tarif'] ?>">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Nom *</label>
                        <input type="text" name="nom" required class="w-full px-4 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none transition">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Prénom *</label>
                        <input type="text" name="prenom" required class="w-full px-4 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none transition">
                    </div>
                </div>

                <!-- Participations -->
                <div class="space-y-3 pt-2">
                    <label class="block text-sm font-medium text-slate-700">Participation à :</label>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <label class="flex items-center p-3 border border-slate-200 rounded-xl cursor-pointer hover:bg-slate-50 transition">
                            <input type="checkbox" name="participation_reunion" value="oui" class="w-4 h-4 text-indigo-600 rounded">
                            <span class="ml-2 text-sm text-slate-700 font-medium">Réunion</span>
                        </label>
                        <label class="flex items-center p-3 border border-slate-200 rounded-xl cursor-pointer hover:bg-slate-50 transition">
                            <input type="checkbox" name="participation_repas" value="oui" class="w-4 h-4 text-indigo-600 rounded">
                            <span class="ml-2 text-sm text-slate-700 font-medium">Repas (<?= $repas['Repas_tarif'] ?> €)</span>
                        </label>
                        <?php if (!empty($repas['Autre_action'])): ?>
                            <label class="flex items-center p-3 border border-slate-200 rounded-xl cursor-pointer hover:bg-slate-50 transition">
                                <input type="checkbox" name="participation_autre" value="oui" class="w-4 h-4 text-indigo-600 rounded">
                                <span class="ml-2 text-sm text-slate-700 font-medium"><?= htmlspecialchars($repas['Autre_action']) ?></span>
                            </label>
                        <?php endif; ?>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Commentaire / Remarques</label>
                    <textarea name="commentaire" rows="2" class="w-full px-4 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none transition" placeholder="Régime alimentaire, précisions..."></textarea>
                </div>

                <!-- Mode de Règlement -->
                <div class="space-y-3 pt-2">
                    <label class="block text-sm font-medium text-slate-700">Mode de règlement :</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <label class="relative flex p-4 border border-slate-200 rounded-xl cursor-pointer hover:border-indigo-500 transition">
                            <input type="radio" name="reglement" value="place" checked class="mt-1 text-indigo-600 focus:ring-indigo-500" onclick="togglePaymentSection('place')">
                            <div class="ml-3">
                                <span class="block text-sm font-semibold text-slate-800">Règlement sur place</span>
                                <span class="block text-xs text-slate-500">Espèces, chèque ou Virement RIB</span>
                            </div>
                        </label>
                        <label class="relative flex p-4 border border-slate-200 rounded-xl cursor-pointer hover:border-indigo-500 transition">
                            <input type="radio" name="reglement" value="ligne" class="mt-1 text-indigo-600 focus:ring-indigo-500" onclick="togglePaymentSection('ligne')">
                            <div class="ml-3">
                                <span class="block text-sm font-semibold text-slate-800">Règlement en ligne</span>
                                <span class="block text-xs text-slate-500">Carte bancaire ou PayPal</span>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Section RIB (Affiche si Règlement sur place) -->
                <div id="section-rib" class="bg-slate-100 p-4 rounded-xl border border-slate-200 text-xs text-slate-600 space-y-1">
                    <p class="font-bold text-slate-800 mb-1"><i class="fa-solid fa-building-columns mr-1"></i> Coordonnées Bancaires pour Virement :</p>
                    <p><strong>Titulaire :</strong> Association Repas convivial</p>
                    <p><strong>IBAN :</strong> FR76 3000 4000 0112 3456 7890 189</p>
                    <p><strong>BIC :</strong> BNPAFR2PPXX</p>
                </div>

                <!-- Section PayPal (Affiche si Règlement en ligne) -->
                <div id="section-paypal" class="hidden bg-indigo-50/50 p-4 rounded-xl border border-indigo-100 text-center">
                    <p class="text-xs text-slate-600 mb-3">Cliquez ci-dessous pour régler de manière sécurisée avec PayPal ou Carte Bancaire :</p>
                    <!-- <form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top"> -->
                        <input type="hidden" name="cmd" value="_xclick">
                        <input type="hidden" name="business" value="sreidid@free.fr">
                        <input type="hidden" name="item_name" value="Inscription Repas - <?= htmlspecialchars($repas['Repas_intitule']) ?>">
                        <input type="hidden" name="amount" value="<?= $repas['Repas_tarif'] ?>">
                        <input type="hidden" name="currency_code" value="EUR">
                        <button type="button" class="px-6 py-2 bg-amber-400 hover:bg-amber-500 text-slate-900 font-bold rounded-xl shadow transition">
                            <i class="fa-brands fa-paypal mr-2"></i> Payer avec PayPal / Carte
                        </button>
                    <!-- </form> -->
                </div>

                <button type="submit" name="inscrire" class="w-full py-3.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl shadow-lg shadow-indigo-200 transition">
                    Valider mon inscription
                </button>
            </form>
        </div>

        <!-- Liste des Inscrits (Si Liste_visible = true) -->
        <?php if ($repas['Liste_visible']): ?>
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 p-6">
                <h3 class="text-xl font-bold text-slate-900 mb-4 flex items-center">
                    <i class="fa-solid fa-users text-indigo-600 mr-2"></i> Participants Inscrits (<?= count($inscrits) ?>)
                </h3>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 text-slate-400 font-medium">
                                <th class="py-3 px-2">Participant</th>
                                <th class="py-3 px-2 text-center">Réunion</th>
                                <th class="py-3 px-2 text-center">Repas</th>
                                <?php if (!empty($repas['Autre_action'])): ?>
                                    <th class="py-3 px-2 text-center"><?= htmlspecialchars($repas['Autre_action']) ?></th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            <?php foreach ($inscrits as $row): ?>
                                <tr>
                                    <td class="py-3 px-2 font-semibold"><?= htmlspecialchars($row['nom']) ?> <?= htmlspecialchars($row['prenom']) ?></td>
                                    <td class="py-3 px-2 text-center">
                                        <?= $row['participation_reunion'] === 'oui' ? '<span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-md text-xs font-bold">Oui</span>' : '<span class="text-slate-300">-</span>' ?>
                                    </td>
                                    <td class="py-3 px-2 text-center">
                                        <?= $row['participation_repas'] === 'oui' ? '<span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-md text-xs font-bold">Oui</span>' : '<span class="text-slate-300">-</span>' ?>
                                    </td>
                                    <?php if (!empty($repas['Autre_action'])): ?>
                                        <td class="py-3 px-2 text-center">
                                            <?= $row['participation_autre'] === 'oui' ? '<span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-md text-xs font-bold">Oui</span>' : '<span class="text-slate-300">-</span>' ?>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($inscrits)): ?>
                                <tr>
                                    <td colspan="4" class="py-4 text-center text-slate-400">Aucun inscrit pour le moment. Soyez le premier !</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <div class="bg-white rounded-2xl shadow-sm p-8 text-center border border-slate-200">
            <p class="text-slate-500">Aucun repas ou événement programmé actuellement.</p>
        </div>
    <?php endif; ?>
</div>

<script>
function togglePaymentSection(mode) {
    const rib = document.getElementById('section-rib');
    const paypal = document.getElementById('section-paypal');
    if (mode === 'ligne') {
        paypal.classList.remove('hidden');
        rib.classList.add('hidden');
    } else {
        paypal.classList.add('hidden');
        rib.classList.remove('hidden');
    }
}
</script>

</body>
</html>
