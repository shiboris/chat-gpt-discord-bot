<?php
declare(strict_types=1);

use ChatGpt\ChatGpt;
use Dotenv\Dotenv;

include __DIR__ . '/vendor/autoload.php';

if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

$chatGpt = new ChatGpt([
    'token' => $_ENV['DISCORD_BOT_TOKEN'],
    'apiToken' => $_ENV['CHATGPT_API_TOKEN'],
]);

$chatGpt->run();
