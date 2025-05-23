<?php
    session_start();

    $identifiant = $_POST['identifiant'];
    $password = $_POST['password'];
    
    include '_conf.php';

    $connexion = mysqli_connect($hostname, $username, $DBBpassword, $database);

    if (!$connexion) {
        die('Erreur. Échec de la connexion à la base de données : ' . mysqli_connect_error());
    }

    $identifiant = mysqli_real_escape_string($connexion, $identifiant);

    $requete_user = "SELECT u.identifiant FROM stagito_utilisateurs u WHERE u.identifiant = '$identifiant'";
    $resultat_user = mysqli_query($connexion, $requete_user);
    $requete = "SELECT u.identifiant, u.id, u.nom, u.prenom, u.password, r.libellé_role, a.id_classe_fk,
                    GROUP_CONCAT(DISTINCT c.libellé_classe SEPARATOR ', ') AS libellé_classes, 
                    GROUP_CONCAT(DISTINCT c.id SEPARATOR ', ') AS id_classes, 
                    GROUP_CONCAT(DISTINCT cr.title SEPARATOR ', ') AS title, 
                    GROUP_CONCAT(DISTINCT cr.description SEPARATOR ', ') AS description
                    FROM stagito_utilisateurs u 
                    JOIN stagito_appartenir_classes a ON u.id = a.id_utilisateur_fk 
                    JOIN stagito_classes c ON a.id_classe_fk = c.id 
                    JOIN stagito_roles r ON u.id_role_fk = r.id 
                    LEFT JOIN stagito_comptes_rendu cr ON cr.id_utilisateur_fk = u.id
                    WHERE u.identifiant = '$identifiant'";
    $resultat = mysqli_query($connexion, $requete);

    if (mysqli_num_rows($resultat_user) == 1) {
        $utilisateur = mysqli_fetch_assoc($resultat);
        if (password_verify($password, $utilisateur['password'])) {
            if ($utilisateur['libellé_role'] == "Administrateur") {
                $get_all_classe = "SELECT GROUP_CONCAT(DISTINCT c.id SEPARATOR ', ') AS id_classe, 
                                            GROUP_CONCAT(DISTINCT c.libellé_classe SEPARATOR ', ') AS libelle_classe
                                            FROM stagito_classes c";

                $resultat_classe = mysqli_query($connexion, $get_all_classe);
                $classes = mysqli_fetch_assoc($resultat_classe);

                $_SESSION['uid'] = $utilisateur['id'];
                $_SESSION['identifiant'] = $utilisateur['identifiant'];
                $_SESSION['nom'] = $utilisateur['nom'];
                $_SESSION['prenom'] = $utilisateur['prenom'];
                $_SESSION['libellé_role'] = $utilisateur['libellé_role'];
                $_SESSION['id_classe_fk'] = $utilisateur['id_classe_fk'];
                $_SESSION['id_classes'] = explode(', ', $utilisateur['id_classes']);
                $_SESSION['libellé_classes'] = explode(', ', $classes['libelle_classe']);
                $_SESSION['titles'] = explode(', ', $utilisateur['title']);
                $_SESSION['descriptions'] = explode(', ', $utilisateur['description']);
                // var_dump($utilisateur);
                header('Location: admin.php');
                exit();
            } elseif ($utilisateur['libellé_role'] == "Professeur") {

                $_SESSION['uid'] = $utilisateur['id'];
                $uid = $utilisateur['id'];
                $_SESSION['identifiant'] = $utilisateur['identifiant'];
                $_SESSION['nom'] = $utilisateur['nom'];
                $_SESSION['prenom'] = $utilisateur['prenom'];
                $_SESSION['libellé_role'] = $utilisateur['libellé_role'];
                $_SESSION['id_classe_fk'] = $utilisateur['id_classe_fk'];

                $get_all_classe = "SELECT GROUP_CONCAT(DISTINCT c.id SEPARATOR ', ') AS id_classe, 
                                GROUP_CONCAT(DISTINCT c.libellé_classe SEPARATOR ', ') AS libelle_classe
                                FROM stagito_classes c
								JOIN stagito_appartenir_classes ac ON ac.id_classe_fk = c.id 
                                JOIN stagito_utilisateurs u ON u.id = ac.id_utilisateur_fk WHERE u.id = '$uid'";

                $resultat_classe = mysqli_query($connexion, $get_all_classe);
                $classes = mysqli_fetch_assoc($resultat_classe);

                $_SESSION['id_classes'] = explode(', ', $utilisateur['id_classes']);
                $_SESSION['libellé_classes'] = explode(', ', $classes['libelle_classe']);
                $_SESSION['titles'] = explode(', ', $utilisateur['title']);
                $_SESSION['descriptions'] = explode(', ', $utilisateur['description']);
                // var_dump($utilisateur);
                header('Location: prof.php');
                exit();
            } else {
                $_SESSION['uid'] = $utilisateur['id'];
                $_SESSION['identifiant'] = $utilisateur['identifiant'];
                $_SESSION['nom'] = $utilisateur['nom'];
                $_SESSION['prenom'] = $utilisateur['prenom'];
                $_SESSION['libellé_role'] = $utilisateur['libellé_role'];
                $_SESSION['id_classe_fk'] = $utilisateur['id_classe_fk']; 
                $_SESSION['libellé_classes'] = explode(', ', $utilisateur['libellé_classes']);
                $_SESSION['titles'] = explode(', ', $utilisateur['title']);
                $_SESSION['descriptions'] = explode(', ', $utilisateur['description']);
                // var_dump($utilisateur);
                header('Location: index.php');
                exit();
            }

        } else {
            $_SESSION['form_data'] = [
                'identifiant' => $identifiant,
            ];
            header('Location: signin.php?error=Nom d\'utilisateur ou mot de passe incorrect.');
            exit();
        }
    } else {
        $_SESSION['form_data'] = [
            'identifiant' => $identifiant,
        ];
        header('Location: signin.php?error=Aucun compte ne correspond.');
        exit();
    }

    mysqli_close($connexion);
?>