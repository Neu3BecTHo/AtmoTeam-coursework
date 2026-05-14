<?php

use yii\db\Migration;


class m240503_000001_create_user_table extends Migration
{
    public function safeUp()
    {
        $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';

        $this->createTable('{{%user}}', [
            'id' => $this->primaryKey(),
            'username' => $this->string(255)->notNull()->unique(),
            'email' => $this->string(255)->notNull()->unique(),
            'password_hash' => $this->string(255)->notNull(),
            'auth_key' => $this->string(32)->null(),
            'password_reset_token' => $this->string(255)->null(),
            'email_verified_at' => $this->integer()->null(),
            'verification_token' => $this->string(255)->null(),
            'avatar' => $this->string(255)->null(),
            'bio' => $this->text()->null(),
            'location' => $this->string(100)->null(),
            'website' => $this->string(255)->null(),
            'status' => $this->smallInteger()->notNull()->defaultValue(10),
            'is_private' => $this->boolean()->defaultValue(0),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], $tableOptions);

        $this->createIndex('idx-user-status', '{{%user}}', 'status');
        $this->createIndex('idx-user-private', '{{%user}}', 'is_private');
        $this->createIndex('idx-user-created_at', '{{%user}}', 'created_at');
    }

    public function safeDown()
    {
        $this->dropTable('{{%user}}');
    }
}
