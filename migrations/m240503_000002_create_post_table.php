<?php

use yii\db\Migration;


class m240503_000002_create_post_table extends Migration
{
    public function safeUp()
    {
        $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';

        $this->createTable('{{%post}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'content' => $this->text()->notNull(),
            'image' => $this->string(255)->null(),
            'likes_count' => $this->integer()->notNull()->defaultValue(0),
            'comments_count' => $this->integer()->notNull()->defaultValue(0),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], $tableOptions);

        $this->addForeignKey(
            'fk-post-user_id',
            '{{%post}}',
            'user_id',
            '{{%user}}',
            'id',
            'CASCADE'
        );

        $this->createIndex('idx-post-user_id', '{{%post}}', 'user_id');
        $this->createIndex('idx-post-created_at', '{{%post}}', 'created_at');
        $this->createIndex('idx-post-user_created', '{{%post}}', ['user_id', 'created_at']);
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-post-user_id', '{{%post}}');
        $this->dropTable('{{%post}}');
    }
}
