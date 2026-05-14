<?php

use yii\db\Migration;


class m240503_000008_create_online_user_table extends Migration
{
    
    public function safeUp()
    {
        $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';

        $this->createTable('{{%online_user}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'session_id' => $this->string(255)->notNull(),
            'ip_address' => $this->string(45)->notNull(),
            'user_agent' => $this->string(500),
            'last_activity' => $this->integer()->notNull(),
            'created_at' => $this->integer()->notNull(),
        ], $tableOptions);

        $this->createIndex(
            'idx-online_user-user_id',
            '{{%online_user}}',
            'user_id'
        );

        $this->createIndex(
            'idx-online_user-session_id',
            '{{%online_user}}',
            'session_id'
        );

        $this->createIndex(
            'idx-online_user-last_activity',
            '{{%online_user}}',
            'last_activity'
        );

        $this->createIndex(
            'uk-online_user-user_session',
            '{{%online_user}}',
            ['user_id', 'session_id'],
            true
        );

        $this->addForeignKey(
            'fk-online_user-user_id',
            '{{%online_user}}',
            'user_id',
            '{{%user}}',
            'id',
            'CASCADE'
        );
    }

    
    public function safeDown()
    {
        $this->dropForeignKey('fk-online_user-user_id', '{{%online_user}}');
        $this->dropTable('{{%online_user}}');
    }
}
