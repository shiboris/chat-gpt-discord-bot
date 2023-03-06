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
     * @return void
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

        if ((new DateTime())->modify('-3 Seconds') < $lastBootTime) {
            return false;
        }

        $operatingStatusTable
            ->where('id', '=', $operatingStatus->id)
            ->update(['last_boot_time' => new DateTime()]);

        return true;
    }
}
