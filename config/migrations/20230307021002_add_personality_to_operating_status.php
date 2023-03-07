<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddPersonalityToOperatingStatus extends AbstractMigration
{
    /**
     * @inheritDoc
     */
    public function change(): void
    {
        $table = $this->table('operating_status');
        $table
            ->addColumn('personality', 'string', [
                'default' => null,
                'null' => true,
                'limit' => 512,
            ])
            ->update();
    }
}
