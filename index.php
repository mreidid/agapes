

<script>
	function traitement(titre){
		document.getElementById('choix').value = titre;
		document.getElementById('registrationForm').submit();
	}
</script>

<?php
ini_set('display_errors', 'On');
error_reporting(E_ALL);

require_once 'config.php';
$tarifs = getTarifs($pdo);

// Récupérer les options supplémentaires (si elles existent)
$options = [];
try {
    $stmt = $pdo->query("SELECT * FROM options");
    $options = $stmt->fetchAll();
} catch (Exception $e) {
    // Table options n'existe pas, on continue sans
}

// Récupérer les options supplémentaires (si elles existent)
$tablerepas = [];
$stmt2 = $pdo->query("SELECT * FROM repas WHERE traite=0");
$tablerepas = $stmt2->fetchAll();
$prochainrepas = $tablerepas[0]['date'];

$error = (isset($_GET['error']) ? $_GET['error'] : 0);
$success = (isset($_GET['success']) ? $_GET['success'] : 0);

if(!empty($_POST)){
	$nom=$_POST['nom'];
	$prenom=$_POST['prenom'];
	$email=$_POST['email'];
	$tenue=$_POST['tenue'];
	$prestenue = (isset($_POST['prestenue']) ? $_POST['prestenue'] : '');
	$repas = (isset($_POST['repas']) ? $_POST['repas'] : '');
	$choix = $_POST['choix']; 
	$ligne=0;
	$presence=0;
	$libre=0;

	switch($choix){
		case 'choix2': $ligne=1; break;
		case 'choix1': $presence=1; break;
		case 'choix3': $libre=1; break;
	}
	
	$sql="INSERT INTO participants (date_repas, nom, prenom, email, date_saisie, tenue, repas, autre, paiement_en_ligne, paiement_presentiel, invite)";
	$sql.="VALUE('";
	$sql.=$tenue."', '";
	$sql.=$nom."', '";
	$sql.=$prenom."', '";
	$sql.=$email."', ";
	$sql.="NOW(), '";
	$sql.=$prestenue."', '";
	$sql.=$repas."', '";
	$sql.="', '";
	$sql.=$ligne."', '";
	$sql.=$presence."', '";
	$sql.=$libre."'";
	$sql.=")";
	//echo $sql;
	$stmt3 = $pdo->query($sql);
	
	//if($choix=='choix1' or $choix=='choix3'){
		header('Location: https://www.paypal.com/ncp/payment/LPNSUL7BZ3P2C'); 
		exit;
	//}
		
	
}


?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= SITE_NAME ?> - Inscription</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .form-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .form-header {
            text-align: center;
            margin-bottom: 30px;
            color: #2c3e50;
        }
        .option-card {
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            margin: 10px 0;
            cursor: pointer;
            transition: all 0.3s;
        }
        .option-card:hover {
            border-color: #3498db;
            background: #f8f9fa;
        }
        .option-card.selected {
            border-color: #3498db;
            background: #e3f2fd;
        }
        .price-tag {
            font-size: 1.5em;
            font-weight: bold;
            color: #2c3e50;
        }
        .stripe-button {
            background: #6772e5;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 5px;
            font-size: 1.1em;
            cursor: pointer;
            width: 100%;
        }
        .stripe-button:hover {
            background: #5469d4;
        }
    </style>
</head>
<body>
<form action=".\index.php" method="post" id="registrationForm">
<input type="hidden" id="choix" name="choix" value="">
		<div class="container">
			<div class="form-container">
					<div class="form-header">
						<h1>🍽️ Inscription aux agapes</h1>
						<?php if($prochainrepas<>''){?>
							<p class="text-muted">Agapes du <?=date('d/m/Y', strtotime($prochainrepas))?></p>
							<input type="hidden" id="tenue" name="tenue" value="<?=$prochainrepas?>">
						<?php }else{?>
							<p class="text-muted">Aucun agapes prévus prochainement. Vous pouvez verser un montant libre.</p>
							<input type="hidden" id="tenue" name="tenue" value="">
						<?php }?>
					</div>			
					<?php if ($error): ?>
						<div class="alert alert-danger">
							<?= htmlspecialchars(urldecode($error)) ?>
						</div>
					<?php endif; ?>

					<?php if ($success): ?>
						<div class="alert alert-success">
							<?= htmlspecialchars(urldecode($success)) ?>
						</div>
					<?php endif; ?>

					
						<!-- Informations personnelles -->
						<div class="mb-4">
							<h3>👤 Vos informations</h3>
							<div class="row">
								<div class="col-md-6 mb-3">
									<label for="nom" class="form-label">Nom *</label>
									<input type="text" class="form-control" id="nom" name="nom" required>
								</div>
								<div class="col-md-6 mb-3">
									<label for="prenom" class="form-label">Prénom *</label>
									<input type="text" class="form-control" id="prenom" name="prenom" required>
								</div>
							</div>
							<div class="mb-3">
								<label for="email" class="form-label">Email *</label>
								<input type="email" class="form-control" id="email" name="email" required>
							</div>
						</div>

				<?php if($prochainrepas){?>
						<div class="mb-4">
						<table>
							<tr>
								<td colspan=2>
									<h3>🍴 Votre participation</h3>
								</td>
							</tr>
							<tr>
								<td>
									<div class="form-check mb-3">
										<input class="form-check-input" type="checkbox" name="prestenue" value="prestenue" id="prestenue" data-price="<?= $tarifs[0]['prix']?>">
										<label class="form-check-label option-card" for="prestenue">
											<strong>Tenue</strong>
											<div class="text-muted">Participation à la tenue</div>
											<div class="price-tag"> &nbsp;</div>
										</label>
									</div>
								</td>
								<td>
									<div class="form-check mb-3">
										<input class="form-check-input" type="checkbox" name="repas" value="repas" id="repas" data-price="<?= $tarifs[1]['prix']?>">
										<label class="form-check-label option-card" for="repas">
											<strong>Repas</strong>
											<div class="text-muted">Participation au repas</div>
											<div class="price-tag">&nbsp;</div>
										</label>
									</div>
								</td>
							</tr>
							<tr>
								<td>
									<button type="button" class="btn btn-primary btn-lg w-100"  onclick="traitement('choix31')">
										Je souhaite m'inscrire et régler sur place
									</button>
								</td>
								<td>
									<?php $lien_paypal = "https://www.paypal.com/ncp/payment/RT8AE3J3H5PGL";?>
									<button type="button" class="btn btn-primary btn-lg w-100"  onclick="traitement('choix2')">
										Je souhaite m'inscrire et règler en ligne
									</button>			
								</td>
							</tr>
						</table>				
						
							<input type="hidden" name="montant_total" id="montant_total" value="0">
						</div>

				<?php }else{?>
					<div class="form-header">
						<h1>🍽️ Les inscriptions au prochain repas ne sont pas ouvertes</h1>
					</div>		
					<table>
						<tr>				
							<td>
								<button type="button" class="btn btn-primary btn-lg w-100" onclick="traitement('choix3')">
									Je souhaite faire un versement en ligne
								</button>			
							</td>
						</tr>
					</table>
				

				<?php }?>
			</div>
		</div>
	</form>
  
</body>
</html>
