<?php
include("database.php");

session_start();

if (strlen($_SESSION['login']) == 0) {
	header('location:index.php');
} else {
	$dbh = new DataBase();

	if (isset($_GET['del'])) {
		$id = $_GET['del'];
		$sql = "DELETE from game WHERE id=:id";
		$query = $dbh->prepare($sql);
		$query->bindParam(':id', $id, PDO::PARAM_INT);
		$query->execute();

		$sql2 = "DELETE FROM gamedata WHERE game=:id";
		$query2 = $dbh->prepare($sql2);
		$query2->bindParam(':id', $id, PDO::PARAM_INT);
		$query2->execute();
	}

	$sql = "SELECT * from game";
	$query = $dbh->prepare($sql);
	$query->execute();
	$results = $query->fetchAll(PDO::FETCH_OBJ);

	$sql2 = "SELECT id, login from player";
	$query2 = $dbh->prepare($sql2);
	$query2->execute();
	$players = $query2->fetchAll(PDO::FETCH_OBJ);
	$buffer = [];

	if (is_array($players)) {
		foreach ($players as $player) {
			$buffer[$player->id] = $player->login;
		}
	}

	$gamesToPrint = [];
	if (is_array($results)) {
		foreach ($results as $result) {
			$idWhite = $result->white;
			$idBlack = $result->black;
			$gamesToPrint[$result->id] = [$buffer[$idWhite], $buffer[$idBlack]];
		}
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
	<h1>Online Chess</h1>&nbsp;&nbsp;Vous etes connecte en tant que <?php echo $_SESSION['login']; ?>
	<hr />

	<div class="content-wrapper">
		<div class="container">
			<div class="row">
				<div class="col-md-12">
					<form role="form" method="get" action="game.php">
						<div class="form-group">
							<label>Votre couleur</label>
							<select class="form-control" name="color" required>
								<option value="">Choisir une couleur</option>
								<option value="white">Blanc</option>
								<option value="black">Noir</option>
							</select>
						</div>
						<div class="form-group">
							<label>Votre adversaire</label>
							<select class="form-control" name="opponent" required>
								<option value="">Choisir un adversaire</option>
								<?php
								if (count($buffer) > 0) {
									foreach ($buffer as $id => $opponent) {
										if ($opponent !== $_SESSION['login']) { ?>
											<option value="<?php echo $id; ?>"><?php echo $opponent; ?></option>
								<?php
										}
									}
								} ?>
							</select>
						</div>
						<button type="submit" name="game" value="0" class="btn btn-info">Creer une partie</button>
					</form>
				</div>
			</div>
			<br><br><br>
			<div class="row">
				<div class="col-md-12">
					<h4 class="header-line">Parties en cours</h4>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12">
					<!-- Advanced Tables -->
					<table class="table table-striped table-bordered ">
						<thead>
							<tr>
								<th>#</th>
								<th>Blanc</th>
								<th>Noir</th>
								<th>Action</th>
							</tr>
						</thead>
						<tbody>
							<?php
							$cnt = 1;

							foreach ($gamesToPrint as $ind => $gameToPrint) { ?>
								<tr>
									<td class="center"><?php echo $cnt; ?></td>
									<td class="center"><?php echo $gameToPrint[0]; ?></td>
									<td class="center"><?php echo $gameToPrint[1]; ?></td>

									<?php if ($_SESSION['login'] == $gameToPrint[0] || $_SESSION['login'] == $gameToPrint[1]) { ?>
										<td class="center">
											<a href="game.php?white=<?php echo $gameToPrint[0]; ?>&black=<?php echo $gameToPrint[1]; ?>&game=<?php echo $ind; ?>"><button class="btn btn-info">Rejoindre</button></a>
											<a href="list.php?del=<?php echo $ind; ?>" onclick="return confirm('Etes vous sur de vouloir supprimer cette partie ?');"" ><button class=" btn btn-danger">Supprimer</button></a>
										</td>
									<?php } ?>

								</tr>
							<?php $cnt++;
							} // Fin foreach
							?>
						</tbody>
					</table>
					<!--End Advanced Tables -->
				</div>
			</div>
		</div>
	</div>

	<!-- CONTENT-WRAPPER SECTION END-->
	<?php //include('includes/footer.php');
	?>
	<!-- FOOTER SECTION END-->
	<!-- CORE JQUERY  -->
	<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"></script>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
</body>

</html>