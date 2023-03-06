<?php
declare(strict_types=1);

namespace ChatGpt;

use ChatGpt\Database\Database;
use Discord\Discord;
use Discord\Parts\Channel\Message;
use Discord\WebSockets\Event;
use GuzzleHttp\Client;

class ChatGpt extends Discord
{
    protected const MESSAGE_PREFIX = 'ai ';
    protected const API_URL = 'https://api.openai.com/v1/chat/completions';
    protected Client $_client;
    protected Database $_database;

    /**
     * @inheritDoc
     */
    public function __construct(array $options = [])
    {
        $apiToken = $options['apiToken'];
        unset($options['apiToken']);
        parent::__construct($options);

        $this->_client = new Client([
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $apiToken,
            ],
        ]);

        $this->_database = new Database();

        $this->on('ready', function (): void {
            $this->on(Event::MESSAGE_CREATE, function (Message $message): void {
                $messageContent = $this->_checkMessage($message);
                if ($messageContent === false) {
                    return;
                }

                if (!$this->_database->checkExecutable()) {
                    $this->getChannel($message->channel_id)->sendMessage('ちょっとまってね : ' . $messageContent);

                    return;
                }

                $resultMessage = $this->_callApi($messageContent);
                $this->getChannel($message->channel_id)->sendMessage($resultMessage);
            });
        });
    }

    /**
     * @param \Discord\Parts\Channel\Message $message
     * @return string|false
     */
    protected function _checkMessage(Message $message): string|false
    {
        if ($message->author->bot) {
            return false;
        }

        $messageContent = $message->content;
        if (!str_starts_with($messageContent, self::MESSAGE_PREFIX)) {
            return false;
        }

        return (string)str_replace(self::MESSAGE_PREFIX, '', $messageContent);
    }

    /**
     * @param string $message
     * @return string|null
     */
    protected function _callApi(string $message): string|false
    {
        $response = $this->_client->post(self::API_URL, [
            'json' => [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $message,
                    ],
                ],
                'max_tokens' => 200,
            ],
        ]);

        $result = json_decode($response->getBody()->getContents(), true);
        $resultMessage = $result['choices'][0]['message']['content'];

        return (string)$resultMessage;
    }
}
