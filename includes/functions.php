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

// Envoi d'email simple (nécessite un serveur mail configuré, ex: sendmail/SMTP dans php.ini)
function send_email($to, $subject, $bodyHtml)
{
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= 'From: ' . MAIL_FROM_NAME . ' <' . MAIL_FROM . ">\r\n";

    return @mail($to, $subject, $bodyHtml, $headers);
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
