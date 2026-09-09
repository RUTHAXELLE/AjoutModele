<?php
require_once __DIR__ . '/../config/config.php';
require_role('admin');

$errors = [];
$old = ['nom' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $old['nom'] = trim($_POST['nom'] ?? '');
    $old['email'] = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    if ($old['nom'] === '') {
        $errors[] = 'Le nom est obligatoire.';
    }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email invalide.';
    }
    if (strlen($password) < 6) {
        $errors[] = 'Le mot de passe doit contenir au moins 6 caractères.';
    }
    if ($password !== $passwordConfirm) {
        $errors[] = 'Les mots de passe ne correspondent pas.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$old['email']]);
        if ($stmt->fetch()) {
            $errors[] = 'Un compte existe déjà avec cet email.';
        }
    }

    if (!$errors) {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("INSERT INTO users (nom, email, password, role) VALUES (?, ?, ?, 'client')");
        $stmt->execute([$old['nom'], $old['email'], $hash]);
        set_flash('success', 'Compte client créé pour ' . $old['nom'] . '.');
        header('Location: ' . BASE_URL . '/admin/comptes.php');
        exit;
    }
}

$clients = $pdo->query("SELECT * FROM users WHERE role = 'client' ORDER BY created_at DESC")->fetchAll();

$pageTitle = 'Comptes clients';
require __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <h1>Créer un compte client</h1>

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
        <div class="form-row">
            <div>
                <label for="nom">Nom complet</label>
                <input type="text" id="nom" name="nom" required value="<?= e($old['nom']) ?>">
            </div>
            <div>
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required value="<?= e($old['email']) ?>">
            </div>
        </div>
        <div class="form-row">
            <div>
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" required minlength="6">
            </div>
            <div>
                <label for="password_confirm">Confirmer le mot de passe</label>
                <input type="password" id="password_confirm" name="password_confirm" required minlength="6">
            </div>
        </div>
        <div style="margin-top:20px;">
            <button type="submit" class="btn">Créer le compte</button>
        </div>
    </form>
</div>

<div class="card">
    <h2>Comptes clients existants</h2>
    <?php if (!$clients): ?>
        <p class="muted">Aucun compte client pour l'instant.</p>
    <?php else: ?>
    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>Nom</th>
                <th>Email</th>
                <th>Créé le</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($clients as $c): ?>
                <tr>
                    <td><?= e($c['nom']) ?></td>
                    <td><?= e($c['email']) ?></td>
                    <td><?= e(date('d/m/Y H:i', strtotime($c['created_at']))) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
