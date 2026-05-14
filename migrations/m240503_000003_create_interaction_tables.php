<?php

use yii\db\Migration;


class m240503_000003_create_interaction_tables extends Migration
{
    public function safeUp()
    {
        $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';

        $this->createTable('{{%like}}', [
            'id' => $this->primaryKey(),
            'post_id' => $this->integer()->notNull(),
            'user_id' => $this->integer()->notNull(),
            'created_at' => $this->integer()->notNull(),
        ], $tableOptions);

        $this->createTable('{{%comment}}', [
            'id' => $this->primaryKey(),
            'post_id' => $this->integer()->notNull(),
            'user_id' => $this->integer()->notNull(),
            'content' => $this->text()->notNull(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], $tableOptions);

        $this->createTable('{{%repost}}', [
            'id' => $this->primaryKey(),
            'post_id' => $this->integer()->notNull(),
            'user_id' => $this->integer()->notNull(),
            'created_at' => $this->integer()->notNull(),
        ], $tableOptions);

        $this->addForeignKey('fk-like-post_id', '{{%like}}', 'post_id', '{{%post}}', 'id', 'CASCADE');
        $this->addForeignKey('fk-like-user_id', '{{%like}}', 'user_id', '{{%user}}', 'id', 'CASCADE');

        $this->addForeignKey('fk-comment-post_id', '{{%comment}}', 'post_id', '{{%post}}', 'id', 'CASCADE');
        $this->addForeignKey('fk-comment-user_id', '{{%comment}}', 'user_id', '{{%user}}', 'id', 'CASCADE');

        $this->addForeignKey('fk-repost-post_id', '{{%repost}}', 'post_id', '{{%post}}', 'id', 'CASCADE');
        $this->addForeignKey('fk-repost-user_id', '{{%repost}}', 'user_id', '{{%user}}', 'id', 'CASCADE');

        $this->createIndex('idx-like-unique', '{{%like}}', ['post_id', 'user_id'], true);
        $this->createIndex('idx-repost-unique', '{{%repost}}', ['post_id', 'user_id'], true);

        $this->createIndex('idx-like-post_id', '{{%like}}', 'post_id');
        $this->createIndex('idx-like-user_id', '{{%like}}', 'user_id');

        $this->createIndex('idx-comment-post_id', '{{%comment}}', 'post_id');
        $this->createIndex('idx-comment-user_id', '{{%comment}}', 'user_id');
        $this->createIndex('idx-comment-created_at', '{{%comment}}', 'created_at');

        $this->createIndex('idx-repost-post_id', '{{%repost}}', 'post_id');
        $this->createIndex('idx-repost-user_id', '{{%repost}}', 'user_id');
    }

    public function safeDown()
    {

        $this->dropForeignKey('fk-repost-user_id', '{{%repost}}');
        $this->dropForeignKey('fk-repost-post_id', '{{%repost}}');
        $this->dropForeignKey('fk-comment-user_id', '{{%comment}}');
        $this->dropForeignKey('fk-comment-post_id', '{{%comment}}');
        $this->dropForeignKey('fk-like-user_id', '{{%like}}');
        $this->dropForeignKey('fk-like-post_id', '{{%like}}');

        $this->dropTable('{{%repost}}');
        $this->dropTable('{{%comment}}');
        $this->dropTable('{{%like}}');
    }
}
