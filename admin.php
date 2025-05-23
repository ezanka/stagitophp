<?php
    session_start();
    include '_conf.php';
    $_POST['refresh'] = 1;

    $connexion = mysqli_connect($hostname, $username, $DBBpassword, $database);
    if (!$connexion) {
        die('Erreur : Échec de la connexion à la base de données : ' . mysqli_connect_error());
    }

    if (!isset($_SESSION['identifiant'])) {
        header('Location: signin.php');
        exit();
    }

    if (isset($_POST['logout'])) {
        session_destroy();
        header('Location: signin.php');
        exit();
    }

    if ($_SESSION['libellé_role'] !== "Administrateur") {
        session_destroy();
        header('Location: signin.php');
        exit();
    }

    if (isset($_POST['libelle_roles_users'])) {
        $_SESSION['role_user'] = $_POST['libelle_roles_users'];
    }

    if (isset($_POST['libelle_classes'])) {
        $_SESSION['classe'] = $_POST['libelle_classes'];
    }

    $id_classe = $_SESSION['id_classe_fk'] ?? null;
    $identifiant = $_SESSION['identifiant'];

    $id_classe = mysqli_real_escape_string($connexion, $id_classe);

    if (isset($_POST['refresh'])) {
        if (isset($_SESSION['classe'])) {
            $libelle_classe = mysqli_real_escape_string($connexion, $_SESSION['classe']);
        } else {
            $libelle_classe = mysqli_real_escape_string($connexion, $_SESSION['libellé_classes'][0]);
        }

        $requete_id_classe = "SELECT id FROM stagito_classes WHERE libellé_classe = '$libelle_classe'";
        $result = mysqli_query($connexion, $requete_id_classe);
        $row = mysqli_fetch_assoc($result);

        if ($row) {
            $_SESSION['id_classe_fk'] = $row['id'];
        } else {
            echo "Aucune classe trouvée.";
            die();
        }

        $id_classe_fk = $_SESSION['id_classe_fk'];
        $requete = "SELECT GROUP_CONCAT(DISTINCT u.id ORDER BY u.id SEPARATOR ', ') AS id,
                        GROUP_CONCAT(u.nom ORDER BY u.id SEPARATOR ', ') AS nom,
                        GROUP_CONCAT(u.prenom ORDER BY u.id SEPARATOR ', ') AS prenom,
                        GROUP_CONCAT(u.identifiant ORDER BY u.id SEPARATOR ', ') AS identifiant
                    FROM stagito_utilisateurs u 
                    JOIN stagito_appartenir_classes ac ON ac.id_utilisateur_fk = u.id 
                    JOIN stagito_classes c ON c.id = ac.id_classe_fk 
                    WHERE c.id = '$id_classe_fk' AND u.id_role_fk != 2 AND u.id_role_fk != 3";

        $resultat = mysqli_query($connexion, $requete);
        $utilisateur = mysqli_fetch_assoc($resultat);

        $_SESSION['eleves_nom'] = explode(', ', $utilisateur['nom'] ?? '');
        $_SESSION['eleves_prenom'] = explode(', ', $utilisateur['prenom'] ?? '');
        $_SESSION['eleves_identifiant'] = explode(', ', $utilisateur['identifiant'] ?? '');
        $_SESSION['eleves_id'] = explode(', ', $utilisateur['id'] ?? '');
    }

    if ($_SESSION['libellé_role'] === "Administrateur" && isset($_POST['choose_eleve'])) {
        $uid = $_POST['uid'];
        $uid_classe = $_SESSION['id_classe_fk'];
        $requete = "SELECT a.id_classe_fk, GROUP_CONCAT(DISTINCT cr.id ORDER BY cr.id SEPARATOR ', ') AS id,  
                        GROUP_CONCAT(DISTINCT cr.title ORDER BY cr.id SEPARATOR ', ') AS title, 
                        GROUP_CONCAT(DISTINCT cr.description ORDER BY cr.id SEPARATOR ', ') AS description,
                        GROUP_CONCAT(DISTINCT DATE_FORMAT(DATE_ADD(cr.date_creation, INTERVAL 1 HOUR), '%d/%m/%Y %H:%i') ORDER BY cr.id SEPARATOR ', ') AS date   
                        FROM stagito_utilisateurs u 
                        JOIN stagito_comptes_rendu cr ON cr.id_utilisateur_fk = u.id
                        JOIN stagito_classes c ON c.id = cr.id_classe_fk
                        JOIN stagito_appartenir_classes a ON u.id = a.id_utilisateur_fk 
                        WHERE u.id = '$uid' AND c.id = '$uid_classe'";

                    $resultat = mysqli_query($connexion, $requete);
                    $utilisateur = mysqli_fetch_assoc($resultat);

                    $_SESSION['identifiant_e'] = $utilisateur['identifiant'];
                    $_SESSION['id'] = explode(', ', $utilisateur['id'] ?? '');
                    $_SESSION['titles'] = explode(', ', $utilisateur['title'] ?? '');
                    $_SESSION['descriptions'] = explode(', ', $utilisateur['description'] ?? '');
                    $_SESSION['dates'] = explode(', ', $utilisateur['date'] ?? '');
        header("Location: admin.php?view_cr_u=true&uid=$uid&uid_classe=$uid_classe");
        exit();
    }

    if (isset($_POST['quit_widget'])) {
        header("Location: admin.php");
        exit();
    }

    if (isset($_POST['del_user'])) {
        $id_user = $_POST['id_users'];
        $del_requete1 = "DELETE FROM stagito_appartenir_classes WHERE id_utilisateur_fk = $id_user";
        mysqli_query($connexion, $del_requete1);
        $del_requete2 = "DELETE FROM stagito_comptes_rendu WHERE id_utilisateur_fk = $id_user";
        mysqli_query($connexion, $del_requete2);
        $del_requete3 = "DELETE FROM stagito_utilisateurs WHERE id = $id_user";
        mysqli_query($connexion, $del_requete3);
        
        $_POST['user-list'] = 1;
    }

    if (isset($_POST['role_user'])) {
        $id_user = $_POST['id_users'];
        $libelle_role = $_POST['libelle_role'];
        $selected_classes = $_POST['selected_classes'];
        $selected_classes = str_replace(", ", ",", $selected_classes); 

        if (isset($selected_classes)) {
            $requete_supp_ac = "DELETE FROM stagito_appartenir_classes WHERE id_utilisateur_fk = '$id_user'";
            mysqli_query($connexion, $requete_supp_ac);
            $requete_get_id_classe = "SELECT c.id FROM stagito_classes c WHERE FIND_IN_SET(c.libellé_classe, '$selected_classes');";
            $result = mysqli_query($connexion, $requete_get_id_classe);
            if ($result) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $id_classe = $row['id'];
                    $classe_requete = "INSERT INTO stagito_appartenir_classes (id_utilisateur_fk, id_classe_fk) VALUES ('$id_user', '$id_classe')";
                    mysqli_query($connexion, $classe_requete);
                }
            } 
        }

        
        $role_requete = "UPDATE stagito_utilisateurs u JOIN stagito_roles r ON r.libellé_role = '$libelle_role' SET u.id_role_fk = r.id WHERE u.id = '$id_user';";
        mysqli_query($connexion, $role_requete);
        $_POST['user-list'] = 1;
    }


