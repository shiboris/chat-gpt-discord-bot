<?php
declare(strict_types=1);

include __DIR__ . '/vendor/autoload.php';

use Discord\Discord;
use Discord\Parts\Channel\Message;
use Discord\WebSockets\Event;
use Discord\WebSockets\Intents;

$discord = new Discord([
    'token' => getenv('BOT_TOKEN'),
    'intents' => Intents::getDefaultIntents(),
//      | Intents::MESSAGE_CONTENT, // Note: MESSAGE_CONTENT is privileged, see https://dis.gd/mcfaq
]);

$discord->on('init', function (Discord $discord): void {
    echo 'Bot is ready!', PHP_EOL;

    // Listen for messages.
    $discord->on(Event::MESSAGE_CREATE, function (Message $message, Discord $discord): void {
        echo "{$message->author->username}: {$message->content}", PHP_EOL;
        // Note: MESSAGE_CONTENT intent must be enabled to get the content if the bot is not mentioned/DMed.
    });
});

$discord->run();
