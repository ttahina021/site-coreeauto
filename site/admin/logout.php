<?php
require __DIR__ . '/../includes/bootstrap.php';

unset($_SESSION['admin_id'], $_SESSION['admin_nom']);
session_regenerate_id(true);
redirect('admin/login.php');
