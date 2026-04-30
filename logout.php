<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_role']);

setFlash('success', t('logout.button'));
redirect('/3d_print_shop/index.php');