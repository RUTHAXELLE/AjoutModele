<?php
require_once __DIR__ . '/../config/config.php';
require_role('admin');

$counts = ['En attente' => 0, 'En cours' => 0, 'Traité' => 0, 'Rejeté' => 0];
$rows = $pdo->query('SELECT statut, COUNT(*) AS nb FROM demandes GROUP BY statut')->fetchAll();
foreach ($rows as $r) {
    $counts[$r['statut']] = (int)$r['nb'];
}
$total = array_sum($counts);

$pageTitle = 'Tableau de bord admin';
require __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <h1>Tableau de bord</h1>
    <p class="muted">Vue d'ensemble des demandes reçues.</p>
</div>

<div class="stats-row">
    <div class="stat-card"><div class="num"><?= $total ?></div><div class="label">Total demandes</div></div>
    <div class="stat-card"><div class="num"><?= $counts['En attente'] ?></div><div class="label">En attente</div></div>
    <div class="stat-card"><div class="num"><?= $counts['En cours'] ?></div><div class="label">En cours</div></div>
    <div class="stat-card"><div class="num"><?= $counts['Traité'] ?></div><div class="label">Traité</div></div>
    <div class="stat-card"><div class="num"><?= $counts['Rejeté'] ?></div><div class="label">Rejeté</div></div>
</div>

<div class="grid-menu">
    <a class="menu-tile" href="<?= BASE_URL ?>/admin/demandes.php">
        <div class="icon">📋</div>
        <h3>Gérer les demandes</h3>
        <p>Prise en compte, traitement et rejet des demandes clients.</p>
    </a>
    <a class="menu-tile" href="<?= BASE_URL ?>/admin/comptes.php">
        <div class="icon">👤</div>
        <h3>Comptes clients</h3>
        <p>Créer et consulter les comptes clients.</p>
    </a>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
