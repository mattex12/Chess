<?php
require_once "class.chess.php";

session_start();

if (!isset($_SESSION['idgame']) or ($_SESSION['idgame'] == 0)) {

	if (isset($_GET['color'])) {
		if ($_GET['color'] == 'white') {
			$_SESSION['white'] = $_SESSION['id'];
			$_SESSION['black'] = $_GET['opponent'];
		} else {
			$_SESSION['white'] = $_GET['opponent'];
			$_SESSION['black'] = $_SESSION['id'];
		}
	}
	$_SESSION['idgame'] = 0;
}

if (isset($_GET['game']) && $_GET['game'] > 0) {
	$_SESSION['white'] = $_GET['white'];
	$_SESSION['black'] = $_GET['black'];
	$_SESSION['idgame'] = $_GET['game'];
}

// On instancie la classe OnlineChess
$chess = new OnlineChess();
// On recupere le login du joueur dont c'est le tour
$player = $chess->getPlayer();
$theme = $chess->getTheme($_SESSION['id']);

// On recupere le dossier des images des pi�ces par la methode setImageDir
if (is_dir("themes/")) {
	$chess->setImageDir("themes/images_" . $theme . '/');
}
?>

<html lang="fr">

<head>
	<?php if ($player != $_SESSION['login']) { ?>
		<meta http-equiv="Refresh" content="5;url=game.php">
	<?php } ?>
	<title>Online Chess \_|_/</title>
	<!-- CUSTOM STYLE  -->
	<link href="css/style.css" rel="stylesheet" />

	<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.4.3/jquery.min.js"></script>
	<!-- script type="text/javascript"	src="js/game.js"></script -->
</head>

<body>
	<h1>Online Chess</h1>
	<a href="list.php">Liste des parties</a> - Connecte en tant que <?php echo $_SESSION['login'] ?>
	<hr />

	<?php
	// Affiche un message aux utilisateurs
	echo '<p style="text-align: center;">' . $chess->message(true) . '</p>';


	// Affiche le plateau au moyen de la m�thode board
	$chess->board();


	// Affiche le formulaire de soumission du mouvement au moyen de la methode form
	$chess->form();
	?>
</body>

</html>