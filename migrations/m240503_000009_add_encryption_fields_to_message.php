<?php

use yii\db\Migration;

/**
 * Handles adding columns to table `{{%message}}`.
 */
class m240503_000009_add_encryption_fields_to_message extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%message}}', 'encrypted_text', $this->text()->null()->after('content'));
        $this->addColumn('{{%message}}', 'encrypted_key', $this->text()->null()->after('encrypted_text'));
        $this->alterColumn('{{%message}}', 'content', $this->text()->null());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%message}}', 'encrypted_key');
        $this->dropColumn('{{%message}}', 'encrypted_text');
        $this->alterColumn('{{%message}}', 'content', $this->text()->notNull());
    }
}