<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();
$controller = new Modules\Admin\Http\Controllers\DashboardController();
$response = $controller->index();
$data = $response->getData();
print_r($data['analyticsData'] ?? null);
