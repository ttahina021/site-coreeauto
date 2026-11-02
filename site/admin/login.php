<?php
require __DIR__ . '/../includes/bootstrap.php';

if (admin_connecte()) {
    redirect('admin/index.php');
}

$erreur = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $_SESSION['tentatives'] = ($_SESSION['tentatives'] ?? 0) + 1;
    if ($_SESSION['tentatives'] > 5) {
        sleep(2); // ralentit les tentatives répétées
    }
    $stmt = db()->prepare('SELECT * FROM admins WHERE identifiant = ?');
    $stmt->execute([trim($_POST['identifiant'] ?? '')]);
    $admin = $stmt->fetch();
    if ($admin && password_verify($_POST['mot_de_passe'] ?? '', $admin['mot_de_passe'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = (int) $admin['id'];
        $_SESSION['admin_nom'] = $admin['identifiant'];
        unset($_SESSION['tentatives']);
        if (password_verify('coreeauto2026', $admin['mot_de_passe'])) {
            flash('Vous utilisez le mot de passe par défaut. Changez-le dans « Mon compte ».', 'erreur');
        }
        redirect('admin/index.php');
    }
    $erreur = 'Identifiant ou mot de passe incorrect.';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Connexion | Admin Corée Auto</title>
    <link rel="icon" href="<?= url('assets/img/favicon.png') ?>">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
    <link rel="stylesheet" href="<?= url('assets/css/admin.css') ?>">
</head>
<body class="page-connexion">
    <form method="post" class="boite-connexion formulaire">
        <img src="<?= url('assets/img/logo-header.png') ?>" alt="Corée Auto">
        <h1>Espace administrateur</h1>
        <?php if ($erreur): ?><div class="alerte alerte-erreur"><?= e($erreur) ?></div><?php endif; ?>
        <?= csrf_field() ?>
        <label>Identifiant<input type="text" name="identifiant" required autofocus autocomplete="username"></label>
        <label>Mot de passe<input type="password" name="mot_de_passe" required autocomplete="current-password"></label>
        <button type="submit" class="btn btn-rouge btn-bloc">Se connecter</button>
        <a href="<?= url('') ?>" class="lien-reinit">← Retour au site</a>
    </form>
</body>
</html>
