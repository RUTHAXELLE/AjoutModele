<?php
// Attend $pageTitle défini avant l'inclusion
$user = current_user();
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle ?? 'Ajout Marque/Modèle') ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="topbar">
    <div class="brand">🚗 Ajout Marque / Modèle</div>
    <?php if ($user): ?>
        <nav>
            <?php if ($user['role'] === 'client'): ?>
                <a href="<?= BASE_URL ?>/client/dashboard.php">Accueil</a>
                <a href="<?= BASE_URL ?>/client/ajouter_demande.php">Ajouter Marque/Modèle</a>
                <a href="<?= BASE_URL ?>/client/mes_demandes.php">Mes demandes</a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>/admin/dashboard.php">Tableau de bord</a>
                <a href="<?= BASE_URL ?>/admin/demandes.php">Demandes</a>
                <a href="<?= BASE_URL ?>/admin/comptes.php">Comptes clients</a>
            <?php endif; ?>
            <span class="user-info"><?= e($user['nom']) ?> · <?= e($user['role']) ?></span>
            <a href="<?= BASE_URL ?>/logout.php">Déconnexion</a>
        </nav>
    <?php endif; ?>
</div>
<div class="container">
    <?php foreach (get_flashes() as $flash): ?>
        <div class="flash flash-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
    <?php endforeach; ?>
