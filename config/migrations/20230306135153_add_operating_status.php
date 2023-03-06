<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddOperatingStatus extends AbstractMigration
{
    /**
     * @inheritDoc
     */
    public function change(): void
    {
        $table = $this->table('operating_status');
        $table
            ->addColumn('last_boot_time', 'datetime', ['null' => true])
            ->create();
    }
}
