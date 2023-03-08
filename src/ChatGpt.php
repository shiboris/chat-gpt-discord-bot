<?php
declare(strict_types=1);

namespace ChatGpt;

use ChatGpt\Database\Database;
use Discord\Discord;
use Discord\Parts\Channel\Message;
use Discord\WebSockets\Event;
use Exception;
use Orhanerday\OpenAi\OpenAi;
use SMB\Pemojine\Container;
use SMB\Pemojine\Structure\Vendor\Common;

class ChatGpt extends Discord
{
    protected const FUNC_TYPE_CALL_API = 'call_api';
    protected const FUNC_TYPE_SET_PERSONARITY = 'set_personality';
    protected const FUNC_TYPE_RESET = 'reset';

    protected const FUNC_PREFIX_CALL_API = 'ai ';
    protected const FUNC_PREFIX_SET_PERSONARITY = 'ai set personality ';
    protected const FUNC_PREFIX_RESET = 'ai reset';

    protected OpenAi $_openAi;
    protected Database $_database;

    /**
     * @inheritDoc
     */
    public function __construct(string $discordToken, string $chatGptToken)
    {
        parent::__construct(['token' => $discordToken]);

        $this->_openAi = new OpenAi($chatGptToken);
        $this->_database = new Database();

        $this->on('ready', function (): void {
            $this->on(Event::MESSAGE_CREATE, function (Message $message): void {
                // ボットのメッセージはスルー
                if ($message->author->bot) {
                    return;
                }

                // メッセージ内容から機能種類を選ぶ
                // 該当の機能がなければスルー
                $funcType = $this->_selectFuncType($message);
                if ($funcType === null) {
                    return;
                }

                $channel = $this->getChannel($message->channel_id);

                // 実行可能な状態かチェック
                if (!$this->_database->checkExecutable()) {
                    $channel->sendMessage('ちょっとまってね');

                    return;
                }

                // ランダムな絵文字をつける
                $pemojine = Container::make(new Common());
                $selectedGroup = $pemojine->randomFromGroup();
                $message->react($selectedGroup->output());

                // 入力中...を通知して機能ごとの処理を実行する
                $channel->broadcastTyping()->done(
                    function () use ($channel, $funcType, $message): void {
                        $resultMessage = match ($funcType) {
                            self::FUNC_TYPE_CALL_API => $this->_callApi($message),
                            self::FUNC_TYPE_SET_PERSONARITY => $this->_setPersonality($message),
                            self::FUNC_TYPE_RESET => $this->_resetConversationHistories(),
                            default => "想定外のコマンドが指定されました : {$funcType}"
                        };

                        $channel->sendMessage($resultMessage);
                    }
                );
            });
        });
    }

    /**
     * @param \Discord\Parts\Channel\Message $message
     * @return ?string
     */
    protected function _selectFuncType(Message $message): ?string
    {
        $funcType = null;
        $content = $message->content;

        if (str_starts_with($content, self::FUNC_PREFIX_CALL_API)) {
            $funcType = self::FUNC_TYPE_CALL_API;
        }
        if (str_starts_with($content, self::FUNC_PREFIX_SET_PERSONARITY)) {
            $funcType = self::FUNC_TYPE_SET_PERSONARITY;
        }
        if (str_starts_with($content, self::FUNC_PREFIX_RESET)) {
            $funcType = self::FUNC_TYPE_RESET;
        }

        return $funcType;
    }

    /**
     * @param \Discord\Parts\Channel\Message $message
     * @return string
     */
    protected function _callApi(Message $message): string
    {
        $userMessage = ltrim($message->content, self::FUNC_PREFIX_CALL_API);
        $requestMessages = [];

        $conversationHistories = $this->_database->getConversationHistories();
        if (!empty($conversationHistories)) {
            foreach ($conversationHistories as $conversationHistory) {
                $requestMessages[] = [
                    'role' => $conversationHistory->role,
                    'content' => $conversationHistory->content,
                ];
            }
        }

        $requestMessages[] = [
            'role' => 'user',
            'content' => $userMessage,
        ];

        $personality = $this->_database->getPersonality();
        if (!empty($personality)) {
            $requestMessages[] = [
                'role' => 'system',
                'content' => $personality,
            ];
        }

        try {
            $response = $this->_openAi->chat([
                'model' => 'gpt-3.5-turbo',
                'messages' => $requestMessages,
                'max_tokens' => 500,
            ]);
        } catch (Exception $e) {
            return "サーバーエラー : {$e->getMessage()}";
        }

        $result = json_decode($response, true);
        if (!isset($result['choices'][0]['message']['content'])) {
            $result = print_r($result, true);

            return "想定外のレスポンス : {$result}";
        }

        $resultMessage = $result['choices'][0]['message']['content'];

        $this->_database->saveConversationHistories([
            [
                'role' => 'user',
                'content' => $userMessage,
            ],
            [
                'role' => 'assistant',
                'content' => $resultMessage,
            ],
        ]);

        return (string)$resultMessage;
    }

    /**
     * @param \Discord\Parts\Channel\Message $message
     * @return string
     */
    protected function _setPersonality(Message $message): string
    {
        $personality = ltrim($message->content, self::FUNC_PREFIX_SET_PERSONARITY);
        $this->_database->setPersonality($personality);
        $this->_personality = $personality;

        return '設定完了しました。';
    }

    /**
     * @param \Discord\Parts\Channel\Message $message
     * @return string
     */
    protected function _resetConversationHistories(): string
    {
        $this->_database->resetConversationHistories();

        return '文脈をリセットしました。';
    }
}
