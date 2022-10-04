<?php

use App\Controller\DefaultController;

echo json_encode([
    'data' => DefaultController::renderFunction('List', [$result['data'] ?? []]),
    'listUrl' => $listUrl ?? null
]);