<?php

declare(strict_types=1);

ob_start();

require_once __DIR__ . '/src/init.php';

var_dump($app->getLogo());

?>
<img src="<?= $app->getLogo(); ?>" alt="MeetLog" class="navbar-brand-image" style="max-height: 56px; width: auto;">
