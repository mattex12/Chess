<?php
include("database.php");
// On inclue le fichier de configuration et de connexion à la base de données
$dbh = new DataBase();

// On récupère dans $_GET l email soumis par l'utilisateur
if(!empty($_GET["login"])) {
	$login= $_GET["login"];

	// On prépare la requete qui recherche la présence de l'email dans la table tblreaders
	$sql ="SELECT id FROM player WHERE login = :login";
	$query = $dbh -> prepare($sql);
	$query->bindParam(':login', $login, PDO::PARAM_STR);
	$query->execute();
	$result = $query -> fetch(PDO::FETCH_OBJ);
	
	if(!empty($result)) {
		echo "<span style='color:red'>Ce login existe deja.</span>";
		echo "<script>$('#submit').prop('disabled',true);</script>";
	} else{
		echo "<span style='color:green'>Ce login est disponible</span>";
		echo "<script>$('#submit').prop('disabled',false);</script>";
	}
}
?>
