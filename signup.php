<?php
include("database.php");

// On d�marre ou on r�cup�re la session courante
session_start();

if (isset($_POST['signup'])) {
    $dbh = new DataBase();
    // On r�cup�re nom saisi par le lecteur
    $fname = $_POST['login'];
    // On r�cup�re le mot de passe
    $password = md5($_POST['password']);
    // On récupère le thême
    $theme = $_POST['theme'];

    // On pr�pare la requete d'insertion en base de donn�e de toutes ces valeurs dans la table tblreaders
    $sql = "INSERT INTO player (login, password, theme) VALUES (:login, :password, :theme)";
    $query = $dbh->prepare($sql);

    $query->bindParam(':login', $fname, PDO::PARAM_STR);
    $query->bindParam(':password', $password, PDO::PARAM_STR);
    $query->bindParam(':theme', $theme, PDO::PARAM_STR);

    // On �xecute la requete
    $query->execute();
    // On r�cup�re le dernier id ins�r� en bd (fonction lastInsertId)
    $lastInsertId = $dbh->lastInsertId();

    if ($lastInsertId) {
        // Si ce dernier id existe, on affiche dans une pop-up que l'op�ration s'est bien d�roul�e, et on affiche l'identifiant lecteur (valeur de $hit[0])
        echo '<script>alert("Votre enregistrement est réussi et votre ID est "+"' . $lastInsertId . '")</script>';
        $_SESSION['login'] = $fname;
        $_SESSION['id'] = $lastInsertId;
        $_SESSION['theme'] = $theme;
        header('location:list.php');
    } else {
        // Sinon on affiche qu'il y a eu un probl�me
        echo '<script>alert("Un problème est survenu. Veuillez recommencer");</script>';
    }
}
?>

<!DOCTYPE html>
<html lang="FR">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
    <!--[if IE]>
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <![endif]-->
    <title>Gestion de librairie en ligne | Signup</title>
    <!-- BOOTSTRAP CORE STYLE  -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
    <!-- CUSTOM STYLE  -->
    <link href="assets/css/style.css" rel="stylesheet" />
    <!-- GOOGLE FONT -->
    <!-- link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css' / -->
    <script type="text/javascript">
        // Fonction de validation sans param�tre qui renvoie 
        // TRUE si les mots de passe saisis dans le formulaire sont identiques
        // FALSE sinon
        function valid() {
            let password = document.getElementById("password");
            let checkPassword = document.getElementById("check-password");
            let message = document.getElementById("message");
            let button = document.getElementById("submit");

            if (password.value === checkPassword.value) {
                message.style.color = "green";
                message.innerHTML = "Les mots de passe sont identiques";
                button.disabled = false;
                return true;
            } else {
                message.style.color = "red";
                message.innerHTML = "Les mots de passe sont differents";
                button.disabled = true;
                checkPassword.focus();
                return false;
            }
        }


        // fonction avec le login pass� en param�tre et qui v�rifie la disponibilit� du login
        // Cette fonction effectue un appel AJAX vers check_availability.php

        function checkAvailability(str) {
            let xhr = new XMLHttpRequest();
            xhr.open("GET", "check_availability.php?emailid=" + str);
            xhr.responseType = "text";
            xhr.send();

            xhr.onload = function() {
                document.getElementById('user-availability-status').innerHTML = xhr.response;
            }
            xhr.onerror = function() {
                alert("Une erreur s'est produite");
            }
        }
    </script>
</head>

<body>
    <!-- On inclue le fichier header.php qui contient le menu de navigation-->
    <?php //include('includes/header.php');
    ?>
    <h1>Online Chess</h1>
    <hr />
    <!--On affiche le titre de la page-->
    <div class="content-wrapper">
        <div class="container">
            <div class="row pad-botm">
                <div class="col-md-12">
                    <h4 class="header-line">Créer un compte</h4>
                </div>
            </div>

            <!--On affiche le formulaire de compte-->
            <!--A la suite de la zone de saisie du captcha, on ins�re l'image cr��e par captcha.php : <img src="captcha.php">  -->
            <!-- On appelle la fonction valid() dans la balise <form> onSubmit="return valid(); -->
            <!-- On appelle la fonction checkAvailability() dans la balise <input> de l'email onBlur="checkAvailability(this.value)" -->
            <div class="row">
                <div class="col-md-9 offset-md-1">
                    <div class="panel panel-danger">
                        <div class="panel-body">
                            <form name="signup" method="post" action="signup">
                                <div class="form-group">
                                    <input class="form-control" type="text" name="login" placeholder="Votre pseudo" onBlur="checkAvailability(this.value)" required />
                                    <span id="user-availability-status" style="font-size:12px;"></span>
                                </div>

                                <div class="form-group">
                                    <input class="form-control" type="password" name="password" id="password" placeholder="Votre mot de passe" required />
                                </div>

                                <div class="form-group">
                                    <input class="form-control" type="password" name="confirmpassword" id="check-password" placeholder="Confirmez votre mot de passe" onBlur="return valid()" required />
                                    <span id="message"></span>
                                </div>

                                <div class="form-group">
                                    <select class="form-control" name="theme" required>
                                        <option value="">Choisissez un thême</option>
                                        <option value="regular">Regular</option>
                                        <option value="simple">Simple</option>
                                        <option value="simple">Plain</option>
                                        <option value="fancy">Fancy</option>
                                        <option value="master">Master</option>
                                    </select>
                                </div>

                                <button type="submit" name="signup" class="btn btn-info" id="submit">Enregistrer</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- CONTENT-WRAPPER SECTION END-->
    <?php // include('includes/footer.php');
    ?>

    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>

</body>

</html>