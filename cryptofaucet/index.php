<?php
/**
 * Front controller. All requests rewrite through here via .htaccess.
 */
declare(strict_types=1);

require __DIR__ . '/app/Core/Application.php';

\App\Core\Application::instance()->boot(__DIR__);
\App\Core\Application::instance()->dispatch();
