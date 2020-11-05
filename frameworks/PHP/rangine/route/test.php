<?php

\W7\Core\Facades\Router::get('/json', [\W7\App\Controller\TestController::class, 'json']);
\W7\Core\Facades\Router::get('/db', [\W7\App\Controller\TestController::class, 'db']);
\W7\Core\Facades\Router::get('/queries/[{queries}]', [\W7\App\Controller\TestController::class, 'queries']);
\W7\Core\Facades\Router::get('/fortunes', [\W7\App\Controller\TestController::class, 'fortunes']);
\W7\Core\Facades\Router::get('/updates/[{queries}]', [\W7\App\Controller\TestController::class, 'updates']);
\W7\Core\Facades\Router::get('/plaintext', [\W7\App\Controller\TestController::class, 'plaintext']);
