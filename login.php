<?php
require_once __DIR__ . '/config/config.php';

if (is_logged_in()) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Merci de renseigner votre email et votre mot de passe.';
    } else {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_nom'] = $user['nom'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            header('Location: ' . BASE_URL . '/' . ($user['role'] === 'admin' ? 'admin/dashboard.php' : 'client/dashboard.php'));
            exit;
        } else {
            $error = 'Email ou mot de passe incorrect.';
        }
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Connexion - Ajout Marque/Modèle</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="login-wrap">
    <div class="login-box">
        <span class="brand-mark"></span>
        <h1>Connexion</h1>
        <?php if ($error): ?>
            <div class="flash flash-error"><?= e($error) ?></div>
        <?php endif; ?>
        <form method="post" novalidate>
            <?= csrf_field() ?>
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>">
            <label for="password">Mot de passe</label>
            <input type="password" id="password" name="password" required>
            <div style="margin-top:20px;">
                <button type="submit" class="btn" style="width:100%;">Se connecter</button>
            </div>
        </form>
        <p class="muted" style="margin-top:18px;text-align:center;">Les comptes sont créés par l'administrateur.</p>
    </div>
</div>
</body>
</html>
