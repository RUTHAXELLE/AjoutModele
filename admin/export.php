<?php
require_once __DIR__ . '/../config/config.php';
require_role('admin');

$filtre = $_GET['statut'] ?? '';
$validStatuts = ['En attente', 'En cours', 'Traité', 'Rejeté'];

if ($filtre !== '' && in_array($filtre, $validStatuts, true)) {
    $stmt = $pdo->prepare('SELECT d.*, u.nom AS client_nom, u.email AS client_email
        FROM demandes d JOIN users u ON u.id = d.user_id
        WHERE d.statut = ? ORDER BY d.created_at DESC');
    $stmt->execute([$filtre]);
} else {
    $stmt = $pdo->query('SELECT d.*, u.nom AS client_nom, u.email AS client_email
        FROM demandes d JOIN users u ON u.id = d.user_id
        ORDER BY d.created_at DESC');
}
$demandes = $stmt->fetchAll();

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="demandes_' . date('Y-m-d_His') . '.csv"');

$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF"); // BOM UTF-8, pour qu'Excel affiche correctement les accents

fputcsv($out, [
    'ID',
    'Date de la demande',
    'Client',
    'Email client',
    'Type de demande',
    'Marque existante',
    'Nouvelle marque',
    'Type technique',
    'Nom du modèle',
    'Genre',
    'Places',
    'Énergie',
    'Puissance',
    'PTAC',
    'Statut',
    'Motif de rejet',
    'Nombre de relances',
    'Dernière relance',
    'Dernière mise à jour',
], ';');

foreach ($demandes as $d) {
    fputcsv($out, [
        $d['id'],
        date('d/m/Y H:i', strtotime($d['created_at'])),
        $d['client_nom'],
        $d['client_email'],
        type_demande_label($d['type_demande']),
        $d['marque_existante'],
        $d['nouvelle_marque'],
        $d['type_technique'],
        $d['nom_modele'],
        $d['genre'],
        $d['places'],
        $d['energie'],
        $d['puissance'],
        $d['ptac'],
        $d['statut'],
        $d['motif_rejet'],
        $d['relance_count'],
        $d['last_relance_at'] ? date('d/m/Y H:i', strtotime($d['last_relance_at'])) : '',
        $d['updated_at'] ? date('d/m/Y H:i', strtotime($d['updated_at'])) : '',
    ], ';');
}

fclose($out);
exit;
