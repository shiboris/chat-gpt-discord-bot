<?php
declare(strict_types=1);

use ChatGpt\ChatGpt;

include __DIR__ . '/vendor/autoload.php';

$chatGpt = new ChatGpt([
    'token' => getenv('BOT_TOKEN'),
    'apiToken' => getenv('API_TOKEN'),
]);

$chatGpt->run();
