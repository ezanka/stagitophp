<?php
    session_start();
    
    include '_conf.php';

    $connexion = mysqli_connect($hostname, $username, $DBBpassword, $database);

    if (!$connexion) {
        die('Erreur. Échec de la connexion à la base de données : ' . mysqli_connect_error());
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        if (isset($_POST['quit_form']) && $_POST['quit_form'] == "1") {
            header("Location: index.php");
            exit();
        }
    
        $uid = $_POST['uid'];
        $title = $_POST['title'];
        $description = $_POST['description'];
        $libelle_classe = $_POST['id_classe_modif'];
        $requete_get_id_classe = "SELECT c.id FROM stagito_classes c WHERE c.libellé_classe = '$libelle_classe'";
        $get_id_classe = mysqli_query($connexion, $requete_get_id_classe);
        $id_classe = mysqli_fetch_assoc($get_id_classe);
        $id_classe = $id_classe['id'];

        if (isset($_POST['delete_cr'])) {
            $del_requete = "DELETE FROM stagito_comptes_rendu WHERE id = $uid";
            $del_exec = mysqli_query($connexion, $del_requete);
        
            unset($_SESSION['id'], $_SESSION['titles'], $_SESSION['descriptions'], $_SESSION['dates']);
            ?>
                <form method="POST">
                    <button type="hidden" name="refresh"></button>
                </form>
            <?php
            header("Location: index.php");
            exit();
        }

        if (isset($_POST['modif_cr_valid'])) {
            $modif_requete = "UPDATE stagito_comptes_rendu SET title = '$title', description = '$description', id_classe_fk = '$id_classe', date_modification = current_timestamp() WHERE stagito_comptes_rendu.id = '$uid';";
            if (mysqli_query($connexion, $modif_requete)) {
            header("Location: index.php");
            exit();
            }
        }

        
        

    }
?>