<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddErrorLevel extends AbstractMigration
{
    public function change(): void
    {
        // no default: snippets saved before this column existed must stay NULL and use the default level (2)
        $table = $this->table('codes');

        // skip columns that already exist, e.g. added by hand to unbreak production; rollback replays
        // change() to record what to invert, so the guard must only apply when migrating up
        if (!$this->isMigratingUp() || !$table->hasColumn('error_level')) {
            $table->addColumn('error_level', 'integer', [
                'limit' => \Phinx\Db\Adapter\MysqlAdapter::INT_TINY,
                'signed' => false,
                'null' => true,
                'default' => null,
            ]);
        }

        $table->update();
    }
}
