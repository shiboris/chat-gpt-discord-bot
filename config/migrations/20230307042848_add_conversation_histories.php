<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddConversationHistories extends AbstractMigration
{
    /**
     * @inheritDoc
     */
    public function change(): void
    {
        $table = $this->table('conversation_histories');
        $table
            ->addColumn('role', 'string', [
                'null' => false,
                'default' => null,
                'limit' => 16,
            ])
            ->addColumn('content', 'string', [
                'null' => false,
                'default' => null,
                'limit' => 1024,
            ])
            ->addColumn('created', 'datetime', [
                'null' => false,
                'default' => null,
            ])
            ->create();
    }
}
