<?php

use yii\db\Migration;

/**
 * Handles adding columns to table `{{%user}}`.
 */
class m240503_000010_add_public_key_to_user extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%user}}', 'public_key', $this->text()->after('avatar'));
        $this->addColumn('{{%user}}', 'key_updated_at', $this->integer()->after('public_key'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%user}}', 'key_updated_at');
        $this->dropColumn('{{%user}}', 'public_key');
    }
}