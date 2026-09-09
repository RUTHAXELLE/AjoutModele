<?php
require_once __DIR__ . '/../config/config.php';
require_role('admin');

function get_client_email_nom($pdo, $userId)
{
    $stmt = $pdo->prepare('SELECT nom, email FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    return $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $action = $_POST['action'] ?? '';
    $demandeId = (int)($_POST['demande_id'] ?? 0);

    $stmt = $pdo->prepare('SELECT * FROM demandes WHERE id = ?');
    $stmt->execute([$demandeId]);
    $demande = $stmt->fetch();

    if (!$demande) {
        set_flash('error', 'Demande introuvable.');
    } else {
        $client = get_client_email_nom($pdo, $demande['user_id']);
        $objet = $demande['type_demande'] === 'nouvelle_marque'
            ? $demande['nouvelle_marque']
            : ($demande['marque_existante'] . ' / ' . $demande['nom_modele']);

        if ($action === 'prise_en_compte' && $demande['statut'] === 'En attente') {
            $pdo->prepare("UPDATE demandes SET statut = 'En cours' WHERE id = ?")->execute([$demandeId]);
            set_flash('success', 'Demande n°' . $demandeId . ' passée en "En cours".');
        } elseif ($action === 'traiter' && in_array($demande['statut'], ['En attente', 'En cours'], true)) {
            $pdo->prepare("UPDATE demandes SET statut = 'Traité', motif_rejet = NULL WHERE id = ?")->execute([$demandeId]);
            if ($client) {
                queue_email(
                    $client['email'],
                    'Votre demande n°' . $demandeId . ' a été traitée',
                    '<p>Bonjour ' . e($client['nom']) . ',</p><p>Votre demande concernant <strong>' . e($objet) . '</strong> a été <strong>traitée</strong>.</p>'
                );
            }
            set_flash('success', 'Demande n°' . $demandeId . ' marquée comme "Traité" et email envoyé au client.');
        } elseif ($action === 'rejeter' && in_array($demande['statut'], ['En attente', 'En cours'], true)) {
            $motif = trim($_POST['motif_rejet'] ?? '');
            if ($motif === '') {
                set_flash('error', 'Le motif de rejet est obligatoire.');
            } else {
                $pdo->prepare("UPDATE demandes SET statut = 'Rejeté', motif_rejet = ? WHERE id = ?")->execute([$motif, $demandeId]);
                if ($client) {
                    queue_email(
                        $client['email'],
                        'Votre demande n°' . $demandeId . ' a été rejetée',
                        '<p>Bonjour ' . e($client['nom']) . ',</p><p>Votre demande concernant <strong>' . e($objet) . '</strong> a été <strong>rejetée</strong>.</p><p>Motif : ' . nl2br(e($motif)) . '</p>'
                    );
                }
                set_flash('success', 'Demande n°' . $demandeId . ' rejetée et email envoyé au client.');
            }
        } else {
            set_flash('error', 'Action invalide pour le statut actuel de la demande.');
        }
    }

    header('Location: ' . BASE_URL . '/admin/demandes.php');
    exit;
}

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

$pageTitle = 'Demandes reçues';
require __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <div class="section-title">
        <h1 style="margin:0;">Demandes reçues</h1>
        <div style="display:flex;gap:10px;align-items:center;">
            <form method="get">
                <select name="statut" onchange="this.form.submit()">
                    <option value="">Tous les statuts</option>
                    <?php foreach ($validStatuts as $s): ?>
                        <option value="<?= e($s) ?>" <?= $filtre === $s ? 'selected' : '' ?>><?= e($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
            <a class="btn small outline" href="<?= BASE_URL ?>/admin/export.php<?= $filtre !== '' ? '?statut=' . urlencode($filtre) : '' ?>">Exporter (Excel)</a>
        </div>
    </div>

    <?php if (!$demandes): ?>
        <p class="muted">Aucune demande pour ce filtre.</p>
    <?php else: ?>
    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>Date</th>
                <th>Client</th>
                <th>Type</th>
                <th>Marque</th>
                <th>Modèle</th>
                <th>Statut</th>
                <th>Relances</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($demandes as $d): ?>
                <tr>
                    <td><?= e(date('d/m/Y H:i', strtotime($d['created_at']))) ?></td>
                    <td><?= e($d['client_nom']) ?><br><span class="muted"><?= e($d['client_email']) ?></span></td>
                    <td><?= e(type_demande_label($d['type_demande'])) ?></td>
                    <td><?= e($d['type_demande'] === 'nouvelle_marque' ? $d['nouvelle_marque'] : $d['marque_existante']) ?></td>
                    <td><?= e($d['nom_modele']) ?></td>
                    <td>
                        <span class="badge <?= statut_badge_class($d['statut']) ?>"><?= e($d['statut']) ?></span>
                        <?php if ($d['statut'] === 'Rejeté' && $d['motif_rejet']): ?>
                            <div class="motif-box">Motif : <?= e($d['motif_rejet']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td><?= (int)$d['relance_count'] ?></td>
                    <td>
                        <div class="actions-cell">
                            <?php if ($d['statut'] === 'En attente'): ?>
                                <form method="post">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="demande_id" value="<?= (int)$d['id'] ?>">
                                    <input type="hidden" name="action" value="prise_en_compte">
                                    <button type="submit" class="btn small info">Prise en compte</button>
                                </form>
                            <?php endif; ?>

                            <?php if (in_array($d['statut'], ['En attente', 'En cours'], true)): ?>
                                <form method="post">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="demande_id" value="<?= (int)$d['id'] ?>">
                                    <input type="hidden" name="action" value="traiter">
                                    <button type="submit" class="btn small success">Traiter</button>
                                </form>
                                <button type="button" class="btn small danger" onclick="openRejectModal(<?= (int)$d['id'] ?>)">Rejeter</button>
                            <?php endif; ?>

                            <?php if (!in_array($d['statut'], ['En attente', 'En cours'], true)): ?>
                                <span class="muted">—</span>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<div class="modal-backdrop" id="rejectModal">
    <div class="modal">
        <h3>Motif du rejet</h3>
        <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="rejeter">
            <input type="hidden" name="demande_id" id="reject-demande-id" value="">
            <label for="motif_rejet">Merci de préciser le motif *</label>
            <textarea id="motif_rejet" name="motif_rejet" rows="4" required></textarea>
            <div style="margin-top:16px;display:flex;gap:10px;justify-content:flex-end;">
                <button type="button" class="btn outline" onclick="closeRejectModal()">Annuler</button>
                <button type="submit" class="btn danger">Confirmer le rejet</button>
            </div>
        </form>
    </div>
</div>

<script>
function openRejectModal(id) {
    document.getElementById('reject-demande-id').value = id;
    document.getElementById('rejectModal').classList.add('open');
}
function closeRejectModal() {
    document.getElementById('rejectModal').classList.remove('open');
}
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
