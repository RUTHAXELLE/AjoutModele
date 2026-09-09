<?php
require_once __DIR__ . '/../config/config.php';
require_role('client');

$pageTitle = 'Accueil - Espace client';
require __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <h1>Bienvenue, <?= e($_SESSION['user_nom']) ?></h1>
    <p class="muted">Choisissez une action ci-dessous.</p>
</div>

<div class="grid-menu">
    <a class="menu-tile" href="<?= BASE_URL ?>/client/ajouter_demande.php">
        <div class="icon">➕</div>
        <h3>Ajouter Marque/Modèle</h3>
        <p>Soumettre une nouvelle demande d'ajout de marque ou de modèle.</p>
    </a>
    <a class="menu-tile" href="<?= BASE_URL ?>/client/mes_demandes.php">
        <div class="icon">📋</div>
        <h3>Consultation</h3>
        <p>Suivre le statut de vos demandes et relancer si besoin.</p>
    </a>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
