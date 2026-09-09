<?php
// Exécuté en tâche de fond par queue_email() (includes/functions.php) — jamais appelé directement.

require __DIR__ . '/../config/config.php';

$payloadFile = $argv[1] ?? null;
if (!$payloadFile || !is_file($payloadFile)) {
    exit(1);
}

$data = json_decode(file_get_contents($payloadFile), true);
@unlink($payloadFile);

if (!$data || empty($data['to'])) {
    error_log('send_mail.php: payload invalide ou vide.');
    exit(1);
}

$ok = send_email($data['to'], $data['subject'], $data['body'], !empty($data['withCc']));
error_log('send_mail.php: envoi à ' . $data['to'] . ' -> ' . ($ok ? 'OK' : 'ECHEC'));
