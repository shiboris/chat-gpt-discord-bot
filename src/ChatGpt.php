<?php

declare(strict_types=1);

namespace ChatGpt;

use ChatGpt\Database\Database;
use Discord\Discord;
use Discord\Parts\Channel\Message;
use Discord\WebSockets\Event;
use Exception;
use Orhanerday\OpenAi\OpenAi;

/**
 * ChatGpt
 */
class ChatGpt extends Discord
{
    /**
     * ChatGPT API をコールして、メッセージを受け取る
     *
     * @var string
     */
    protected const CALL_API = 'call_api';

    /**
     * ChatBot の人格を設定する
     *
     * @var string
     */
    protected const SET_PERSONARITY = 'set_personality';

    /**
     * 文脈をリセットする
     *
     * @var string
     */
    protected const RESET = 'reset';

    /**
     * ChatBot の動作をスタートする
     *
     * @var string
     */
    protected const START = 'start';

    /**
     * ChatBot の動作をストップする
     *
     * @var string
     */
    protected const STOP = 'stop';

    /**
     * 絵文字リアクションを使う
     *
     * @var string
     */
    protected const USE_EMOJI = 'use_emoji';

    /**
     * 絵文字リアクション使わない
     *
     * @var string
     */
    protected const UNUSE_EMOJI = 'unuse_emoji';

    /**
     * 各機能に対応する Prefix のセット
     *
     * @var array<string, string>
     */
    protected const PREFIX_SET = [
        self::CALL_API        => 'ai ',
        self::SET_PERSONARITY => 'ai set personality ',
        self::RESET           => 'ai reset',
        self::START           => 'ai start',
        self::STOP            => 'ai stop',
        self::USE_EMOJI       => 'ai use emoji',
        self::UNUSE_EMOJI     => 'ai unuse emoji',
    ];

    /**
     * OpenAI API との通信に使用するクラス
     *
     * @var OpenAi
     */
    protected OpenAi $openAi;

    /**
     * データベースの操作に使用するクラス
     *
     * @var Database
     */
    protected Database $database;

    /**
     * 現在の ChatBot の人格設定
     *
     * @var string
     */
    protected string $personality;

    /**
     * ChatBot の起動状態
     *
     * @var bool
     */
    protected bool $isStart;

    /**
     * 絵文字リアクションを使うか
     *
     * @var bool
     */
    protected bool $useEmoji;

    /**
     * __construct
     */
    public function __construct(string $discordToken, string $chatGptToken)
    {
        parent::__construct(['token' => $discordToken]);

        $this->openAi = new OpenAi($chatGptToken);
        $this->database = new Database();

        $this->personality = $this->database->getPersonality();
        $this->isStart = true;
        $this->useEmoji = true;

        $this->on('ready', function (): void {
            $this->on(Event::MESSAGE_CREATE, function (Message $message): void {
                // ボットのメッセージはスルー
                if ($message->author->bot) {
                    return;
                }

                // メッセージ内容から機能種類を選ぶ
                // 該当の機能がなければスルー
                $funcType = $this->selectFuncType($message);
                if ($funcType === null) {
                    return;
                }

                // ボットを止めていたらスルー
                if ($funcType !== self::START && !$this->isStart) {
                    return;
                }

                $channel = $this->getChannel($message->channel_id);

                // 実行可能な状態かチェック
                if (!$this->database->checkExecutable()) {
                    $channel->sendMessage('ちょっとまってね');

                    return;
                }

                // 入力中...を通知して機能ごとの処理を実行する
                $channel->broadcastTyping()->done(function () use ($channel, $funcType, $message): void {
                    $resultMessage = match ($funcType) {
                        self::CALL_API => $this->callApi($message),
                        self::SET_PERSONARITY => $this->setPersonality($message),
                        self::RESET => $this->reset(),
                        self::START => $this->start(),
                        self::STOP => $this->stop(),
                        self::USE_EMOJI => $this->useEmoji(),
                        self::UNUSE_EMOJI => $this->unuseEmoji(),
                        default => "想定外のコマンドが指定されました : {$funcType}"
                    };

                    $channel->sendMessage($resultMessage);
                });
            });
        });
    }

