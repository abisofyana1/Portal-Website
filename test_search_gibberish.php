<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$request = Illuminate\Http\Request::create('/api/news', 'GET', [
    'search' => 'pdfgdfgdfgdffdgdfgdfgdfgdfgfdgdgggggggggggggggggggggggggdrtertertwertwerttyjyjg',
    'unit' => 'Banking',
    'exclude_content' => 1,
    'per_page' => 30
]);

$controller = new App\Http\Controllers\NewsController();
$response = $controller->index($request);
$data = json_decode($response->getContent(), true);

echo "Total returned: " . count($data['data']) . "\n";
foreach ($data['data'] as $item) {
    echo " - " . $item['title'] . "\n";
}
