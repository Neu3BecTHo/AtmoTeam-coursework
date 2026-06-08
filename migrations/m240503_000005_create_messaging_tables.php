<?php

use yii\db\Migration;

class m240503_000005_create_messaging_tables extends Migration
{
    public function safeUp()
    {
        $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';

        $this->createTable('{{%message}}', [
            'id' => $this->primaryKey(),
            'sender_id' => $this->integer()->notNull(),
            'receiver_id' => $this->integer()->notNull(),
            'content' => $this->text()->notNull(),
            'image_urls' => $this->text()->null(),
            'is_read' => $this->boolean()->defaultValue(0),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], $tableOptions);

        $this->createTable('{{%notification}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'type' => $this->string(50)->notNull(),
            'from_user_id' => $this->integer()->null(),
            'post_id' => $this->integer()->null(),
            'comment_id' => $this->integer()->null(),
            'message' => $this->text()->notNull(),
            'is_read' => $this->boolean()->defaultValue(0),
            'created_at' => $this->integer()->notNull(),
        ], $tableOptions);

        $this->addForeignKey('fk-message-sender_id', '{{%message}}', 'sender_id', '{{%user}}', 'id', 'CASCADE');
        $this->addForeignKey('fk-message-receiver_id', '{{%message}}', 'receiver_id', '{{%user}}', 'id', 'CASCADE');
        $this->addForeignKey('fk-notification-user_id', '{{%notification}}', 'user_id', '{{%user}}', 'id', 'CASCADE');

        $this->createIndex('idx-message-sender_receiver', '{{%message}}', ['sender_id', 'receiver_id']);
        $this->createIndex('idx-message-receiver_read', '{{%message}}', ['receiver_id', 'is_read']);
        $this->createIndex('idx-message-created_at', '{{%message}}', 'created_at');
        $this->createIndex('idx-notification-user_read', '{{%notification}}', ['user_id', 'is_read']);
        $this->createIndex('idx-notification-type', '{{%notification}}', 'type');
        $this->createIndex('idx-notification-created_at', '{{%notification}}', 'created_at');
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-notification-user_id', '{{%notification}}');
        $this->dropForeignKey('fk-message-receiver_id', '{{%message}}');
        $this->dropForeignKey('fk-message-sender_id', '{{%message}}');
        $this->dropTable('{{%notification}}');
        $this->dropTable('{{%message}}');
    }
}