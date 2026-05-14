<?php

use yii\db\Migration;


class m240503_000007_setup_rbac extends Migration
{
    public function safeUp()
    {
        $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';

        $this->createTable('{{%auth_assignment}}', [
            'item_name' => $this->string(64)->notNull(),
            'user_id' => $this->integer()->notNull(),
            'created_at' => $this->integer(),
        ], $tableOptions);

        $this->createTable('{{%auth_item}}', [
            'name' => $this->string(64)->notNull(),
            'type' => $this->smallInteger()->notNull(),
            'description' => $this->text(),
            'rule_name' => $this->string(64),
            'data' => $this->binary(),
            'created_at' => $this->integer(),
            'updated_at' => $this->integer(),
        ], $tableOptions);

        $this->createTable('{{%auth_item_child}}', [
            'parent' => $this->string(64)->notNull(),
            'child' => $this->string(64)->notNull(),
        ], $tableOptions);

        $this->createTable('{{%auth_rule}}', [
            'name' => $this->string(64)->notNull(),
            'data' => $this->binary(),
            'created_at' => $this->integer(),
            'updated_at' => $this->integer(),
        ], $tableOptions);

        $this->addPrimaryKey('pk-auth_assignment', '{{%auth_assignment}}', ['item_name', 'user_id']);
        $this->addPrimaryKey('pk-auth_item', '{{%auth_item}}', 'name');
        $this->addPrimaryKey('pk-auth_item_child', '{{%auth_item_child}}', ['parent', 'child']);
        $this->addPrimaryKey('pk-auth_rule', '{{%auth_rule}}', 'name');

        $this->createIndex('idx-auth_assignment-user_id', '{{%auth_assignment}}', 'user_id');
        $this->createIndex('idx-auth_item-type', '{{%auth_item}}', 'type');

        $this->addForeignKey('fk-auth_assignment-item_name', '{{%auth_assignment}}', 'item_name', '{{%auth_item}}', 'name', 'CASCADE');
        $this->addForeignKey('fk-auth_assignment-user_id', '{{%auth_assignment}}', 'user_id', '{{%user}}', 'id', 'CASCADE');
        $this->addForeignKey('fk-auth_item_child-parent', '{{%auth_item_child}}', 'parent', '{{%auth_item}}', 'name', 'CASCADE');
        $this->addForeignKey('fk-auth_item_child-child', '{{%auth_item_child}}', 'child', '{{%auth_item}}', 'name', 'CASCADE');
        $this->addForeignKey('fk-auth_item-rule_name', '{{%auth_item}}', 'rule_name', '{{%auth_rule}}', 'name', 'SET NULL');

        $this->batchInsert('{{%auth_item}}', ['name', 'type', 'description', 'created_at', 'updated_at'], [
            ['admin', 1, 'Administrator', time(), time()],
            ['user', 1, 'Regular user', time(), time()],
            ['createPost', 2, 'Create a post', time(), time()],
            ['updatePost', 2, 'Update a post', time(), time()],
            ['deletePost', 2, 'Delete a post', time(), time()],
            ['accessAdminPanel', 2, 'Доступ к админ-панели', time(), time()],
        ]);

        $this->batchInsert('{{%auth_item_child}}', ['parent', 'child'], [
            ['user', 'createPost'],
            ['user', 'updatePost'],
            ['user', 'deletePost'],
            ['admin', 'user'],
            ['admin', 'accessAdminPanel'],
        ]);

        $this->insert('{{%user}}', [
            'username' => 'admin',
            'email' => 'admin@example.com',
            'password_hash' => Yii::$app->security->generatePasswordHash('admin123'),
            'auth_key' => Yii::$app->security->generateRandomString(),
            'status' => 10,
            'created_at' => time(),
            'updated_at' => time(),
        ]);

        $this->insert('{{%auth_assignment}}', [
            'item_name' => 'admin',
            'user_id' => 1, // Предполагаем ID=1 для первого пользователя
            'created_at' => time(),
        ]);
    }

    public function safeDown()
    {

        $this->dropForeignKey('fk-auth_item-rule_name', '{{%auth_item}}');
        $this->dropForeignKey('fk-auth_item_child-child', '{{%auth_item_child}}');
        $this->dropForeignKey('fk-auth_item_child-parent', '{{%auth_item_child}}');
        $this->dropForeignKey('fk-auth_assignment-user_id', '{{%auth_assignment}}');
        $this->dropForeignKey('fk-auth_assignment-item_name', '{{%auth_assignment}}');

        $this->dropTable('{{%auth_rule}}');
        $this->dropTable('{{%auth_item_child}}');
        $this->dropTable('{{%auth_item}}');
        $this->dropTable('{{%auth_assignment}}');
    }
}
