<?php 
    session_start(); 

    include '_conf.php';

    $connexion = mysqli_connect($hostname, $username, $DBBpassword, $database);

    if (!$connexion) {
        die('Erreur. Échec de la connexion à la base de données : ' . mysqli_connect_error());
    }


    if(isset($_POST['confirm'])) {
        $_SESSION['login'] = $_POST['login'];
        $identifiant = $_SESSION['login'];
        $email = $_POST['email'];
        $requete_user = "SELECT u.identifiant FROM stagito_utilisateurs u WHERE u.identifiant = '$identifiant'";
        $resultat_user = mysqli_query($connexion, $requete_user);

        if (mysqli_num_rows($resultat_user) == 1) {
            $verif_code = rand(10000, 99999);

            $requete_set_code = "UPDATE stagito_utilisateurs u SET code_reset = $verif_code WHERE u.identifiant = '$identifiant'";
            mysqli_query($connexion, $requete_set_code);

            $mail = $_POST['email'];
            if(mail($mail, 'Code de reinitialisation', "Votre code est : $verif_code")){
                echo "Mail envoyé avec succès, vérifier vos spams";
            } else {
                echo "Le mail a rencontrer un probleme lors de son envoie";
            }?>
            


            <form method="post">
                <input type="text" name="verif_code">
                <button name="confirm_verif">Vérifier</button>
            </form>

            <?php
        } else {
            echo "Aucun utilisateur possède ce login ou cette email";
        }
    } elseif (isset($_POST['confirm_verif'])) {
        $identifiant = $_SESSION['login'];
        $requete_verif_code = "SELECT u.code_reset FROM stagito_utilisateurs u WHERE u.identifiant = '$identifiant'";
        $verif_code = mysqli_query($connexion, $requete_verif_code);
        $get_code = mysqli_fetch_assoc($verif_code);



        if ($get_code['code_reset'] === $_POST['verif_code']) {
            ?>
                <form method="post">
                    Nouveau mot de passe : <input name="password" type="password"><br>
                    Confirmation mot de passe : <input name="confirm_password" type="password"><br>
                    <button name="confirm_new_mdp">Valider</button>
                </form>
            <?php
        } else {
            echo "code incorrect";
            ?>
            <form method="post">
                <input type="text" name="verif_code">
                <button name="confirm_verif">Vérifier</button>
            </form>
            <?php
        }
    } elseif (isset($_POST['confirm_new_mdp'])) {
        if ($_POST['password'] == $_POST['confirm_password']) {
            $identifiant = $_SESSION['login'];
            $password = $_POST['password'];
            $password_hashed = password_hash($password, PASSWORD_DEFAULT);
            $requete_change_mdp = "UPDATE stagito_utilisateurs SET code_reset = null, password = '$password_hashed' WHERE identifiant = '$identifiant'";
            if (mysqli_query($connexion, $requete_change_mdp)) {
                header('Location: signin.php?correct=Mot de passe changé avec succès');
                exit();
            }
        } else {
            $_POST['confirm_verif'] = 1;
            echo "Mot de passe non identiques";
        }
    } else { ?>
        <form method="post">
            <p>Entrer votre mail</p>
            <input type="email" name="email" required>
            <p>Entrer votre login</p>
            <input type="text" name="login" required>
            <button name="confirm">Confirmer</button>
        </form>
    <?php }
?>

