<?php
require_once __DIR__ . '/config/config.php';

if (is_logged_in()) {
    header('Location: ' . BASE_URL . '/' . ($_SESSION['user_role'] === 'admin' ? 'admin/dashboard.php' : 'client/dashboard.php'));
} else {
    header('Location: ' . BASE_URL . '/login.php');
}
exit;
