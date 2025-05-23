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
    
        $id_user = $_POST['id'];
        $title = $_POST['title'];
        $description = $_POST['description'];
        $libelle_classe = $_POST['classe'];
        $requete_id_classe = "SELECT c.id FROM stagito_classes c WHERE c.libellé_classe = '$libelle_classe'";
        $id_classe_ = mysqli_query($connexion, $requete_id_classe);
        if ($id_classe_) {
            $row = mysqli_fetch_assoc($id_classe_);
            $id_classe = $row['id']; 
        } else {
            echo "Aucune classe trouvée.";
        }
        
        $id_user = mysqli_real_escape_string($connexion, $id_user);
        $title = mysqli_real_escape_string($connexion, $title);
        $description = mysqli_real_escape_string($connexion, $description);
        $libelle_classe = mysqli_real_escape_string($connexion, $libelle_classe);
        $requete_id_classe = mysqli_real_escape_string($connexion, $requete_id_classe);
        $id_classe = mysqli_real_escape_string($connexion, $id_classe);

        $requete = "INSERT INTO stagito_comptes_rendu (id_utilisateur_fk, title, description, id_classe_fk, date_creation)
                VALUES ('$id_user', '$title', '$description', '$id_classe', current_time());";

        if (mysqli_query($connexion, $requete)) {
            header("Location: index.php");
            exit();
        }
    }
?>