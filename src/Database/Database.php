<?php
declare(strict_types=1);

namespace ChatGpt\Database;

use DateTime;
use SQLite3;

/**
 * Database
 */
class Database extends SQLite3
{
    public bool $isConnected = false;
    protected SQLite3 $conn;

    /**
     * __construct
     */
    public function __construct()
    {
        // データベースと接続
        $db = new SQLite3('./chat_gpt.db');
        $this->conn = $db;

        $this->_createTable();
    }

    /**
     * __construct
     */
    public function __destruct()
    {
        $this->conn->close();
    }

    /**
     * @return void
     */
    protected function _createTable(): void
    {
        $sql = 'SELECT count(*) FROM sqlite_master WHERE type="table" AND name="operating_status"';
        if (!$this->conn->querySingle($sql)) {
            $sql = "CREATE TABLE operating_status(
                id INTEGER PRIMARY KEY,
                last_boot_time DATETIME NOT NULL
            )";
            $this->conn->exec($sql);
        }
    }

    /**
     * @return void
     */
    public function check(): bool
    {
        $stmt = $this->conn->prepare('SELECT * FROM operating_status limit 1');
        $res = $stmt->execute()->fetchArray();
        $now = (new DateTime())->format('Y-m-d H:i:s');

        if ($res === false) {
            $stmt = $this->conn->prepare("INSERT INTO operating_status(
                last_boot_time
            ) VALUES (
                :last_boot_time
            )");

            $stmt->bindValue(':last_boot_time', $now, SQLITE3_TEXT);
            $res = $stmt->execute();

            return true;
        }

        $lastBootTime = new DateTime($res['last_boot_time']);
        if ((new DateTime())->modify('-15 Seconds') < $lastBootTime) {
            return false;
        }

        $stmt = $this->conn->prepare("UPDATE operating_status
            SET
                last_boot_time = :last_boot_time
        ");

        $stmt->bindValue(':last_boot_time', $now, SQLITE3_TEXT);
        $stmt->execute();

        return true;
    }
}
