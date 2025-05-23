<?php
session_start();

$identifiant = $_POST['identifiant'];
$password = $_POST['password'];
$nom = $_POST['nom'];
$prenom = $_POST['prenom'];
$classe = $_POST['classe']; 

include '_conf.php';

$connexion = mysqli_connect($hostname, $username, $DBBpassword, $database);

if (!$connexion) {
    die('Erreur. Échec de la connexion à la base de données : ' . mysqli_connect_error());
}

$identifiant = mysqli_real_escape_string($connexion, $identifiant);
$password = mysqli_real_escape_string($connexion, $password);
$password_hashed = password_hash($password, PASSWORD_DEFAULT);
$nom = mysqli_real_escape_string($connexion, $nom);
$prenom = mysqli_real_escape_string($connexion, $prenom);
$classe = mysqli_real_escape_string($connexion, $classe);

$verif_User_exist = "SELECT * FROM stagito_utilisateurs WHERE identifiant = '$identifiant'";
$result = mysqli_query($connexion, $verif_User_exist);

if (mysqli_num_rows($result) <= 0) {    
    $requete = "INSERT INTO stagito_utilisateurs (id, nom, prenom, identifiant, password) 
                VALUES (NULL, '$nom', '$prenom', '$identifiant', '$password_hashed')";

    if (mysqli_query($connexion, $requete)) {
        $utilisateur_id = mysqli_insert_id($connexion);

        $requete_classe = "INSERT INTO stagito_appartenir_classes (id_utilisateur_fk, id_classe_fk) 
                           VALUES ('$utilisateur_id', '$classe')";

        if (mysqli_query($connexion, $requete_classe)) {
            header('Location: index.php');
            exit();
        } else {
            $_SESSION['form_data'] = [
                'identifiant' => $identifiant,
                'nom' => $nom,
                'prenom' => $prenom,
                'classe' => $classe,
            ];
            echo "<br>Erreur lors de l'affectation à la classe : " . mysqli_error($connexion) . "<br>";
            header('Location: signup.php?error=' . urlencode("Erreur lors de l'affectation à la classe : " . mysqli_error($connexion)));
            exit();
        }
    } else {
        $_SESSION['form_data'] = [
            'identifiant' => $identifiant,
            'nom' => $nom,
            'prenom' => $prenom,
            'classe' => $classe,
        ];
        echo "<br>Erreur lors de l'inscription : " . mysqli_error($connexion) . "<br>";
        header('Location: signup.php?error=' . urlencode("Erreur lors de l'inscription : " . mysqli_error($connexion)));
        exit();
    }
} else {
    $_SESSION['form_data'] = [
        'identifiant' => $identifiant,
        'nom' => $nom,
        'prenom' => $prenom,
        'classe' => $classe,
    ];
    header('Location: signup.php?error=Un utilisateur ayant le même login est déjà inscrit.');
    exit();
}

mysqli_close($connexion);
?>
