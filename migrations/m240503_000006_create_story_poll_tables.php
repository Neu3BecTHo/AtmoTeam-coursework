<?php

use yii\db\Migration;


class m240503_000006_create_story_poll_tables extends Migration
{
    public function safeUp()
    {
        $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';

        $this->createTable('{{%story}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'image' => $this->string(255)->notNull(),
            'caption' => $this->text()->null(),
            'expires_at' => $this->integer()->notNull(),
            'created_at' => $this->integer()->notNull(),
        ], $tableOptions);

        $this->createTable('{{%poll}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'post_id' => $this->integer()->null(),
            'question' => $this->text()->notNull(),
            'expires_at' => $this->integer()->null(),
            'created_at' => $this->integer()->notNull(),
        ], $tableOptions);

        $this->createTable('{{%poll_option}}', [
            'id' => $this->primaryKey(),
            'poll_id' => $this->integer()->notNull(),
            'text' => $this->string(255)->notNull(),
            'votes_count' => $this->integer()->notNull()->defaultValue(0),
        ], $tableOptions);

        $this->createTable('{{%poll_vote}}', [
            'id' => $this->primaryKey(),
            'poll_id' => $this->integer()->notNull(),
            'poll_option_id' => $this->integer()->notNull(),
            'user_id' => $this->integer()->notNull(),
            'created_at' => $this->integer()->notNull(),
        ], $tableOptions);

        $this->addForeignKey('fk-story-user_id', '{{%story}}', 'user_id', '{{%user}}', 'id', 'CASCADE');

        $this->addForeignKey('fk-poll-user_id', '{{%poll}}', 'user_id', '{{%user}}', 'id', 'CASCADE');
        $this->addForeignKey('fk-poll-post_id', '{{%poll}}', 'post_id', '{{%post}}', 'id', 'CASCADE');

        $this->addForeignKey('fk-poll_option-poll_id', '{{%poll_option}}', 'poll_id', '{{%poll}}', 'id', 'CASCADE');

        $this->addForeignKey('fk-poll_vote-poll_id', '{{%poll_vote}}', 'poll_id', '{{%poll}}', 'id', 'CASCADE');
        $this->addForeignKey('fk-poll_vote-poll_option_id', '{{%poll_vote}}', 'poll_option_id', '{{%poll_option}}', 'id', 'CASCADE');
        $this->addForeignKey('fk-poll_vote-user_id', '{{%poll_vote}}', 'user_id', '{{%user}}', 'id', 'CASCADE');

        $this->createIndex('idx-poll_vote-unique', '{{%poll_vote}}', ['poll_id', 'user_id'], true);

        $this->createIndex('idx-story-user_expires', '{{%story}}', ['user_id', 'expires_at']);
        $this->createIndex('idx-story-expires', '{{%story}}', 'expires_at');
        $this->createIndex('idx-story-created_at', '{{%story}}', 'created_at');

        $this->createIndex('idx-poll-user_id', '{{%poll}}', 'user_id');
        $this->createIndex('idx-poll-post_id', '{{%poll}}', 'post_id');
        $this->createIndex('idx-poll-expires_at', '{{%poll}}', 'expires_at');
        $this->createIndex('idx-poll-created_at', '{{%poll}}', 'created_at');

        $this->createIndex('idx-poll_option-poll_id', '{{%poll_option}}', 'poll_id');

        $this->createIndex('idx-poll_vote-poll_id', '{{%poll_vote}}', 'poll_id');
        $this->createIndex('idx-poll_vote-user_id', '{{%poll_vote}}', 'user_id');
    }

    public function safeDown()
    {

        $this->dropForeignKey('fk-poll_vote-user_id', '{{%poll_vote}}');
        $this->dropForeignKey('fk-poll_vote-poll_option_id', '{{%poll_vote}}');
        $this->dropForeignKey('fk-poll_vote-poll_id', '{{%poll_vote}}');
        $this->dropForeignKey('fk-poll_option-poll_id', '{{%poll_option}}');
        $this->dropForeignKey('fk-poll-user_id', '{{%poll}}');
        $this->dropForeignKey('fk-story-user_id', '{{%story}}');

        $this->dropTable('{{%poll_vote}}');
        $this->dropTable('{{%poll_option}}');
        $this->dropTable('{{%poll}}');
        $this->dropTable('{{%story}}');
    }
}
