<?php
require_once __DIR__ . '/../config/config.php';
require_role('client');

$errors = [];
$old = [
    'type_demande' => 'nouveau_modele',
    'marque_existante' => '',
    'nouvelle_marque' => '',
    'type_technique' => '',
    'nom_modele' => '',
    'genre' => '',
    'places' => '',
    'energie' => '',
    'puissance' => '',
    'ptac' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    foreach ($old as $key => $default) {
        $old[$key] = trim($_POST[$key] ?? '');
    }

    if (!in_array($old['type_demande'], ['nouvelle_marque', 'nouveau_modele'], true)) {
        $errors[] = 'Type de demande invalide.';
    }
    if ($old['type_demande'] === 'nouvelle_marque' && $old['nouvelle_marque'] === '') {
        $errors[] = 'Merci de préciser le nom de la nouvelle marque.';
    }
    if ($old['type_demande'] === 'nouveau_modele') {
        if ($old['marque_existante'] === '') {
            $errors[] = 'Merci de sélectionner la marque existante.';
        } else {
            $check = $pdo->prepare('SELECT id FROM marques WHERE nom = ?');
            $check->execute([$old['marque_existante']]);
            if (!$check->fetch()) {
                $errors[] = 'Marque inconnue. Merci de choisir une marque dans la liste proposée.';
            }
        }
    }
    if ($old['nom_modele'] === '') {
        $errors[] = 'Le nom du modèle est obligatoire.';
    }
    if ($old['places'] !== '' && !ctype_digit($old['places'])) {
        $errors[] = 'Le nombre de places doit être un nombre.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare("INSERT INTO demandes
            (user_id, type_demande, marque_existante, nouvelle_marque, type_technique, nom_modele, genre, places, energie, puissance, ptac, statut)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'En attente')");
        $stmt->execute([
            $_SESSION['user_id'],
            $old['type_demande'],
            $old['type_demande'] === 'nouveau_modele' ? $old['marque_existante'] : null,
            $old['type_demande'] === 'nouvelle_marque' ? $old['nouvelle_marque'] : null,
            $old['type_technique'] ?: null,
            $old['nom_modele'],
            $old['genre'] ?: null,
            $old['places'] !== '' ? (int)$old['places'] : null,
            $old['energie'] ?: null,
            $old['puissance'] ?: null,
            $old['ptac'] ?: null,
        ]);

        $demandeId = $pdo->lastInsertId();
        $objet = $old['type_demande'] === 'nouvelle_marque' ? $old['nouvelle_marque'] : ($old['marque_existante'] . ' / ' . $old['nom_modele']);
        notify_admins(
            $pdo,
            'Nouvelle demande reçue #' . $demandeId,
            '<p>Le client <strong>' . e($_SESSION['user_nom']) . '</strong> a soumis une nouvelle demande.</p>'
                . '<p>Type : ' . e(type_demande_label($old['type_demande'])) . '<br>Objet : ' . e($objet) . '</p>'
        );

        set_flash('success', 'Votre demande a été envoyée avec succès. Statut : En attente.');
        header('Location: ' . BASE_URL . '/client/mes_demandes.php');
        exit;
    }
}

$marques = $pdo->query('SELECT nom FROM marques ORDER BY nom')->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = 'Ajouter Marque/Modèle';
require __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <h1>Ajouter Marque/Modèle</h1>

    <?php if ($errors): ?>
        <div class="flash flash-error">
            <ul style="margin:0;padding-left:18px;">
                <?php foreach ($errors as $err): ?>
                    <li><?= e($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" novalidate>
        <?= csrf_field() ?>

        <label for="type_demande">Type de demande</label>
        <select id="type_demande" name="type_demande" onchange="toggleMarqueFields()">
            <option value="nouveau_modele" <?= $old['type_demande'] === 'nouveau_modele' ? 'selected' : '' ?>>Ajout de modele</option>
            <option value="nouvelle_marque" <?= $old['type_demande'] === 'nouvelle_marque' ? 'selected' : '' ?>>Nouvelle marque</option>
        </select>

        <div id="champ-marque-existante">
            <label for="marque_existante">Marque existante</label>
            <input type="text" id="marque_existante" name="marque_existante" list="marques-datalist" autocomplete="off"
                   value="<?= e($old['marque_existante']) ?>" placeholder="Tapez pour rechercher une marque...">
            <datalist id="marques-datalist">
                <?php foreach ($marques as $m): ?>
                    <option value="<?= e($m) ?>"></option>
                <?php endforeach; ?>
            </datalist>
        </div>

        <div id="champ-nouvelle-marque">
            <label for="nouvelle_marque">Nouvelle marque</label>
            <input type="text" id="nouvelle_marque" name="nouvelle_marque" value="<?= e($old['nouvelle_marque']) ?>" placeholder="Nom de la nouvelle marque">
        </div>

        <div class="form-row">
            <div>
                <label for="type_technique">Type technique</label>
                <input type="text" id="type_technique" name="type_technique" value="<?= e($old['type_technique']) ?>">
            </div>
            <div>
                <label for="nom_modele">Nom du modèle *</label>
                <input type="text" id="nom_modele" name="nom_modele" required value="<?= e($old['nom_modele']) ?>">
            </div>
        </div>

        <div class="form-row">
            <div>
                <label for="genre">Genre</label>
                <select id="genre" name="genre">
                    <option value="">-- Choisir --</option>
                    <?php foreach (GENRE_OPTIONS as $opt): ?>
                        <option value="<?= e($opt) ?>" <?= $old['genre'] === $opt ? 'selected' : '' ?>><?= e($opt) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="places">Places</label>
                <input type="number" id="places" name="places" min="1" value="<?= e($old['places']) ?>">
            </div>
        </div>

        <div class="form-row">
            <div>
                <label for="energie">Énergie</label>
                <select id="energie" name="energie">
                    <option value="">-- Choisir --</option>
                    <?php foreach (ENERGIE_OPTIONS as $opt): ?>
                        <option value="<?= e($opt) ?>" <?= $old['energie'] === $opt ? 'selected' : '' ?>><?= e($opt) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="puissance">Puissance <span class="hint">(CV fiscaux)</span></label>
                <input type="text" id="puissance" name="puissance" value="<?= e($old['puissance']) ?>">
            </div>
        </div>

        <label for="ptac">PTAC <span class="hint">(kg)</span></label>
        <input type="text" id="ptac" name="ptac" value="<?= e($old['ptac']) ?>">

        <div style="margin-top:22px;">
            <button type="submit" class="btn">Envoyer la demande</button>
        </div>
    </form>
</div>

<script>
function toggleMarqueFields() {
    var type = document.getElementById('type_demande').value;
    document.getElementById('champ-marque-existante').style.display = (type === 'nouveau_modele') ? 'block' : 'none';
    document.getElementById('champ-nouvelle-marque').style.display = (type === 'nouvelle_marque') ? 'block' : 'none';
}
toggleMarqueFields();
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
