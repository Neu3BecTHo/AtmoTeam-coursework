<?php

use yii\db\Migration;


class m240503_000004_create_social_tables extends Migration
{
    public function safeUp()
    {
        $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';

        $this->createTable('{{%follow}}', [
            'id' => $this->primaryKey(),
            'follower_id' => $this->integer()->notNull(),
            'following_id' => $this->integer()->notNull(),
            'created_at' => $this->integer()->notNull(),
        ], $tableOptions);

        $this->createTable('{{%block}}', [
            'id' => $this->primaryKey(),
            'blocker_id' => $this->integer()->notNull(),
            'blocked_id' => $this->integer()->notNull(),
            'created_at' => $this->integer()->notNull(),
        ], $tableOptions);

        $this->createTable('{{%saved_post}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'post_id' => $this->integer()->notNull(),
            'created_at' => $this->integer()->notNull(),
        ], $tableOptions);

        $this->addForeignKey('fk-follow-follower_id', '{{%follow}}', 'follower_id', '{{%user}}', 'id', 'CASCADE');
        $this->addForeignKey('fk-follow-following_id', '{{%follow}}', 'following_id', '{{%user}}', 'id', 'CASCADE');

        $this->addForeignKey('fk-block-blocker_id', '{{%block}}', 'blocker_id', '{{%user}}', 'id', 'CASCADE');
        $this->addForeignKey('fk-block-blocked_id', '{{%block}}', 'blocked_id', '{{%user}}', 'id', 'CASCADE');

        $this->addForeignKey('fk-saved_post-user_id', '{{%saved_post}}', 'user_id', '{{%user}}', 'id', 'CASCADE');
        $this->addForeignKey('fk-saved_post-post_id', '{{%saved_post}}', 'post_id', '{{%post}}', 'id', 'CASCADE');

        $this->createIndex('idx-follow-unique', '{{%follow}}', ['follower_id', 'following_id'], true);
        $this->createIndex('idx-block-unique', '{{%block}}', ['blocker_id', 'blocked_id'], true);
        $this->createIndex('idx-saved_post-unique', '{{%saved_post}}', ['user_id', 'post_id'], true);

        $this->createIndex('idx-follow-follower_id', '{{%follow}}', 'follower_id');
        $this->createIndex('idx-follow-following_id', '{{%follow}}', 'following_id');

        $this->createIndex('idx-block-blocker_id', '{{%block}}', 'blocker_id');
        $this->createIndex('idx-block-blocked_id', '{{%block}}', 'blocked_id');

        $this->createIndex('idx-saved_post-user_id', '{{%saved_post}}', 'user_id');
        $this->createIndex('idx-saved_post-post_id', '{{%saved_post}}', 'post_id');
    }

    public function safeDown()
    {

        $this->dropForeignKey('fk-saved_post-post_id', '{{%saved_post}}');
        $this->dropForeignKey('fk-saved_post-user_id', '{{%saved_post}}');
        $this->dropForeignKey('fk-block-blocked_id', '{{%block}}');
        $this->dropForeignKey('fk-block-blocker_id', '{{%block}}');
        $this->dropForeignKey('fk-follow-following_id', '{{%follow}}');
        $this->dropForeignKey('fk-follow-follower_id', '{{%follow}}');

        $this->dropTable('{{%saved_post}}');
        $this->dropTable('{{%block}}');
        $this->dropTable('{{%follow}}');
    }
}