?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stagito | Accueil</title>
    <link rel="stylesheet" href="global.css" data-cache-bust="true">
    <link rel="stylesheet" href="home.css" data-cache-bust="true">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>
    <header>
        <div class="title">
            <h1>Stagito.fr</h1>
        </div>
        <div class="type">
            <p><?php echo htmlspecialchars($_SESSION['nom']) . " " . htmlspecialchars($_SESSION['prenom']); ?></p>
        </div>
        <div class="leave">
            <form method="POST">
                <?php 
                if (isset($_POST['user-list'])) {
                    ?>
                        <button name="quit_widget">X</button>
                    <?php
                } else {
                    ?>
                        <button class="submit-button" type="submit" name="user-list"><i class='bx bxs-user-detail'></i></button>
                    <?php
                }
                ?>
                <button class="submit-button" type="submit" name="logout">Déconnexion</button>
            </form>
        </div>
    </header>

    <main>
        <?php
            if (isset($_POST['user-list'])) {
                $get_info = "SELECT GROUP_CONCAT(DISTINCT u.id ORDER BY u.id SEPARATOR ', ') AS id,  
                                    GROUP_CONCAT(u.nom ORDER BY u.id SEPARATOR ', ') AS nom, 
                                    GROUP_CONCAT(u.prenom ORDER BY u.id SEPARATOR ', ') AS prenom,  
                                    GROUP_CONCAT(u.identifiant ORDER BY u.id SEPARATOR ', ') AS identifiant, 
                                    GROUP_CONCAT(r.libellé_role ORDER BY u.id SEPARATOR ', ') AS role  
                                    FROM stagito_utilisateurs u 
                                    JOIN stagito_roles r ON r.id = u.id_role_fk;";
                $result = mysqli_query($connexion, $get_info);
                $utilisateurs = mysqli_fetch_assoc($result);
                $_SESSION['id_users'] = explode(', ', $utilisateurs['id'] ?? '');
                $_SESSION['nom_users'] = explode(', ', $utilisateurs['nom'] ?? '');
                $_SESSION['prenom_users'] = explode(', ', $utilisateurs['prenom'] ?? '');
                $_SESSION['identifiant_users'] = explode(', ', $utilisateurs['identifiant'] ?? '');
                $_SESSION['libelle_roles_users'] = explode(', ', $utilisateurs['role'] ?? '');

                $get_roles = "SELECT GROUP_CONCAT(DISTINCT r.libellé_role SEPARATOR ', ') AS libelle_role FROM stagito_roles r";
                $result_roles = mysqli_query($connexion, $get_roles);
                $roles = mysqli_fetch_assoc($result_roles);
                $_SESSION['libelle_role_'] = explode(', ', $roles['libelle_role'] ?? '');

                $get_classe_by_id = "SELECT u.id AS id_utilisateur, 
                                        GROUP_CONCAT(DISTINCT c.libellé_classe ORDER BY c.libellé_classe SEPARATOR ', ') AS libelle_classes
                                        FROM stagito_utilisateurs u
                                        JOIN stagito_appartenir_classes ac ON ac.id_utilisateur_fk = u.id
                                        JOIN stagito_classes c ON c.id = ac.id_classe_fk
                                        GROUP BY u.id;";

                $result_get_classe_by_id = mysqli_query($connexion, $get_classe_by_id);

                $classes_par_utilisateur = []; 
                while ($row = mysqli_fetch_assoc($result_get_classe_by_id)) {
                    $classes_par_utilisateur[$row['id_utilisateur']] = explode(', ', $row['libelle_classes']);
                }
                ?>
                    <div class="container_users">
                        <?php  
                            foreach ($_SESSION['id_users'] as $index => $id_users) {
                                $nom_users = $_SESSION['nom_users'][$index] ?? ''; 
                                $prenom_users = $_SESSION['prenom_users'][$index] ?? ''; 
                                $identifiant_users = $_SESSION['identifiant_users'][$index] ?? ''; 
                                $_SESSION['role_user'] = $_SESSION['libelle_roles_users'][$index] ?? ''; 
                                ?>
                                <div class="card_users">
                                    <div class="card_users_header">
                                        <h4> <?php echo htmlspecialchars($nom_users) , " " , htmlspecialchars($prenom_users) ?></h4>
                                        <?php 
                                            if ($_SESSION['role_user'] === "Administrateur") {
                                            ?>
                                                <select name="role" class="libelle_classes" id="role_select_<?php echo $id_users; ?>" onchange="updateRoleInput(<?php echo $id_users; ?>)">
                                                        <?php
                                                            foreach ($_SESSION['libelle_role_'] as $role) {
                                                                $selected = ($role == $_SESSION['role_user']) ? 'selected' : ''; 
                                                                echo '<option value="' . htmlspecialchars($role) . '" ' . $selected . '>' . htmlspecialchars($role) . '</option>';
                                                            }
                                                        ?>
                                                </select>
                                            <?php
                                            }
                                        ?>
                                        <div class="select_users">

                                            <?php 
                                            if ($_SESSION['role_user'] !== "Administrateur") {
                                                    ?>
                                                        <select name="role" class="libelle_classes_" id="role_select_<?php echo $id_users; ?>" onchange="updateRoleInput(<?php echo $id_users; ?>)">
                                                            <?php
                                                                foreach ($_SESSION['libelle_role_'] as $role) {
                                                                    $selected = ($role == $_SESSION['role_user']) ? 'selected' : ''; 
                                                                    echo '<option value="' . htmlspecialchars($role) . '" ' . $selected . '>' . htmlspecialchars($role) . '</option>';
                                                                }
                                                            ?>
                                                        </select>
                                                        <select name="classe[]" class="libelle_classes_" id="multiple-select-<?php echo $id_users; ?>" multiple onchange="updateSelectedClasses(<?php echo $id_users; ?>)">
                                                            <?php
                                                                $classes_utilisateur = $classes_par_utilisateur[$id_users] ?? [];

                                                                foreach ($_SESSION['libellé_classes'] as $classe) {
                                                                    $selected = in_array($classe, $classes_utilisateur) ? 'selected' : '';
                                                                    echo '<option value="' . htmlspecialchars($classe) . '" ' . $selected . '>' . htmlspecialchars($classe) . '</option>';
                                                                }
                                                            ?>
                                                        </select>
                                                    <?php
                                                }
                                            ?>
                                            <input type="hidden" id="selected-classes-<?php echo $id_users; ?>" value="" readonly>


                                        </div>
                                    </div>
                                    <form method="POST">
                                        <?php 
                                            $text_role = $_SESSION['role_user']; 
                                        ?>
                                        <input type="hidden" name="selected_classes" id="hidden-selected-classes-<?php echo $id_users; ?>">
                                        <input type="hidden" name="libelle_role" id="role_input_<?php echo $id_users; ?>" value="<?php echo htmlspecialchars($text_role) ?>">
                                        <button name="role_user"><i class='bx bx-check'></i></i></button>
                                        <input type="hidden" name="id_users" value="<?php echo htmlspecialchars($id_users) ?>">
                                        <button name="del_user"><i class='bx bx-trash'></i></button>
                                    </form>
                                </div>
                                <?php
                            }
                        ?>
                    </div>
                    <?php
            } else {
                ?>
                <div class="title">
                    <form method="POST">
                        <select name="libelle_classes" class="libelle_classes">
                            <?php
                                foreach ($_SESSION['libellé_classes'] as $class) {
                                    $selected = ($class == $_SESSION['classe']) ? 'selected' : ''; 
                                    echo '<option value="' . htmlspecialchars($class) . '" ' . $selected . '>' . htmlspecialchars($class) . '</option>';
                                }
                            ?>
                        </select>
                        <?php 
                            if (isset($_GET['view_cr_u'])) {
                                ?> <button type="submit" name="quit_widget">Actualiser</button> <?php
                            } else {
                                ?> <button type="submit" name="refresh">Actualiser</button> <?php
                            }
                        ?>
                    </form>
                    <div class="user-name">
                        <p><?php echo htmlspecialchars($_SESSION['libellé_role']); ?></p>
                        <?php
                            if (isset($_GET['view_cr_u']) && isset($_GET['uid']) && isset($_GET['uid_classe'])) {
                                ?>
                                    <p><?php echo $_SESSION['identifiant_e'] ?></p>
                                <?php
                            }
                        ?>
                    </div>
                </div>
        
                <div class="container">
                    <?php  
                        if (isset($_GET['view_cr_u']) && isset($_GET['uid']) && isset($_GET['uid_classe'])) {
                            
                            if (isset($_SESSION['titles']) && isset($_SESSION['descriptions']) && isset($_SESSION['dates'])) {
                                foreach ($_SESSION['id'] as $index => $id) {
                                    $title = $_SESSION['titles'][$index] ?? ''; 
                                    $description = $_SESSION['descriptions'][$index] ?? ''; 
                                    $date = $_SESSION['dates'][$index] ?? ''; 
                                    ?>
                                    <form method="post">
                                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($id); ?>">
                                        <input type="hidden" name="title" value="<?php echo htmlspecialchars($title); ?>">
                                        <input type="hidden" name="description" value="<?php echo htmlspecialchars($description); ?>">
                                        <input type="hidden" name="date" value="<?php echo htmlspecialchars($date); ?>">
                                        <?php if (empty($title)) {
                                            ?>
                                                    <div class="card" name="choose_cr">
                                                    <div class="card-title">
                                                        <h4>Aucun compte rendu</h4>
                                                    </div>
                                                    <?php 
                                                        if (!empty($title)) {
                                                            ?> <hr><?php
                                                        }
                                                    ?>
                                                    <p><?php echo htmlspecialchars($date); ?></p>
                                                    <p><?php echo htmlspecialchars($description); ?></p>
                                                    </div>       
                                                <?php                     
                                            } else {
                                                ?>
                                                <button class="card" name="choose_cr">
                                                    <div class="card-title">
                                                        <h4>
                                                            <?php if (empty($title)) {
                                                                    echo "Aucun compte rendu";
                                                                } else {
                                                                    echo htmlspecialchars($title);
                                                                }  
                                                            ?>
                                                        </h4>
                                                    </div>
                                                    <?php 
                                                        if (!empty($title)) {
                                                            ?> <hr><?php
                                                        }
                                                    ?>
                                                    <p><?php echo htmlspecialchars($date); ?></p>
                                                    <p><?php echo htmlspecialchars($description); ?></p>
                                                </button>
                                                <?php                           
                                            }  
                                        ?>
                                    </form>
                                    <?php
                                }
                            }
                        } else {
                            if (isset($_SESSION['eleves_identifiant'])) {
                                foreach ($_SESSION['eleves_identifiant'] as $index => $eleves_identifiant) {
                                    $unom = $_SESSION['eleves_nom'][$index] ?? ''; 
                                    $uprenom = $_SESSION['eleves_prenom'][$index] ?? ''; 
                                    $uid = $_SESSION['eleves_id'][$index] ?? ''; 
                                    ?>
                                    <form method="POST">
                                        <input type="hidden" name="uid" value="<?php echo htmlspecialchars($uid); ?>">
                                        <button class="card" type="submit" name="choose_eleve">
                                            <div class="card-title">
                                                <h4>
                                                    <?php 
                                                        echo !empty($eleves_identifiant) ? "$uid | $unom $uprenom" : "Aucun élève enregistré dans cette classe";  
                                                    ?>
                                                </h4>
                                            </div>
                                        </button>
                                    </form>
                                    <?php
                                }
                            }
                        }
                    ?>
                </div>
                <?php
            }
        ?>
        
    </main>
    <script>
        function updateRoleInput(userId) {
            let selectedRole = document.getElementById('role_select_' + userId).value;
            
            document.getElementById('role_input_' + userId).value = selectedRole;
        }
    </script>

    <script>
        function updateSelectedClasses(id_users) {
            let select = document.getElementById('multiple-select-' + id_users);
            let selectedOptions = Array.from(select.selectedOptions).map(option => option.value);
            
            document.getElementById('selected-classes-' + id_users).value = selectedOptions.join(', ');

            document.getElementById('hidden-selected-classes-' + id_users).value = selectedOptions.join(', ');
        }


    </script>


    <script>
        function bustCache() {
          const scripts = document.querySelectorAll('script[data-cache-bust="true"]');
          const links = document.querySelectorAll('link[data-cache-bust="true"]');
          
          scripts.forEach(script => {
            const src = script.getAttribute('src');
            if (src) {
              const newSrc = src.split('?')[0] + '?v=' + new Date().getTime();
              script.setAttribute('src', newSrc);
            }
          });
    
          links.forEach(link => {
            const href = link.getAttribute('href');
            if (href) {
              const newHref = href.split('?')[0] + '?v=' + new Date().getTime();
              link.setAttribute('href', newHref);
            }
          });
        }
    
        window.addEventListener('load', bustCache);
    </script>
</body>
</html>