<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

require_post();
csrf_verify();

logout();
redirect('/index.php');
