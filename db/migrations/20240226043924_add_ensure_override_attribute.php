<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddEnsureOverrideAttribute extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change(): void
    {
        $table = $this->table('codes');

        // the column may have been added by hand before migrations ran on production
        if (!$table->hasColumn('ensure_override_attribute')) {
            $table->addColumn('ensure_override_attribute', 'bit', ['length' => 1, 'default' => 0])->update();
        }
    }
}
