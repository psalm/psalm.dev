<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddArrayOffsetsAndUnusedSuppressSettings extends AbstractMigration
{
    public function change(): void
    {
        // ensure_array_offsets_exist defaults to 1: snippets saved before this column existed were analysed with it on
        $this->table('codes')
            ->addColumn('ensure_array_offsets_exist', 'bit', ['length' => 1, 'default' => 1])
            ->addColumn('find_unused_psalm_suppress', 'bit', ['length' => 1, 'default' => 0])
            ->update();
    }
}
