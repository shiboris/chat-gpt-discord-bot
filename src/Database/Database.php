<?php
declare(strict_types=1);

namespace ChatGpt\Database;

use DateTime;
use Illuminate\Database\Capsule\Manager;
use SQLite3;

/**
 * Database
 */
class Database extends SQLite3
{
    /**
     * __construct
     */
    public function __construct()
    {
        $manager = new Manager();

        $manager->addConnection([
            'driver' => 'sqlite',
            'database' => 'chat_gpt.sqlite3',
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
        ]);
        $manager->setAsGlobal();
        $manager->bootEloquent();
    }

    /**
     * @return bool
     */
    public function checkExecutable(): bool
    {
        $operatingStatusTable = Manager::table('operating_status');

        if (!$operatingStatusTable->exists()) {
            $values = [
                'last_boot_time' => new DateTime(),
            ];
            $operatingStatusTable->insert($values);

            return true;
        }

        $operatingStatus = $operatingStatusTable->get()->first();
        $lastBootTime = new DateTime($operatingStatus->last_boot_time);

        if ((new DateTime())->modify('-5 Seconds') < $lastBootTime) {
            return false;
        }

        $operatingStatusTable
            ->where('id', '=', $operatingStatus->id)
            ->update(['last_boot_time' => new DateTime()]);

        return true;
    }

    /**
     * @return string|null
     */
    public function getPersonality(): ?string
    {
        $operatingStatusTable = Manager::table('operating_status');

        if (!$operatingStatusTable->exists()) {
            return null;
        }

        $operatingStatus = $operatingStatusTable->get()->first();
        $personality = $operatingStatus->personality;

        if (!is_string($personality)) {
            return null;
        }

        return $personality;
    }

    /**
     * @param string $personality
     * @return void
     */
    public function setPersonality(string $personality): void
    {
        $operatingStatusTable = Manager::table('operating_status');

        if (!$operatingStatusTable->exists()) {
            $values = [
                'last_boot_time' => new DateTime(),
                'personality' => $personality,
            ];
            $operatingStatusTable->insert($values);

            return;
        }

        $operatingStatus = $operatingStatusTable->get()->first();
        $operatingStatusTable
            ->where('id', '=', $operatingStatus->id)
            ->update(['personality' => $personality]);
    }

    /**
     * @return array
     */
    public function getConversationHistories(): array
    {
        $conversationHistoriesTable = Manager::table('conversation_histories');

        if (!$conversationHistoriesTable->exists()) {
            return [];
        }

        $conversationHistories = $conversationHistoriesTable
            ->orderByDesc('id')
            ->limit(20)
            ->get()
            ->sortBy('id')
            ->all();

        return $conversationHistories;
    }

    /**
     * @return void
     */
    public function saveConversationHistories(array $conversations): void
    {
        $conversationHistoriesTable = Manager::table('conversation_histories');

        foreach ($conversations as $conversation) {
            $values = [
                'role' => $conversation['role'],
                'content' => $conversation['content'],
                'created' => new DateTime(),
            ];
            $conversationHistoriesTable->insert($values);
        }
    }
}
