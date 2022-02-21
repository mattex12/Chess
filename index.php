<?php
// On inclue le fichier de connexion a la base de donnees
require_once("database.php");
// On d�marre ou on r�cup�re la session courante
session_start();

// On invalide le cache de session $_SESSION
if (isset($_SESSION['login'])) {
	$_SESSION = [];
}

if (isset($_POST['signup'])) {
	// On r�cup�re le nom de l'utilisateur saisi dans le formulaire
	$login = $_POST['login'];
	// On r�cup�re le mot de passe saisi par l'utilisateur et on le crypte (fonction md5)
	$password = md5($_POST['password']);
	// On construit la requete qui permet de retrouver l'utilisateur
	//  � partir de son nom et de son mot de passe depuis la table admin
	$dbh = new DataBase();

	$sql = "SELECT id, login FROM player WHERE login LIKE :login and password LIKE :password";
	$query = $dbh->prepare($sql);
	$query->bindParam(':login', $login, PDO::PARAM_STR);
	$query->bindParam(':password', $password, PDO::PARAM_STR);
	// On execute la requete
	$query->execute();
	$result = $query->fetch(PDO::FETCH_OBJ);

	if (!empty($result)) {
		// Si le resultat de recherche n'est pas vide
		// On stocke le login et l'id de l'utilisateur  $_POST['login'] dans $_SESSION
		$_SESSION['login'] = $result->login;
		$_SESSION['id'] = $result->id;
		// On redirige l'utilisateur vers le tableau de bord administration (n'existe pas encore)
		header('location:list.php');
	} else {
		// sinon le login est refus�. On le signal par une popup
		echo "<script>alert('Login refusé');</script>";
	}
}
?>
<!DOCTYPE html>
<html lang="FR">

<head>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
	<title>Online Chess</title>
	<!-- BOOTSTRAP CORE STYLE  -->
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
</head>

<body>
	<h1>Online Chess</h1>
	<hr />

	<div class="content-wrapper">
		<!--On affiche le titre de la page-->
		<div class="container">
			<div class="row pad-botm">
				<div class="col-md-12">
					<h4 class="header-line">LOGIN</h4>
				</div>
			</div>

			<!--On affiche le formulaire de login-->
			<div class="row">
				<div class="col-md-6 col-sm-6 col-xs-12 offset-md-3">
					<div class="panel panel-info">
						<div class="panel-body">
							<form role="form" method="post" action="index.php">
								<div class="form-group">
									<label>Votre login</label>
									<input class="form-control" type="text" name="login" required />
								</div>
								<div class="form-group">
									<label>Mot de passe</label>
									<input class="form-control" type="password" name="password" required />
								</div>
								<button type="submit" name="signup" class="btn btn-info">LOGIN </button>&nbsp;&nbsp;&nbsp;<a href="signup.php">Je n'ai pas de compte</a>
							</form>
						</div>
					</div>
				</div>
			</div>
			<!---LOGIN PABNEL END-->
		</div>
	</div>
	<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"></script>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
</body>

</html>