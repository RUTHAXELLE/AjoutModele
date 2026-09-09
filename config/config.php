<?php
// Configuration générale de l'application

require_once __DIR__ . '/../vendor/autoload.php';

// En local (XAMPP) uniquement : charge les identifiants SMTP depuis un fichier non versionné.
// En production (Render), ces variables sont définies directement dans le dashboard Render.
if (file_exists(__DIR__ . '/env.local.php')) {
    require __DIR__ . '/env.local.php';
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Chemin de base de l'application. En local (XAMPP), l'app vit dans un sous-dossier.
// En production (Render), l'app est servie à la racine du domaine : APP_BASE_URL="" dans les variables d'env.
$appBaseUrl = getenv('APP_BASE_URL');
define('BASE_URL', rtrim($appBaseUrl !== false ? $appBaseUrl : '/AJOUT-MODELE', '/'));

define('MAIL_FROM', getenv('MAIL_FROM') ?: (getenv('SMTP_USER') ?: 'noreply@ajout-modele.local'));
define('MAIL_FROM_NAME', getenv('MAIL_FROM_NAME') ?: 'Ajout Marque/Modèle');

define('SMTP_HOST', getenv('SMTP_HOST') ?: '');
define('SMTP_PORT', getenv('SMTP_PORT') ?: 587);
define('SMTP_USER', getenv('SMTP_USER') ?: '');
define('SMTP_PASS', getenv('SMTP_PASS') ?: '');
define('MAIL_CC', getenv('MAIL_CC') ?: '');

$databaseUrl = getenv('DATABASE_URL');

try {
    if ($databaseUrl) {
        // Production (Render) : base PostgreSQL, fournie via DATABASE_URL
        // Format : postgres://user:password@host:port/dbname
        $parts = parse_url($databaseUrl);
        $dsn = sprintf(
            'pgsql:host=%s;port=%s;dbname=%s;sslmode=require',
            $parts['host'],
            $parts['port'] ?? 5432,
            ltrim($parts['path'], '/')
        );
        $pdo = new PDO($dsn, $parts['user'] ?? '', $parts['pass'] ?? '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } else {
        // Développement local (XAMPP / MySQL)
        $pdo = new PDO(
            'mysql:host=localhost;dbname=ajout_modele;charset=utf8mb4',
            'root',
            '',
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    }
} catch (PDOException $e) {
    die('Erreur de connexion à la base de données : ' . $e->getMessage());
}

require_once __DIR__ . '/../includes/functions.php';
