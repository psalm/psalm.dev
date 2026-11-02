<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddPhpVersion extends AbstractMigration
{
    public function change(): void
    {
        // no default: snippets saved before this column existed must stay NULL and use the current default version
        $table = $this->table('codes');

        // skip columns that already exist, e.g. added by hand to unbreak production
        if (!$table->hasColumn('php_version')) {
            $table->addColumn('php_version', 'string', ['limit' => 3, 'null' => true, 'default' => null]);
        }

        $table->update();
    }
}
