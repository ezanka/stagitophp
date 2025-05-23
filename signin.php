<?php 
    session_start(); 
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stagito | Connexion</title>
    <link rel="stylesheet" href="global.css" data-cache-bust="true">
    <link rel="stylesheet" href="login.css" data-cache-bust="true">
</head>
<body>
    <main>
        <div class="title-box">
            <div class="title">
                <h1>Stagito.fr</h1>
            </div>
        </div>
        <div class="container">
            <div class="card">
                <div class="card-title">
                    <h3>Connexion</h3>
                </div>
                <form action="signin_verif.php" class="card-form" method="POST">
                    <input type="text" name="identifiant" placeholder="Identifiant" required value="<?php echo isset($_SESSION['form_data']['identifiant']) ? htmlspecialchars($_SESSION['form_data']['identifiant']) : ''; ?>">
                    <input type="password" name="password" placeholder="Mot de passe" value="" required>
                    <?php
                        if (isset($_GET['error'])) {
                            echo '<div class="error-message" style="color: red; margin: 10px 0 0 0; text-align: center; font-size: 14px;">' . htmlspecialchars($_GET['error']) . '</div>';
                        } elseif (isset($_GET['correct'])) {
                            echo '<div class="error-message" style="color: green; margin: 10px 0 0 0; text-align: center; font-size: 15px;">' . htmlspecialchars($_GET['correct']) . '</div>';
                        }
                    ?>
                    <div class="card-bottom-form">
                        <div class="card-bottom-left-form">
                            <button>Se connecter</button>
                        </div>
                        <div class="card-bottom-right-form">
                            <p>Pas encore inscrit ? </p>
                            <a href="signup.php">Créer un compte</a>
                        </div>
                    </div>
                    <a href="oubli.php">Mot de passe oublié?</a>
                </form>
            </div>
        </div>
    </main>
</body>
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
</html>

<?php 

    if (isset($_SESSION['form_data'])) {
        unset($_SESSION['form_data']); 
    }
?>
