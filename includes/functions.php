<?php
// Fonctions utilitaires partagées

function e($str)
{
    return htmlspecialchars((string)($str ?? ''), ENT_QUOTES, 'UTF-8');
}

function is_logged_in()
{
    return isset($_SESSION['user_id']);
}

function current_user()
{
    if (!is_logged_in()) {
        return null;
    }
    return [
        'id' => $_SESSION['user_id'],
        'nom' => $_SESSION['user_nom'],
        'email' => $_SESSION['user_email'],
        'role' => $_SESSION['user_role'],
    ];
}

function require_login()
{
    if (!is_logged_in()) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

function require_role($role)
{
    require_login();
    if ($_SESSION['user_role'] !== $role) {
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }
}

function set_flash($type, $message)
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function get_flashes()
{
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}

function csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field()
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_check()
{
    if (
        $_SERVER['REQUEST_METHOD'] !== 'POST'
        || !isset($_POST['csrf_token'])
        || !isset($_SESSION['csrf_token'])
        || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        http_response_code(400);
        die('Requête invalide (jeton de sécurité manquant ou expiré). Merci de revenir en arrière et réessayer.');
    }
}

// Envoi d'email via SMTP (PHPMailer). Retourne true/false, n'interrompt jamais l'action en cours.
// $withCc : ajoute MAIL_CC en copie (notifications internes uniquement, pas les emails au client).
function send_email($to, $subject, $bodyHtml, $withCc = false)
{
    if (!SMTP_HOST || !SMTP_USER || !SMTP_PASS) {
        error_log('send_email: configuration SMTP manquante (SMTP_HOST/SMTP_USER/SMTP_PASS).');
        return false;
    }

    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->Port = (int)SMTP_PORT;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->CharSet = 'UTF-8';
        $mail->Timeout = 60; // l'envoi tourne déjà en arrière-plan (queue_email) : inutile de couper trop tôt si le réseau est lent

        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($to);
        if ($withCc && MAIL_CC) {
            $mail->addCC(MAIL_CC);
        }

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $bodyHtml;

        $mail->send();
        return true;
    } catch (\PHPMailer\PHPMailer\Exception $e) {
        error_log('send_email failed: ' . $mail->ErrorInfo);
        return false;
    }
}

// Localise le binaire CLI de PHP (utile pour lancer l'envoi d'email en tâche de fond) :
// PHP_BINARY, exécuté depuis Apache, pointe vers httpd/apache2 et non vers php.exe/php.
function php_cli_binary()
{
    if (stripos(PHP_OS, 'WIN') === 0) {
        $candidate = dirname(php_ini_loaded_file()) . '\\php.exe';
    } else {
        $candidate = '/usr/local/bin/php';
    }
    return is_file($candidate) ? $candidate : PHP_BINARY;
}

// Envoie un email dans un processus séparé, pour ne pas faire attendre l'utilisateur
// pendant l'envoi SMTP (qui peut être lent selon le réseau, jusqu'à plusieurs dizaines
// de secondes sur certains hébergeurs). Ne remonte pas d'erreur : au pire l'email
// n'est pas envoyé, mais l'action de l'utilisateur n'est jamais bloquée.
function queue_email($to, $subject, $bodyHtml, $withCc = false)
{
    $isWindows = stripos(PHP_OS, 'WIN') === 0;
    $execFn = $isWindows ? 'popen' : 'exec';

    if (!function_exists($execFn)) {
        // exec()/popen() désactivés côté serveur : on envoie quand même, tant pis pour la latence.
        error_log("queue_email: $execFn() indisponible, envoi synchrone en secours.");
        send_email($to, $subject, $bodyHtml, $withCc);
        return;
    }

    $file = tempnam(sys_get_temp_dir(), 'mail_');
    file_put_contents($file, json_encode([
        'to' => $to,
        'subject' => $subject,
        'body' => $bodyHtml,
        'withCc' => $withCc,
    ]));

    $php = escapeshellarg(php_cli_binary());
    $script = escapeshellarg(__DIR__ . '/../bin/send_mail.php');
    $arg = escapeshellarg($file);

    if ($isWindows) {
        $cmd = "start /B \"\" $php $script $arg";
        error_log('queue_email: ' . $cmd);
        pclose(popen($cmd, 'r'));
    } else {
        $cmd = "$php $script $arg > /dev/null 2>&1 &";
        error_log('queue_email: ' . $cmd);
        exec($cmd);
    }
}

// Notifie tous les comptes admin (utilisée pour nouvelle demande / relance), en tâche de fond.
function queue_notify_admins($pdo, $subject, $bodyHtml)
{
    $admins = $pdo->query("SELECT email FROM users WHERE role = 'admin'")->fetchAll();
    foreach ($admins as $admin) {
        queue_email($admin['email'], $subject, $bodyHtml, true);
    }
}

function statut_badge_class($statut)
{
    switch ($statut) {
        case 'En attente':
            return 'badge-attente';
        case 'En cours':
            return 'badge-cours';
        case 'Traité':
            return 'badge-traite';
        case 'Rejeté':
            return 'badge-rejete';
        default:
            return '';
    }
}

function type_demande_label($type)
{
    return $type === 'nouvelle_marque' ? 'Nouvelle marque' : 'Nouveau modèle';
}

const GENRE_OPTIONS = [
    'Camion',
    'Camionette',
    'Autocar-Bus',
    'Chariot elevateur',
    'Mini Car',
    'Motocyclette',
    'Remorque',
    'Semi-Remorque',
    'Tricycle',
    'Quadricycle',
    'Tracteur Routier',
    'Tracteur agricole',
    'Véhicule à usage speciale',
    'Véhicule utilitaire',
    'Voiture particuliere',
];
const ENERGIE_OPTIONS = ['Essence', 'Électrique', 'Hybride', 'Gaz oil'];