    /**
     * @param \Discord\Parts\Channel\Message $message
     * @return ?string
     */
    protected function selectFuncType(Message $message): ?string
    {
        $funcType = null;
        $content = $message->content;

        foreach (self::PREFIX_SET as $type => $prefix) {
            if (str_starts_with($content, $prefix)) {
                $funcType = $type;
            }
        }

        return $funcType;
    }

    /**
     * @return string
     */
    protected function getMessage(Message $message, string $funcType): string
    {
        $prefix = self::PREFIX_SET[$funcType];
        $personality = ltrim($message->content, $prefix);

        return trim($personality);
    }

    /**
     * @param \Discord\Parts\Channel\Message $message
     * @return string
     */
    protected function callApi(Message $message): string
    {
        $userMessage = $this->getMessage($message, self::CALL_API);
        $requestMessages = [];

        $conversationHistories = $this->database->getConversationHistories();
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

        $systemSetting = '';

        if ($this->useEmoji) {
            $systemSetting .= <<< END
            以後の会話では、まずあなたの現在の感情を表す絵文字を1つを出力し、その後に会話を出力してください。
            出力形式は以下のフォーマットとします。

            <-------------------------------->
            😊
            <-------------------------------->
            了解いたしました。それでははじめましょう。
            <-------------------------------->

            END;
        }

        if ($this->personality) {
            $systemSetting .= <<< END
                あなたは以下の人格を演じてください。

                $this->personality
                END;
        }

        if (!empty($systemSetting)) {
            $requestMessages[] = [
                'role' => 'system',
                'content' => $systemSetting,
            ];
        }

        try {
            $response = $this->openAi->chat([
                'model' => 'gpt-3.5-turbo',
                'messages' => $requestMessages,
                'max_tokens' => 500,
                'temperature' => 0.6,
            ]);
        } catch (Exception $e) {
            return "サーバーエラー : {$e->getMessage()}";
        }

        $result = json_decode($response, true);
        if (!isset($result['choices'][0]['message']['content'])) {
            $result = print_r($result, true);

            return "想定外のレスポンス : {$result}";
        }

        $result = $result['choices'][0]['message']['content'];

        if ($this->useEmoji) {
            var_dump($result);
            $splited = explode("<-------------------------------->", $result);
            $splited = array_filter($splited);

            if (count($splited) === 2) {
                $emoji = array_shift($splited);
                $emoji = trim($emoji);
                if (preg_match('/[\x{10000}-\x{10FFFF}]/u', $emoji)) {
                    $message->react($emoji);
                }
            }

            $result = (string)end($splited);
        }

        $this->database->saveConversationHistories([
            [
                'role' => 'user',
                'content' => $userMessage,
            ],
            [
                'role' => 'assistant',
                'content' => $result,
            ],
        ]);

        return $result;
    }

    /**
     * @param \Discord\Parts\Channel\Message $message
     * @return string
     */
    protected function setPersonality(Message $message): string
    {
        $personality = $this->getMessage($message, self::SET_PERSONARITY);
        $this->database->setPersonality($personality);
        $this->personality = $personality;

        return '設定完了しました。';
    }

    /**
     * @return string
     */
    protected function reset(): string
    {
        $this->database->resetConversationHistories();

        return '文脈をリセットしました。';
    }

    /**
     * @return string
     */
    protected function start(): string
    {
        $this->isStart = true;

        return 'ボットをスタートしました。';
    }

    /**
     * @return string
     */
    protected function stop(): string
    {
        $this->isStart = false;

        return 'ボットをストップしました。';
    }

    /**
     * @return string
     */
    protected function useEmoji(): string
    {
        $this->useEmoji = true;

        return '絵文字リアクションをスタートしました。';
    }

    /**
     * @return string
     */
    protected function unuseEmoji(): string
    {
        $this->useEmoji = false;

        return '絵文字リアクションをストップしました。';
    }
}
