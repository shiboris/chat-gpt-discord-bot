<?php
declare(strict_types=1);

namespace ChatGpt;

use ChatGpt\Database\Database;
use Discord\Discord;
use Discord\Parts\Channel\Message;
use Discord\WebSockets\Event;
use Exception;
use GuzzleHttp\Client;

class ChatGpt extends Discord
{
    protected const MESSAGE_PREFIX = 'ai ';
    protected const API_URL = 'https://api.openai.com/v1/chat/completions';

    protected const FUNC_TYPE_CALL_API = 'call_api';
    protected const FUNC_TYPE_SET_PERSONARITY = 'set_personality';

    protected Client $_client;
    protected Database $_database;

    /**
     * @inheritDoc
     */
    public function __construct(string $discordToken, string $chatGptToken)
    {
        parent::__construct(['token' => $discordToken]);

        $this->_client = new Client([
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $chatGptToken,
            ],
        ]);

        $this->_database = new Database();

        $this->on('ready', function (): void {
            $this->on(Event::MESSAGE_CREATE, function (Message $message): void {
                if ($this->_checkMessage($message) === false) {
                    return;
                }

                $channel = $this->getChannel($message->channel_id);

                if (!$this->_database->checkExecutable()) {
                    $channel->sendMessage('ちょっとまってね');

                    return;
                }

                $resultMessage = match ($this->_selectFuncType($message)) {
                    self::FUNC_TYPE_CALL_API => $this->_callApi($message),
                    self::FUNC_TYPE_SET_PERSONARITY => $this->_setPersonality($message),
                };

                $channel->sendMessage($resultMessage);
            });
        });
    }

    /**
     * @param \Discord\Parts\Channel\Message $message
     * @return bool
     */
    protected function _checkMessage(Message $message): bool
    {
        if ($message->author->bot) {
            return false;
        }

        $messageContent = $message->content;
        if (!str_starts_with($messageContent, self::MESSAGE_PREFIX)) {
            return false;
        }

        return true;
    }

    /**
     * @param \Discord\Parts\Channel\Message $message
     * @return string|false
     */
    protected function _selectFuncType(Message $message): string|false
    {
        $messageContent = $message->content;

        if (str_starts_with($messageContent, 'ai set personality ')) {
            return self::FUNC_TYPE_SET_PERSONARITY;
        }

        return self::FUNC_TYPE_CALL_API;
    }

    /**
     * @param \Discord\Parts\Channel\Message $message
     * @return string|null
     */
    protected function _callApi(Message $message): string|false
    {
        $content = $message->content;
        $message = (string)str_replace(self::MESSAGE_PREFIX, '', $content);
        $messages = [];

        $conversationHistories = $this->_database->getConversationHistories();
        if (!empty($conversationHistories)) {
            foreach ($conversationHistories as $conversationHistory) {
                $messages[] = [
                    'role' => $conversationHistory->role,
                    'content' => $conversationHistory->content,
                ];
            }
        }

        $messages[] = [
            'role' => 'user',
            'content' => $message,
        ];

        $personality = $this->_database->getPersonality();
        if (!empty($personality)) {
            $messages[] = [
                'role' => 'system',
                'content' => $personality,
            ];
        }

        try {
            $response = $this->_client->post(self::API_URL, [
                'json' => [
                    'model' => 'gpt-3.5-turbo',
                    'messages' => $messages,
                    'max_tokens' => 500,
                ],
            ]);
        } catch (Exception) {
            return 'サーバーエラー';
        }

        $result = json_decode($response->getBody()->getContents(), true);
        $resultMessage = $result['choices'][0]['message']['content'];

        $conversation = [
            [
                'role' => 'user',
                'content' => $message,
            ],
            [
                'role' => 'assistant',
                'content' => $resultMessage,
            ],
        ];

        $this->_database->saveConversationHistories($conversation);

        return (string)$resultMessage;
    }

    /**
     * @param \Discord\Parts\Channel\Message $message
     * @return string
     */
    protected function _setPersonality(Message $message): string
    {
        $content = $message->content;
        $personality = (string)str_replace('ai set personality ', '', $content);
        $this->_database->setPersonality($personality);
        $this->_personality = $personality;

        return '設定完了しました。';
    }
}
