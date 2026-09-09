<?php
require_once __DIR__ . '/../config/config.php';
require_role('client');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/client/mes_demandes.php');
    exit;
}
csrf_check();

$demandeId = (int)($_POST['demande_id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM demandes WHERE id = ? AND user_id = ?');
$stmt->execute([$demandeId, $_SESSION['user_id']]);
$demande = $stmt->fetch();

if (!$demande) {
    set_flash('error', "Demande introuvable.");
    header('Location: ' . BASE_URL . '/client/mes_demandes.php');
    exit;
}

if (!in_array($demande['statut'], ['En attente', 'En cours'], true)) {
    set_flash('error', 'Cette demande ne peut plus être relancée.');
    header('Location: ' . BASE_URL . '/client/mes_demandes.php');
    exit;
}

$update = $pdo->prepare('UPDATE demandes SET relance_count = relance_count + 1, last_relance_at = NOW() WHERE id = ?');
$update->execute([$demandeId]);

// Notifier tous les administrateurs
$admins = $pdo->query("SELECT email, nom FROM users WHERE role = 'admin'")->fetchAll();
$modele = $demande['type_demande'] === 'nouvelle_marque' ? $demande['nouvelle_marque'] : ($demande['marque_existante'] . ' - ' . $demande['nom_modele']);
$subject = 'Relance sur une demande #' . $demandeId;
$body = '<p>Le client <strong>' . e($_SESSION['user_nom']) . '</strong> (' . e($_SESSION['user_email']) . ') a relancé la demande n°' . $demandeId . '.</p>'
    . '<p>Objet : ' . e($modele) . '<br>Statut actuel : ' . e($demande['statut']) . '</p>';

foreach ($admins as $admin) {
    send_email($admin['email'], $subject, $body);
}

set_flash('success', 'Votre relance a bien été envoyée à l\'administrateur.');
header('Location: ' . BASE_URL . '/client/mes_demandes.php');
exit;
