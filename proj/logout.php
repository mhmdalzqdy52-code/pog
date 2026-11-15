<?php
require_once __DIR__ . '/includes/auth.php';

unset($_SESSION['auth_user']);
header('Location: login.php');
exit;
