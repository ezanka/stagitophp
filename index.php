<?php
    session_start();
    $_POST['refresh'] = 1;

    include '_conf.php';

    $connexion = mysqli_connect($hostname, $username, $DBBpassword, $database);

    if (!$connexion) {
        die('Erreur. Échec de la connexion à la base de données : ' . mysqli_connect_error());
    }

    if (!isset($_SESSION['identifiant'])) {
        header('Location: signin.php');
        exit();
    }

    // if (isset($_SESSION['cooldown'])) {
    //     $temps_restant = 300 - (time() - $_SESSION['cooldown']);
    //     if ($temps_restant <= 0) {
    //         session_destroy();
    //         header('Location: signin.php?error=Session expirée, Cause : inactivité sur 300 secondes');
    //         exit();
    //     }
    // }

    if (isset($_GET['action']) && $_GET['action'] === 'reset') {
        $_SESSION['cooldown'] = time();
        exit();
    }

    if (isset($_POST['logout'])) {
        session_destroy();
        header('Location: signin.php');
        exit();
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

        if (isset($_GET['day']) && isset($_GET['month']) && isset($_GET['year'])) {
            $day = $_GET['day'];
            $month = $_GET['month'];
            $year = $_GET['year'];
            $_SESSION['day'] = $_GET['day'];
            $_SESSION['month'] = $_GET['month'];
            $_SESSION['year'] = $_GET['year'];
        } else {
            $date_parts = explode("-", date("Y-m-d"));
            $day = $date_parts[2];
            $month = $date_parts[1];
            $year = $date_parts[0];
        }
        
        $today = "$year-$month-$day";

        $requete = "SELECT cr.id,
       cr.title,
       cr.description,
       DATE_FORMAT(DATE_ADD(cr.date_creation, INTERVAL 1 HOUR), '%d/%m/%Y %H:%i') AS date,
       DATE_FORMAT(DATE_ADD(cr.date_modification, INTERVAL 1 HOUR), '%d/%m/%Y %H:%i') AS date_modif
FROM stagito_utilisateurs u
JOIN stagito_comptes_rendu cr ON cr.id_utilisateur_fk = u.id
JOIN stagito_classes c ON c.id = cr.id_classe_fk
JOIN stagito_appartenir_classes a ON u.id = a.id_utilisateur_fk
WHERE u.identifiant = '$identifiant'
  AND c.id = '$id_classe_fk'
  AND DAY(cr.date_creation) = '$day'
  AND MONTH(cr.date_creation) = '$month'
  AND YEAR(cr.date_creation) = '$year'
ORDER BY cr.id
";
        $resultat = mysqli_query($connexion, $requete);

        $_SESSION['id'] = [];
        $_SESSION['titles'] = [];
        $_SESSION['descriptions'] = [];
        $_SESSION['dates'] = [];
        $_SESSION['dates_modif'] = [];

        while ($row = mysqli_fetch_assoc($resultat)) {
            $_SESSION['id'][] = $row['id'];
            $_SESSION['titles'][] = $row['title'];
            $_SESSION['descriptions'][] = $row['description'];
            $_SESSION['dates'][] = $row['date'];
            $_SESSION['dates_modif'][] = $row['date_modif'];
        }


    }

    if (isset($_POST['choose_cr'])) {
        $id = $_POST['id'];
        $title = urlencode($_POST['title']);
        $description = urlencode($_POST['description']);
        $date = $_POST['date'];

        header("Location: index.php?show_cr=true&id=$id&title=$title&description=$description&date=$date");
        exit();
    }



    if (isset($_POST['edit_cr'])) {
        $id = $_GET['id'];
        $title = urlencode($_GET['title']);
        $description = urlencode($_GET['description']);
        $date = $_GET['date'];
        // $date_modif = $_GET['date_modif'];
        header("Location: index.php?modif_cr=true&id=$id&title=$title&description=$description&date=$date");
        exit();
    }

    if (isset($_POST['quit_widget'])) {
        $day = $_SESSION['day'];
        $month = $_SESSION['month'];
        $year = $_SESSION['year'];
        
        header("Location: index.php?day=$day&month=$month&year=$year");
        exit();
    }

    if (isset($_POST['show_new_cr'])) {
        header("Location: index.php?show_new_cr=true");
        exit();
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
            <p><?php echo $_SESSION['nom'] , " " , $_SESSION['prenom'] ?></p>
        </div>
        <div class="leave">
            <form method="POST">
                <?php 
                if (isset($_POST['user-param'])) {
                    ?>
                        <button name="quit_widget">X</button>
                    <?php
                } else {
                    ?>
                        <button class="submit-button" type="submit" name="user-param"><i class='bx bx-cog'></i></button>
                    <?php
                }
                ?>
                <button class="submit-button" type="submit" name="logout">Deconnexion</button>
            </form>
        </div>
    </header>
    <main>
        <?php if (isset($_POST['user-param'])) {
                $uid = $_SESSION['uid'];
                $requete_nb_cr = "SELECT COUNT(*) AS total FROM stagito_comptes_rendu";
                $resultat = mysqli_query($connexion, $requete_nb_cr);
                
                if ($resultat) {
                    $row = mysqli_fetch_assoc($resultat); 
                } else {
                    echo "Erreur SQL : " . mysqli_error($connexion);
                }
            ?>
            <div class="container_users">
                <div class="card_param">
                    <div class="title">
                    <h2>Informations personnel</h2>
                    </div>
                    <div class="infos">
                        <div class="info-line">
                            <p>Identifiant : <?php echo $_SESSION['identifiant'] ?></p>
                        </div>
                        <div class="info-line">
                            <p>Nom : <?php echo $_SESSION['nom'] ?></p>
                        </div>
                        <div class="info-line">
                            <p>Prénom : <?php echo $_SESSION['prenom'] ?></p>
                        </div>
                        <div class="info-line">
                            <p>Nombres total de comptes rendu : <?php echo $row['total'] ?></p>
                        </div>
                    </div>
                </div>
            </div>
            <?php
        } else {
            ?>
            <div class="title">
                        <form method="POST" class="form">
                            <div class="cr-param">
                                <select name="libelle_classes" class="libelle_classes">
                                <?php
                                    foreach ($_SESSION['libellé_classes'] as $class) {
                                        $selected = ($class == $_SESSION['classe']) ? 'selected' : '';
                                        echo '<option value="' . htmlspecialchars($class) . '" ' . $selected . '>' . htmlspecialchars($class) . '</option>';
                                    }
                                ?>
                                </select>
                                <?php
                                    if (empty($_GET['day']) || empty($_GET['month']) || empty($_GET['year'])) {
                                        $today = date("Y-m-d");
                                    } else {
                                        $day = $_GET['day'];
                                        $month = $_GET['month'];
                                        $year = $_GET['year'];
                                        $today = date("$year-$month-$day");
                                    }
                                ?>
                                <input type="date" name="selectDate" class="selectDate" value="<?php echo $today ?>" min="2025-01-01" max="2025-12-21" />
                            </div>
                            <button type="submit" name="refresh" class="refresh">Actualiser</button>
                        </form>

                        <p><?php echo $_SESSION['libellé_role'] ?></p>
                    </div>
                    <div class="container">
                        <?php
                            if (isset($_SESSION['titles']) && isset($_SESSION['descriptions']) && isset($_SESSION['dates'])) {
                                foreach ($_SESSION['id'] as $index => $id) {
                                    $title = $_SESSION['titles'][$index] ?? '';
                                    $description = $_SESSION['descriptions'][$index] ?? '';
                                    $date = $_SESSION['dates'][$index] ?? '';
                                    $date_modif = $_SESSION['dates_modif'][$index] ?? '';
                                    ?>
                                    <form method="post">
                                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($id); ?>">
                                        <input type="hidden" name="title" value="<?php echo htmlspecialchars($title); ?>">
                                        <input type="hidden" name="description" value="<?php echo htmlspecialchars($description); ?>">
                                        <input type="hidden" name="date" value="<?php echo htmlspecialchars($date); ?>">
                                        <input type="hidden" name="date_modif" value="<?php echo htmlspecialchars($date_modif); ?>">
                                        <?php
                                            if (empty($title)) {
                                                ?>
                                                    <div class="card">
                                                        <div class="card-title">
                                                            <h4>Aucun compte rendu</h4>
                                                        </div>

                                                    </div>
                                                <?php
                                            } else {
                                            ?>
                                            <button class="card" name="choose_cr">
                                                <div class="card-title">
                                                    <h4>
                                                        <?php echo htmlspecialchars($title); ?>
                                                    </h4>
                                                </div>
                                                <?php
                                                    if (!empty($title)) {
                                                        ?> <hr><?php
                                                    }
                                                    if (!empty($date_modif)) {
                                                        ?>
                                                            <p><i class='bx bx-message-alt-edit'></i> <?php echo htmlspecialchars($date_modif); ?></p>
                                                        <?php
                                                    }
                                                    if (!empty($date)) {
                                                        ?>
                                                            <p><i class='bx bx-message-alt-add' ></i> <?php echo htmlspecialchars($date); ?></p>
                                                        <?php
                                                    }
                                                    $description = urldecode($description)
                                                ?>

                                                <p><?php echo htmlspecialchars($description); ?></p>
                                            </button>
                                            <?php
                                            }
                                        ?>
                                    </form>
                                    <?php


                                }
                            }
                        ?>
                    </div>
                    <?php
                        if (isset($_GET['show_cr']) && isset($_GET['id'])) {
                            ?>
                                <div class="container-cr">
                                    <div class="card-cr">
                                        <div class="card-cr-header">
                                            <h1><?php echo htmlspecialchars($_GET['title']); ?></h1>
                                            <form method="POST" class="form">
                                                <button name="edit_cr"><i class='bx bx-edit-alt'></i></button>
                                                <button name="quit_widget">X</button>
                                            </form>
                                        </div>
                                        <hr class="hr-cr">
                                        <p><?php
                                            $description = urldecode($_GET['description']);
                                            echo nl2br(htmlspecialchars($description));
                                        ?></p>

                                    </div>
                                </div>
                            <?php
                        }

                        if (isset($_GET['modif_cr'])) {
                            ?>
                                <div class="container-cr">
                                    <form class="card-cr" method="POST" action="modifCompteRendu.php">
                                        <div class="card-cr-header">
                                            <input type="text" name="title" placeholder="Titre" value="<?php echo $_GET['title'] ?>" required>
                                            <div class="form">
                                                <button name="delete_cr"><i class='bx bx-trash'></i></button>
                                                <button type="button" name="quit_widget" onclick="quitForm()">X</button>
                                            </div>
                                        </div>
                                        <hr>
                                        <select name="id_classe_modif" class="libelle_classes">
                                            <?php
                                                foreach ($_SESSION['libellé_classes'] as $class) {
                                                    $selected = ($class == $_SESSION['classe']) ? 'selected' : '';
                                                    echo '<option value="' . htmlspecialchars($class) . '" ' . $selected . '>' . htmlspecialchars($class) . '</option>';
                                                }
                                            ?>
                                        </select>
                                        <textarea name="description" id="new-cr-descr" required><?php echo htmlspecialchars($_GET['description']); ?></textarea>
                                        <input type="hidden" name="uid" value="<?php echo $_GET['id'] ?>">
                                        <input type="hidden" name="quit_form" id="quit_form" value="0">
                                        <input type="file" class="img" id="img" name="img" accept="image/png, image/jpeg" />
                                        <button type="submit" name="modif_cr_valid" class="add_new_cr">Valider</button>
                                    </form>
                                </div>
                            <?php
                        }

                        if (isset($_GET['show_new_cr'])) {

                            ?>
                                <div class="container-cr">
                                    <form class="card-cr" method="POST" action="compteRendu.php">
                                        <div class="card-cr-header">
                                            <input type="text" name="title" placeholder="Titre" required>
                                            <div class="form">
                                                <button type="button" name="quit_widget" onclick="quitForm()">X</button>
                                            </div>
                                        </div>
                                        <hr>
                                        <select name="classe" class="libelle_classes">
                                            <?php
                                                foreach ($_SESSION['libellé_classes'] as $class) {
                                                    $selected = ($class == $_SESSION['classe']) ? 'selected' : '';
                                                    echo '<option value="' . htmlspecialchars($class) . '" ' . $selected . '>' . htmlspecialchars($class) . '</option>';
                                                }
                                            ?>
                                        </select>
                                        <textarea name="description" id="new-cr-descr" required>
            Matinée :
            - ...
            - ...
            Après-midi :
            - ...
            - ...
                                        </textarea>
                                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($_SESSION['uid']); ?>">
                                        <input type="hidden" name="quit_form" id="quit_form" value="0">
                                        <input type="file" class="img" id="img" name="img" accept="image/png, image/jpeg" />
                                        <button type="submit" name="add_cr" class="add_new_cr">Valider</button>
                                    </form>
                                </div>
                            <?php
                        }
                    ?>
                    <div class="footer">
                        <form method="POST">
                            <button class="submit-button" type="submit" name="show_new_cr">+ Faire un nouveau compte rendu</button>
                        </form>
                    </div>
            <?php
        }
        ?>
    </main>
    <script>
        function quitForm() {
            document.getElementById('quit_form').value = "1";
            document.querySelector('.card-cr').submit();
        }

        if (window.location.search.includes('refresh=true')) {
            window.location.href = 'index.php';
        }

    </script>
    <script>
        if (window.location.search.includes('show_new_cr') || window.location.search.includes('show_cr') || window.location.search.includes('modif_cr')) {
            document.body.classList.add('no-scroll');
        } else {
            document.body.classList.remove('no-scroll');
        }


        function quitForm() {
            document.body.classList.remove('no-scroll');
            document.getElementById('quit_form').value = "1";
            document.querySelector('.card-cr').submit();
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
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const dateInput = document.querySelector('input[name="selectDate"]');

            dateInput.addEventListener("change", function () {
                const selectedDate = new Date(this.value);
                const day = selectedDate.getDate().toString().padStart(2, '0');
                const month = (selectedDate.getMonth() + 1).toString().padStart(2, '0');
                const year = selectedDate.getFullYear();

                const urlParams = new URLSearchParams(window.location.search);
                urlParams.set('day', day);
                urlParams.set('month', month);
                urlParams.set('year', year);

                window.location.search = urlParams.toString(); 
            });
        });
    </script>

</body>
</html>