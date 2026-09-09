<?php
require_once __DIR__ . '/../config/config.php';
require_role('client');

$stmt = $pdo->prepare('SELECT * FROM demandes WHERE user_id = ? ORDER BY created_at DESC');
$stmt->execute([$_SESSION['user_id']]);
$demandes = $stmt->fetchAll();

$pageTitle = 'Mes demandes';
require __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <div class="section-title">
        <h1 style="margin:0;">Mes demandes</h1>
        <a href="<?= BASE_URL ?>/client/ajouter_demande.php" class="btn small">+ Nouvelle demande</a>
    </div>

    <?php if (!$demandes): ?>
        <p class="muted">Vous n'avez encore soumis aucune demande.</p>
    <?php else: ?>
        <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Marque</th>
                <th>Modèle</th>
                <th>Statut</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($demandes as $d): ?>
                <tr>
                    <td><?= e(date('d/m/Y H:i', strtotime($d['created_at']))) ?></td>
                    <td><?= e(type_demande_label($d['type_demande'])) ?></td>
                    <td><?= e($d['type_demande'] === 'nouvelle_marque' ? $d['nouvelle_marque'] : $d['marque_existante']) ?></td>
                    <td><?= e($d['nom_modele']) ?></td>
                    <td>
                        <span class="badge <?= statut_badge_class($d['statut']) ?>"><?= e($d['statut']) ?></span>
                        <?php if ($d['statut'] === 'Rejeté' && $d['motif_rejet']): ?>
                            <div class="motif-box">Motif : <?= e($d['motif_rejet']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (in_array($d['statut'], ['En attente', 'En cours'], true)): ?>
                            <form method="post" action="<?= BASE_URL ?>/client/relance.php" onsubmit="return confirm('Envoyer une relance pour cette demande ?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="demande_id" value="<?= (int)$d['id'] ?>">
                                <button type="submit" class="btn small outline">Relance</button>
                            </form>
                        <?php else: ?>
                            <span class="muted">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
