<?php
require __DIR__ . '/includes/bootstrap.php';
redirect(empty($_SESSION['admin_id']) ? 'login.php' : 'dashboard.php');
