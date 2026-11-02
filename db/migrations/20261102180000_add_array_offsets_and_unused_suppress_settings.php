<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddArrayOffsetsAndUnusedSuppressSettings extends AbstractMigration
{
    public function change(): void
    {
        // ensure_array_offsets_exist defaults to 1: snippets saved before this column existed were analysed with it on
        $table = $this->table('codes');

        // skip columns that already exist, e.g. added by hand to unbreak production
        if (!$table->hasColumn('ensure_array_offsets_exist')) {
            $table->addColumn('ensure_array_offsets_exist', 'bit', ['length' => 1, 'default' => 1]);
        }

        if (!$table->hasColumn('find_unused_psalm_suppress')) {
            $table->addColumn('find_unused_psalm_suppress', 'bit', ['length' => 1, 'default' => 0]);
        }

        $table->update();
    }
}
